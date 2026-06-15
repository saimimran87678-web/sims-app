<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseAuth;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseController extends Controller
{
    /**
     * Activate the license key submitted via the web UI form.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $licenseKey = trim($request->input('license_key'));

        // Validate Env Setup
        $apiKey = config('services.firebase.api_key');
        $projectId = config('services.firebase.project_id');
        $rsaKey = config('services.license.rsa_public_key');

        if (empty($apiKey) || empty($projectId)) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase environment variables are not configured on this server.',
            ], 500);
        }

        if (empty($rsaKey)) {
            return response()->json([
                'success' => false,
                'message' => 'LICENSE_RSA_PUBLIC_KEY is not configured in the server environment.',
            ], 500);
        }

        // 1. Establish Anonymous Firebase Session to generate a Refresh Token
        try {
            $response = Http::withoutVerifying()->post("https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}", [
                'returnSecureToken' => true
            ]);

            if ($response->successful()) {
                $refreshToken = $response->json('refreshToken');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to register Firebase session: ' . ($response->json('error.message') ?? 'Unknown error'),
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase connectivity failed. Check your internet connection: ' . $e->getMessage(),
            ], 500);
        }

        // 2. Exchange Refresh Token for an ID Token
        $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
        if (!$tokenData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to establish secure token exchange session.',
            ], 400);
        }

        $idToken = $tokenData['id_token'];
        $newRefreshToken = $tokenData['refresh_token'];

        // 3. Fetch License Document from Firestore
        $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
        if (!$firebaseLic) {
            return response()->json([
                'success' => false,
                'message' => 'License not found in licensing database. Please verify the License Key is typed correctly.',
            ], 404);
        }

        // 4. Verify Cryptographic RSA Signature
        $isValidSig = LicenseVerifier::verifyRsaSignature(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['rsa_signature']
        );

        if (!$isValidSig) {
            return response()->json([
                'success' => false,
                'message' => 'Cryptographic signature verification failed. The license appears modified or signed with an invalid key.',
            ], 400);
        }

        // 5. Compute local HMAC integrity hash
        $newHash = LicenseVerifier::computeIntegrityHash(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['school_id']
        );

        // 6. Save to local SQLite cache
        try {
            DB::table('software_licenses')->truncate();
            
            DB::table('software_licenses')->insert([
                'license_key' => encrypt($licenseKey),
                'school_id' => $firebaseLic['school_id'],
                'firebase_refresh_token' => encrypt($newRefreshToken),
                'status' => encrypt($firebaseLic['status']),
                'plan' => encrypt($firebaseLic['plan']),
                'expires_at' => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at']) : null,
                'rsa_signature' => $firebaseLic['rsa_signature'],
                'integrity_hash' => $newHash,
                'offline_grace_days' => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Programmatically update the .env file
            $this->updateEnvFile('LICENSE_KEY', $licenseKey);

            // Flush application cache
            LicenseStatus::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'License successfully activated!',
            ]);
        } catch (\Exception $e) {
            Log::error('License Web Activation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to write license parameters locally: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Programmatically update key-value in local .env file.
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Match with or without quotes: LICENSE_KEY=... or LICENSE_KEY="..."
            $pattern = "/^{$key}=(.*)$/m";
            $replacement = "{$key}=\"{$value}\"";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$key}=\"{$value}\"\n";
            }

            file_put_contents($path, $content);
        }
    }
}
