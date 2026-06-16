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
     * Sync license using LICENSE_KEY already stored in .env.
     * Called from the "I've Renewed – Sync Now" banner button.
     * No user input required.
     */
    public function sync(Request $request)
    {
        $licenseKey = trim(config('services.license.key', ''));

        if (empty($licenseKey)) {
            return response()->json([
                'success' => false,
                'message' => 'No LICENSE_KEY is configured on this PC. Please enter your license key below.',
            ], 422);
        }

        return $this->runActivation($licenseKey);
    }

    /**
     * Activate with a license key submitted manually via the blocked-page form.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        return $this->runActivation(trim($request->input('license_key')));
    }

    /**
     * Core activation logic — shared by sync() and activate().
     * Fetches from Firestore, verifies RSA signature, writes to SQLite, clears cache.
     */
    private function runActivation(string $licenseKey): \Illuminate\Http\JsonResponse
    {
        $apiKey    = config('services.firebase.api_key');
        $projectId = config('services.firebase.project_id');
        $rsaKey    = config('services.license.rsa_public_key');

        if (empty($apiKey) || empty($projectId)) {
            return response()->json([
                'success' => false,
                'message' => 'This installation is not fully configured. Please contact your vendor to resolve this.',
            ], 500);
        }

        if (empty($rsaKey)) {
            return response()->json([
                'success' => false,
                'message' => 'License verification keys are not configured on this installation. Please contact your vendor.',
            ], 500);
        }

        // Step 1 — Establish anonymous Firebase session
        try {
            $response = Http::withoutVerifying()->post(
                "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}",
                ['returnSecureToken' => true]
            );

            if ($response->successful()) {
                $refreshToken = $response->json('refreshToken');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not reach the SIMS License Server. Please check your internet connection and try again.',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No internet connection. Please connect to the internet and try again.',
            ], 500);
        }

        // Step 2 — Exchange for ID token
        $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
        if (!$tokenData) {
            return response()->json([
                'success' => false,
                'message' => 'Could not establish a secure session with our servers. Please try again.',
            ], 400);
        }

        $idToken        = $tokenData['id_token'];
        $newRefreshToken = $tokenData['refresh_token'];

        // Step 3 — Fetch license from Firestore
        $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
        if (!$firebaseLic) {
            return response()->json([
                'success' => false,
                'message' => "License key \"" . substr($licenseKey, 0, 20) . "...\" was not found on our servers. Please verify the key is correct or contact your vendor.",
            ], 404);
        }

        // Step 4 — Verify RSA cryptographic signature
        $isValidSig = LicenseVerifier::verifyRsaSignature(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['rsa_signature']
        );

        if (!$isValidSig) {
            return response()->json([
                'success' => false,
                'message' => 'License verification failed. The license data could not be authenticated. Please contact your vendor to re-issue the license.',
            ], 400);
        }

        // Step 5 — Compute integrity hash
        $newHash = LicenseVerifier::computeIntegrityHash(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['school_id']
        );

        // Step 6 — Save to local SQLite + update .env + clear cache
        try {
            DB::table('software_licenses')->truncate();
            DB::table('software_licenses')->insert([
                'license_key'             => encrypt($licenseKey),
                'school_id'               => $firebaseLic['school_id'],
                'firebase_refresh_token'  => encrypt($newRefreshToken),
                'status'                  => encrypt($firebaseLic['status']),
                'plan'                    => encrypt($firebaseLic['plan']),
                'expires_at'              => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at']) : null,
                'rsa_signature'           => $firebaseLic['rsa_signature'],
                'integrity_hash'          => $newHash,
                'offline_grace_days'      => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'created_at'              => Carbon::now(),
                'updated_at'              => Carbon::now(),
            ]);

            $this->updateEnvFile('LICENSE_KEY', $licenseKey);
            LicenseStatus::clearCache();

            $statusLabel = ucfirst($firebaseLic['status']);
            $plan        = strtoupper($firebaseLic['plan']);
            $expiry      = $firebaseLic['expires_at']
                ? Carbon::parse($firebaseLic['expires_at'])->format('d M Y')
                : 'N/A';

            return response()->json([
                'success'  => true,
                'status'   => $firebaseLic['status'],
                'message'  => "✅ License synced successfully!\n\nStatus: {$statusLabel} | Plan: {$plan} | Expires: {$expiry}",
                'redirect' => $firebaseLic['status'] === 'active' ? route('dashboard') : null,
            ]);
        } catch (\Exception $e) {
            Log::error('License sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save license locally: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a key=value pair in the local .env file.
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) return;

        $content = file_get_contents($path);
        $pattern = "/^{$key}=(.*)$/m";
        $replacement = "{$key}=\"{$value}\"";

        $content = preg_match($pattern, $content)
            ? preg_replace($pattern, $replacement, $content)
            : $content . "\n{$key}=\"{$value}\"\n";

        file_put_contents($path, $content);
    }
}
