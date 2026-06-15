<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use App\Services\FirebaseAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseBanner extends Component
{
    public $status = [];
    public $dismissed = false;

    public function mount()
    {
        $this->status = LicenseStatus::getStatus();
        $this->dismissed = session('sims_license_banner_dismissed', false);
    }

    /**
     * Run silent background verification check from the browser.
     * Triggered by wire:init.
     */
    public function verifyLicenseBackground()
    {
        $record = LicenseStatus::getLicenseRecord();
        if (!$record) {
            return;
        }

        // Only query Firebase if last check was more than 1 hour ago
        $lastChecked = $record->last_online_verified_at ? Carbon::parse($record->last_online_verified_at) : null;
        if ($lastChecked && Carbon::now()->diffInMinutes($lastChecked) < 60) {
            // Already checked recently
            return;
        }

        try {
            $refreshToken = decrypt($record->firebase_refresh_token);
            $licenseKey = decrypt($record->license_key);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt license parameters in background verification: ' . $e->getMessage());
            return;
        }

        // 1. Fetch Firebase ID Token
        $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
        if (!$tokenData) {
            Log::warning('Firebase silent verification offline: Token exchange failed.');
            return;
        }

        $idToken = $tokenData['id_token'];
        $newRefreshToken = $tokenData['refresh_token'];

        // 2. Fetch Subscription Data from Firestore
        $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
        if (!$firebaseLic) {
            Log::warning('Firebase silent verification offline: Firestore query failed.');
            return;
        }

        // 3. Cryptographic Signature Validation
        $isValidSig = LicenseVerifier::verifyRsaSignature(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $firebaseLic['rsa_signature']
        );

        if (!$isValidSig) {
            Log::error('Firebase verification aborted: Received invalid cryptographic signature from server.');
            return;
        }

        // 4. Compute New Local Database Integrity Hash
        $newHash = LicenseVerifier::computeIntegrityHash(
            $licenseKey,
            $firebaseLic['expires_at'],
            $firebaseLic['status'],
            $record->school_id
        );

        // 5. Update SQLite Record with Fresh Subscription Data
        DB::table('software_licenses')
            ->where('id', $record->id)
            ->update([
                'status' => encrypt($firebaseLic['status']),
                'plan' => encrypt($firebaseLic['plan']),
                'expires_at' => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at']) : null,
                'rsa_signature' => $firebaseLic['rsa_signature'],
                'integrity_hash' => $newHash,
                'firebase_refresh_token' => encrypt($newRefreshToken),
                'offline_grace_days' => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        // 6. Force-refresh status session cache
        $this->status = LicenseStatus::getStatus(true);

        // 7. If status is now BLOCKED, redirect user to lock screen
        if ($this->status['stage'] === LicenseStatus::STAGE_BLOCKED) {
            return redirect()->route('license.blocked');
        }

        // Refresh views
        $this->dispatch('licenseUpdated');
    }

    /**
     * Dismiss warning banner for the current session.
     */
    public function dismiss()
    {
        session(['sims_license_banner_dismissed' => true]);
        $this->dismissed = true;
    }

    public function render()
    {
        return view('livewire.license-banner');
    }
}
