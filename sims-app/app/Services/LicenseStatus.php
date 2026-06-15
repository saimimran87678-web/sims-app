<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseStatus
{
    // Constant states
    const STAGE_ACTIVE = 'ACTIVE';
    const STAGE_WARNING = 'WARNING';
    const STAGE_GRACE = 'GRACE';
    const STAGE_LOCKED = 'LOCKED';
    const STAGE_BLOCKED = 'BLOCKED';

    /**
     * Get the current license record from SQLite.
     * Programmatically runs migrations if columns are missing.
     *
     * @return object|null
     */
    public static function getLicenseRecord()
    {
        try {
            if (!Schema::hasTable('software_licenses')) {
                Artisan::call('migrate', ['--force' => true]);
            } else if (!Schema::hasColumn('software_licenses', 'plan')) {
                Artisan::call('migrate', ['--force' => true]);
            }
            return DB::table('software_licenses')->first();
        } catch (\Exception $e) {
            Log::error('License Database lookup/migration failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute the current license stage by executing security checks.
     *
     * @return array Returns ['stage' => string, 'reason' => string, 'details' => array]
     */
    public static function computeStatus(): array
    {
        $record = self::getLicenseRecord();

        // Layer 0: Check existence
        if (!$record) {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'unlicensed',
                'message' => 'No software license installed. Please contact support to activate your system.',
            ];
        }

        // Layer 1: Local Integrity Mismatch Check (database tampering)
        if (!LicenseVerifier::verifyIntegrity($record)) {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'tampered_hash',
                'message' => 'License integrity check failed (tampering detected). Please contact support.',
            ];
        }

        // Decrypt parameters
        try {
            $licenseKey = decrypt($record->license_key);
            $status = decrypt($record->status);
            $plan = decrypt($record->plan);
        } catch (\Exception $e) {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'decryption_failed',
                'message' => 'Failed to decrypt license payload. Re-installation required.',
            ];
        }

        // Layer 2: RSA Signature Validation (prevents manual SQLite field editing)
        if (!LicenseVerifier::verifyRsaSignature($licenseKey, $record->expires_at, $status, $record->rsa_signature)) {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'invalid_signature',
                'message' => 'Cryptographic license signature is invalid. Re-activation required.',
            ];
        }

        // Layer 3: System Time Travel Check (clock-tampering mitigation)
        $now = Carbon::now();
        if ($record->last_online_verified_at) {
            $lastVerified = Carbon::parse($record->last_online_verified_at);
            // Allow 1-hour tolerance for timezone / drift
            if ($now->lt($lastVerified->subHour())) {
                return [
                    'stage' => self::STAGE_LOCKED,
                    'reason' => 'clock_tampering',
                    'message' => 'System clock mismatch detected. Please set your computer clock to the correct time to continue editing.',
                ];
            }
        }

        // Layer 4: Offline Grace Period Check
        if ($record->last_online_verified_at) {
            $lastVerified = Carbon::parse($record->last_online_verified_at);
            $daysOffline = $now->diffInDays($lastVerified);
            if ($daysOffline > ($record->offline_grace_days ?? 7)) {
                return [
                    'stage' => self::STAGE_LOCKED,
                    'reason' => 'offline_limit',
                    'message' => 'Offline grace period exceeded. Please connect to the internet to verify your subscription.',
                ];
            }
        } else {
            // First run check
            return [
                'stage' => self::STAGE_LOCKED,
                'reason' => 'never_verified',
                'message' => 'Initial online verification required. Please connect to the internet to activate your software.',
            ];
        }

        // Layer 5: Expiry & Suspension Timeline Check
        if ($status === 'suspended') {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'suspended',
                'message' => 'Your subscription has been suspended by the vendor. Please contact support.',
            ];
        }

        if (!$record->expires_at) {
            return [
                'stage' => self::STAGE_BLOCKED,
                'reason' => 'no_expiry',
                'message' => 'Invalid license structure (missing expiry). Please contact support.',
            ];
        }

        $expiry = Carbon::parse($record->expires_at);

        if ($now->gt($expiry)) {
            $overdueDays = (int) abs($now->diffInDays($expiry));

            if ($overdueDays <= 3) {
                return [
                    'stage' => self::STAGE_GRACE,
                    'reason' => 'expiry_grace',
                    'days_past' => $overdueDays,
                    'message' => 'Your subscription has expired. Please renew to avoid system locking.',
                ];
            } elseif ($overdueDays <= 10) {
                return [
                    'stage' => self::STAGE_LOCKED,
                    'reason' => 'expired_locked',
                    'days_past' => $overdueDays,
                    'message' => 'Your subscription has expired. The system is now in READ-ONLY mode. Please renew.',
                ];
            } else {
                return [
                    'stage' => self::STAGE_BLOCKED,
                    'reason' => 'expired_blocked',
                    'days_past' => $overdueDays,
                    'message' => 'Your subscription expired more than 10 days ago. Access is blocked. Please renew.',
                ];
            }
        }

        // Active checks (expires_at is in the future)
        $daysRemaining = $now->diffInDays($expiry);

        if ($daysRemaining <= 3) {
            return [
                'stage' => self::STAGE_WARNING,
                'reason' => 'expiry_warning',
                'days_left' => $daysRemaining,
                'message' => "Your subscription will expire in {$daysRemaining} days. Please renew soon.",
            ];
        }

        return [
            'stage' => self::STAGE_ACTIVE,
            'reason' => 'active',
            'days_left' => $daysRemaining,
            'plan' => $plan,
            'school_name' => $record->school_id,
        ];
    }

    /**
     * Cache key for the license status.
     */
    const CACHE_KEY = 'sims_license_status';

    /**
     * How long to cache the license status (seconds).
     * Keep short so activation is reflected quickly.
     */
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Get the current cached license status (fast page loads).
     * Uses the application cache (shared across tabs/requests).
     *
     * @param bool $forceRefresh Force a fresh DB + cryptographic check.
     * @return array
     */
    public static function getStatus(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::computeStatus();
        });
    }

    /**
     * Flush the cached license status so the next request re-checks the DB.
     * Call this after license:activate or any admin action.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Helper to check if database writes are allowed in the current stage.
     *
     * @return bool
     */
    public static function canWrite(): bool
    {
        $status = self::getStatus();
        $stage = $status['stage'] ?? self::STAGE_BLOCKED;

        return in_array($stage, [self::STAGE_ACTIVE, self::STAGE_WARNING, self::STAGE_GRACE]);
    }
}
