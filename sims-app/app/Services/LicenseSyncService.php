<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseSyncService
{
    /**
     * Executes the background synchronization logic without returning HTTP responses.
     * Returns true on success, false on failure.
     */
    public static function syncBackground(): bool
    {
        try {
            $record = DB::table('software_licenses')->first();
            if (!$record) {
                return false;
            }

            $licenseKey = decrypt($record->license_key);
            $refreshToken = decrypt($record->firebase_refresh_token);

            $apiKey    = config('services.firebase.api_key');
            $projectId = config('services.firebase.project_id');
            $rsaKey    = config('services.license.rsa_public_key');

            if (empty($apiKey) || empty($projectId) || empty($rsaKey)) {
                return false;
            }

            // Step 1 - Exchange for ID token
            $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
            if (!$tokenData) {
                return false;
            }

            $idToken = $tokenData['id_token'];
            $newRefreshToken = $tokenData['refresh_token'];

            // Step 2 - Fetch license from Firestore
            $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
            if (!$firebaseLic) {
                return false;
            }

            // Step 3 - Verify RSA cryptographic signature
            $isValidSig = LicenseVerifier::verifyRsaSignature(
                $licenseKey,
                $firebaseLic['expires_at'],
                $firebaseLic['status'],
                $firebaseLic['rsa_signature']
            );

            if (!$isValidSig) {
                return false;
            }

            // Step 4 - Compute new integrity hash
            $newHash = LicenseVerifier::computeIntegrityHash(
                $licenseKey,
                $firebaseLic['expires_at'],
                $firebaseLic['status'],
                $firebaseLic['school_id']
            );

            // Step 5 - Save to local SQLite
            DB::table('software_licenses')->truncate();
            DB::table('software_licenses')->insert([
                'license_key'             => encrypt($licenseKey),
                'school_id'               => $firebaseLic['school_id'],
                'firebase_refresh_token'  => encrypt($newRefreshToken),
                'status'                  => encrypt($firebaseLic['status']),
                'plan'                    => encrypt($firebaseLic['plan']),
                'expires_at'              => LicenseVerifier::normalizeExpiresAt($firebaseLic['expires_at']),
                'rsa_signature'           => $firebaseLic['rsa_signature'],
                'integrity_hash'          => $newHash,
                'offline_grace_days'      => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'created_at'              => Carbon::now(),
                'updated_at'              => Carbon::now(),
            ]);

            LicenseStatus::clearCache();

            return true;
        } catch (\Exception $e) {
            Log::error('License background sync error: ' . $e->getMessage());
            return false;
        }
    }
}
