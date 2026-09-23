<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseAuth;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ActivateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:activate 
                            {license_key? : The signed license key issued to the school (optional, reads from .env if omitted)} 
                            {--refresh-token= : Pre-existing Firebase refresh token, if any}';

    /**
     * Command aliases
     *
     * @var array<string>
     */
    protected $aliases = ['sims:activate'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate the school license by connecting to Firebase and seeding local cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $licenseKey = $this->argument('license_key');

        // Fallback to .env if argument was not provided
        if (empty($licenseKey)) {
            $licenseKey = env('LICENSE_KEY') ?: config('services.license.key');
        }

        // Interactively prompt user if still not provided
        if (empty($licenseKey)) {
            $licenseKey = $this->ask('🔑 Please enter your SIMS License Key (e.g. SIMS-XXXX-XXXXXXXXXXXXX)');
        }

        $licenseKey = trim((string) $licenseKey);

        if (empty($licenseKey)) {
            $this->error('❌ Error: A valid license key is required to activate SIMS.');
            return 1;
        }

        $refreshToken = $this->option('refresh-token');

        $this->info("🔑 Starting SIMS License Activation Process...");
        $this->line("License Key: {$licenseKey}");

        // Validate Env Setup
        $apiKey = config('services.firebase.api_key');
        $projectId = config('services.firebase.project_id');
        $rsaKey = config('services.license.rsa_public_key');

        if (empty($apiKey) || empty($projectId)) {
            $this->error("❌ Error: Firebase environment variables are not configured in your .env file.");
            $this->line("Please configure: FIREBASE_API_KEY, FIREBASE_PROJECT_ID");
            return 1;
        }

        if (empty($rsaKey)) {
            $this->error("❌ Error: LICENSE_RSA_PUBLIC_KEY is not configured in your .env file.");
            return 1;
        }

        // 1. Get Refresh Token (Anonymous sign-in fallback if not provided)
        if (empty($refreshToken)) {
            $this->info("🌐 No refresh token provided. Generating anonymous Firebase session...");
            try {
                $response = Http::withoutVerifying()->post("https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}", [
                    'returnSecureToken' => true
                ]);

                if ($response->successful()) {
                    $refreshToken = $response->json('refreshToken');
                    $this->info("✓ Anonymous Firebase session established.");
                } else {
                    $this->error("❌ Failed to register anonymous session: " . $response->body());
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("❌ Firebase Connection failed: " . $e->getMessage());
                return 1;
            }
        }

        // 2. Fetch ID Token
        $this->info("🔑 Exchanging refresh token for secure session token...");
        $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
        if (!$tokenData) {
            $this->error("❌ Token exchange failed. Please check your internet connection or refresh token.");
            return 1;
        }

        $idToken = $tokenData['id_token'];
        $newRefreshToken = $tokenData['refresh_token'];

        // 3. Query Firestore
        $this->info("📡 Fetching license metadata from Firestore...");
        $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
        if (!$firebaseLic) {
            $this->error("❌ License not found in Firestore. Please verify the License Key is correct.");
            return 1;
        }

        // 4. Verify Cryptographic Signature
        $this->info("🛡️ Verifying cryptographic RSA signature...");
        $isValidSig = LicenseVerifier::verifyRsaSignature(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['rsa_signature']
        );

        if (!$isValidSig) {
            $this->error("❌ Cryptographic signature verification FAILED!");
            $this->error("The license data received has been tampered with or is signed with an invalid private key.");
            return 1;
        }
        $this->info("✓ Cryptographic signature is valid.");

        // 5. Compute Integrity Hash
        // 5. Setup Gatekeeper: Check license status
        $licStatus = strtolower(trim($firebaseLic['status'] ?? ''));
        if ($licStatus === 'suspended') {
            $this->error("❌ License is SUSPENDED. Please contact support or renew your subscription.");
            return 1;
        }

        if ($licStatus === 'expired') {
            $this->error("❌ License has EXPIRED. Please renew your subscription.");
            return 1;
        }

        if (!empty($firebaseLic['expires_at'])) {
            $expiry = Carbon::parse($firebaseLic['expires_at'])->startOfDay();
            if (Carbon::now()->startOfDay()->gt($expiry)) {
                $this->error("❌ This license key expired on " . $expiry->format('d M Y') . ".");
                return 1;
            }
        }

        // 6. Compute Integrity Hash
        $allowedDomains = $firebaseLic['allowed_domain'] ?? 'localhost';
        $newHash = LicenseVerifier::computeIntegrityHash(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['school_id'],
            $allowedDomains
        );

        // 7. Write to SQLite Cache
        $this->info("💾 Seeding local SQLite cache database...");
        try {
            DB::table('software_licenses')->delete();
            
            DB::table('software_licenses')->insert([
                'license_key'             => encrypt($licenseKey),
                'school_id'               => $firebaseLic['school_id'],
                'firebase_refresh_token'  => encrypt($newRefreshToken),
                'status'                  => encrypt($firebaseLic['status']),
                'plan'                    => encrypt($firebaseLic['plan']),
                'allowed_domains'         => encrypt($allowedDomains),
                'expires_at'              => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at']) : null,
                'rsa_signature'           => $firebaseLic['rsa_signature'],
                'integrity_hash'          => $newHash,
                'offline_grace_days'      => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'created_at'              => Carbon::now(),
                'updated_at'              => Carbon::now(),
            ]);

            $this->info("✓ SQLite database populated.");

            // Update .env with validated key
            $this->updateEnvFile('LICENSE_KEY', $licenseKey);
            $this->info("✓ Saved LICENSE_KEY to .env file.");

            // Immediately clear the app cache so the next web request
            // picks up the new ACTIVE status without waiting for TTL expiry.
            LicenseStatus::clearCache();
            $this->info("✓ License cache cleared.");
        } catch (\Exception $e) {
            $this->error("❌ Local database insert failed: " . $e->getMessage());
            return 1;
        }

        $this->line("");
        $this->info("==========================================================");
        $this->info("🎉 SIMS LICENSE SUCCESSFULLY ACTIVATED!");
        $this->info("==========================================================");
        $this->line("School ID : " . $firebaseLic['school_id']);
        $this->line("Plan      : " . strtoupper($firebaseLic['plan']));
        $this->line("Status    : " . strtoupper($firebaseLic['status']));
        $this->line("Expires At: " . ($firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at'])->format('Y-m-d H:i:s') : 'Never'));
        $this->info("==========================================================");

        return 0;
    }

    /**
     * Helper to safely update key-value pairs in local .env
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
