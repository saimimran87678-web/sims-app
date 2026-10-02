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

            // Step 1 - Exchange for ID token if refresh token is available
            $idToken = null;
            $newRefreshToken = $refreshToken;
            if (!empty($refreshToken)) {
                $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
                if ($tokenData) {
                    $idToken = $tokenData['id_token'];
                    $newRefreshToken = $tokenData['refresh_token'];
                }
            }

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

            // Step 4 - Hardware UUID Verification & Binding
            $localUuid = HardwareIdentifier::getMachineUuid();
            $hostname  = HardwareIdentifier::getHostname();
            $netTelemetry = HardwareIdentifier::getNetworkTelemetry();

            $remoteUuid = $firebaseLic['bound_machine_uuid'] ?? null;
            if (!empty($remoteUuid) && $remoteUuid !== $localUuid) {
                // Hardware Mismatch! License is locked to a different computer.
                Log::warning("License hardware mismatch! Bound: [{$remoteUuid}], Current PC: [{$localUuid}]");
                DB::table('software_licenses')->update([
                    'status'                  => encrypt('hardware_mismatch'),
                    'last_online_verified_at' => Carbon::now(),
                    'updated_at'              => Carbon::now(),
                ]);
                LicenseStatus::clearCache();
                return false;
            }

            // Step 5 - Send Telemetry Heartbeat to Firestore
            FirebaseAuth::sendTelemetryHeartbeat($licenseKey, array_merge($netTelemetry, [
                'bound_machine_uuid' => $localUuid,
                'hostname'           => $hostname,
                'last_active_at'     => Carbon::now()->toIso8601String(),
                'installed_version'  => '2.5.2',
            ]), $idToken);

            // Step 6 - Compute new integrity hash
            $allowedDomains = $firebaseLic['allowed_domain'] ?? '';
            $newHash = LicenseVerifier::computeIntegrityHash(
                $licenseKey,
                $firebaseLic['expires_at'],
                $firebaseLic['status'],
                $firebaseLic['school_id'],
                $allowedDomains
            );

            // Step 7 - Save to local SQLite
            DB::table('software_licenses')->truncate();
            DB::table('software_licenses')->insert([
                'license_key'             => encrypt($licenseKey),
                'school_id'               => $firebaseLic['school_id'],
                'bound_machine_uuid'      => $localUuid,
                'firebase_refresh_token'  => encrypt($newRefreshToken ?: 'direct_public_session'),
                'status'                  => encrypt($firebaseLic['status']),
                'plan'                    => encrypt($firebaseLic['plan']),
                'allowed_domains'         => encrypt($allowedDomains),
                'expires_at'              => LicenseVerifier::normalizeExpiresAt($firebaseLic['expires_at']),
                'rsa_signature'           => $firebaseLic['rsa_signature'],
                'integrity_hash'          => $newHash,
                'offline_grace_days'      => $firebaseLic['offline_grace'] ?? 7,
                'enabled_modules'         => json_encode($firebaseLic['enabled_modules'] ?? ['fees', 'exams', 'attendance', 'whatsapp', 'reports']),
                'broadcast_announcement'  => $firebaseLic['broadcast_announcement'] ?? null,
                'config_version'          => $firebaseLic['config_version'] ?? 1,
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
