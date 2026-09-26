<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;

class Settings extends Component
{
    use WithFileUploads;

    public $institute_name;
    public $institute_formal_name;
    public $institute_short_name;
    public $institute_phone;
    public $institute_logo;
    public $logo; // Temporary uploaded logo file
    public $weekend_mode;
    public $default_session_shift_mode;
    public $admin_action_pin_enabled = false;
    public $admin_action_pin = '';
    public $successMessage = '';

    // System Update Properties
    public $currentVersion = '2.5.0';
    public $lastUpdateChecksum = 'Initial Installation';
    public $lastUpdatedAt = 'Initial Installation';
    public $updateAvailable = false;
    public $latestVersion = '';
    public $releaseNotes = '';
    public $updateCheckMessage = '';
    public $updateSuccessMessage = '';
    public $updateErrorMessage = '';

    // Manual Patch Upload Properties
    public $patchArchive;
    public $manualPatchSuccess = '';
    public $manualPatchError = '';

    // Security Verification Modal Fields
    public $isSecurityVerificationModalOpen = false;
    public $verificationMethod = 'password'; // 'password' or 'otp'
    public $verificationInput = '';
    public $otpSent = false;
    public $verificationOtp = '';
    public $verificationError = '';

    protected function rules()
    {
        return [
            'institute_name' => 'required|string|max:255',
            'institute_formal_name' => 'nullable|string|max:255',
            'institute_short_name' => 'nullable|string|max:50',
            'institute_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:1024', // Max 1MB logo image
            'weekend_mode'   => 'required|in:sun_only,sat_sun',
            'default_session_shift_mode' => 'required|in:Regular,Dual',
            'admin_action_pin_enabled' => 'boolean',
            'admin_action_pin' => $this->admin_action_pin_enabled ? 'required|string|min:4|max:6' : 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->institute_name = Setting::getGlobal('institute_name', 'IMCB G-6/2');
        $this->institute_formal_name = Setting::getGlobal('institute_formal_name', 'Islamabad Model College for Boys (VI-X), G-6/2 Islamabad');
        $this->institute_short_name = Setting::getGlobal('institute_short_name', 'IMCB');
        $this->institute_phone = Setting::getGlobal('institute_phone', '');
        $this->institute_logo = Setting::getGlobal('institute_logo', '');
        $this->weekend_mode   = Setting::get('weekend_mode', 'sat_sun');
        $this->default_session_shift_mode = Setting::getGlobal('default_session_shift_mode', 'Regular');
        $this->admin_action_pin_enabled = (bool) Setting::get('admin_action_pin_enabled', false);
        $this->admin_action_pin = Setting::get('admin_action_pin', '');

        // Initialize Version & Integrity status
        $this->currentVersion = config('app.version', '2.5.0');
        $this->lastUpdateChecksum = Setting::getGlobal('last_update_checksum', 'None (Initial Installation)');
        $this->lastUpdatedAt = Setting::getGlobal('last_updated_at', 'Initial Installation');
    }

    public function updatedAdminActionPinEnabled($value)
    {
        if ($value === false) {
            $currentlyEnabled = (bool) Setting::get('admin_action_pin_enabled', false);
            if ($currentlyEnabled) {
                // Instantly force it back to true in component state so the UI toggle doesn't turn off until verified
                $this->admin_action_pin_enabled = true;

                // Open the security confirmation modal
                $this->isSecurityVerificationModalOpen = true;
                $this->verificationMethod = 'password';
                $this->verificationInput = '';
                $this->otpSent = false;
                $this->verificationError = '';
            }
        }
    }

    public function sendVerificationOtp()
    {
        $user = auth()->user();
        if (!$user) return;

        // Generate 6-digit OTP
        $this->verificationOtp = (string) rand(100000, 999999);
        $this->otpSent = true;
        $this->verificationError = '';

        // Store OTP in session with timestamp
        session([
            'admin_security_otp' => $this->verificationOtp,
            'admin_security_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            $instituteName = Setting::get('institute_name', 'IMCB G-6/2');
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $instituteName) {
                $message->to($user->email)
                    ->subject('Security Toggle Disable Code - ' . $instituteName)
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                            <div style='text-align: center; margin-bottom: 24px;'>
                                <h2 style='color: #1e3a5f; margin: 0; font-size: 24px; font-weight: 800;'>{$instituteName}</h2>
                                <p style='color: #64748b; margin: 4px 0 0 0; font-size: 13px;'>Security Action Verification</p>
                            </div>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 24px;'>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>Hello {$user->name},</p>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>You requested to disable <strong>Require PIN for Admin Modifications</strong>. Use the following 6-digit OTP code to verify your identity and complete this action:</p>
                            <div style='text-align: center; margin: 36px 0;'>
                                <span style='font-size: 36px; font-weight: 800; letter-spacing: 6px; color: #b91c1c; padding: 12px 24px; background-color: #fef2f2; border-radius: 8px; border: 1px solid #fee2e2; display: inline-block;'>{$this->verificationOtp}</span>
                            </div>
                            <p style='color: #ef4444; font-size: 13px; line-height: 1.5; margin-bottom: 0;'><strong>Note:</strong> This verification code is valid for 10 minutes. If you did not make this request, please change your password immediately.</p>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 24px; margin-bottom: 24px;'>
                            <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0;'>© " . date('Y') . " Adminova. All rights reserved.</p>
                        </div>
                    ");
            });
            session()->flash('otp_status', 'Verification OTP has been sent to your email.');
        } catch (\Exception $e) {
            $this->verificationError = 'Failed to send OTP: ' . $e->getMessage();
            $this->otpSent = false;
        }
    }

    public function verifySecurityAction()
    {
        $this->verificationError = '';

        if ($this->verificationMethod === 'password') {
            if (empty($this->verificationInput)) {
                $this->verificationError = 'Password is required.';
                return;
            }

            if (!\Illuminate\Support\Facades\Hash::check($this->verificationInput, auth()->user()->password)) {
                $this->verificationError = 'Incorrect password.';
                return;
            }
        } else {
            // OTP verification
            if (empty($this->verificationInput)) {
                $this->verificationError = 'OTP code is required.';
                return;
            }

            $sessionOtp = session('admin_security_otp');
            $expiresAt = session('admin_security_otp_expires_at');

            if (!$sessionOtp || !$expiresAt || now()->greaterThan($expiresAt)) {
                $this->verificationError = 'OTP code has expired or is invalid. Please request a new one.';
                return;
            }

            if ($this->verificationInput !== $sessionOtp) {
                $this->verificationError = 'Incorrect OTP code.';
                return;
            }

            // Clear session OTP
            session()->forget(['admin_security_otp', 'admin_security_otp_expires_at']);
        }

        // Verification successful! Disable the toggle and save it immediately to database.
        $this->admin_action_pin_enabled = false;
        Setting::set('admin_action_pin_enabled', false);
        Setting::set('admin_action_pin', '');
        
        $this->isSecurityVerificationModalOpen = false;
        $this->verificationInput = '';
        $this->otpSent = false;
        
        session()->flash('status', 'Admin Action Security disabled successfully.');
    }

    public function closeSecurityVerificationModal()
    {
        $this->isSecurityVerificationModalOpen = false;
        $this->verificationInput = '';
        $this->otpSent = false;
        $this->verificationError = '';
        // Restore correct toggle status from database
        $this->admin_action_pin_enabled = (bool) Setting::get('admin_action_pin_enabled', false);
    }

    public function save()
    {
        $this->validate();

        if ($this->logo) {
            // Store file under public/branding directory
            $fileName = 'logo_' . time() . '.' . $this->logo->getClientOriginalExtension();
            $path = $this->logo->storeAs('branding', $fileName, 'public');
            $logoUrl = 'storage/' . $path;

            // Optional: delete old logo if exists
            if ($this->institute_logo && file_exists(public_path($this->institute_logo))) {
                @unlink(public_path($this->institute_logo));
            }

            Setting::setGlobal('institute_logo', $logoUrl);
            $this->institute_logo = $logoUrl;
            $this->logo = null;
        }

        Setting::setGlobal('institute_name', $this->institute_name);
        Setting::setGlobal('institute_formal_name', $this->institute_formal_name ?? '');
        Setting::setGlobal('institute_short_name', $this->institute_short_name ?? '');
        Setting::setGlobal('institute_phone', $this->institute_phone ?? '');
        Setting::set('weekend_mode',   $this->weekend_mode);
        Setting::setGlobal('default_session_shift_mode', $this->default_session_shift_mode);
        Setting::set('admin_action_pin_enabled', $this->admin_action_pin_enabled);
        Setting::set('admin_action_pin', $this->admin_action_pin_enabled ? $this->admin_action_pin : '');

        session()->flash('status', 'Settings updated successfully!');
    }

    public function removeLogo()
    {
        if ($this->institute_logo) {
            if (file_exists(public_path($this->institute_logo))) {
                @unlink(public_path($this->institute_logo));
            }
            Setting::setGlobal('institute_logo', '');
            $this->institute_logo = '';
        }
        $this->logo = null;
        session()->flash('status', 'Logo removed successfully.');
    }

    public function checkForUpdates()
    {
        $this->updateCheckMessage = '';
        $this->updateSuccessMessage = '';
        $this->updateErrorMessage = '';

        try {
            $manifestSource = trim(config('app.update_manifest_url', \App\Console\Commands\SimsUpdate::DEFAULT_MANIFEST_URL), " '\"");
            $manifest = null;

            if (str_starts_with($manifestSource, 'http://') || str_starts_with($manifestSource, 'https://')) {
                $cacheBust = '?t=' . time() . '_' . mt_rand(100, 999);
                $cleanSource = strtok($manifestSource, '?');

                $candidates = [
                    'https://cdn.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust,
                    'https://fastly.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust,
                    'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json' . $cacheBust,
                ];

                if (!str_contains($cleanSource, 'manifest.json')) {
                    array_unshift($candidates, $cleanSource . $cacheBust);
                }

                foreach ($candidates as $url) {
                    try {
                        $response = \Illuminate\Support\Facades\Http::timeout(3)
                            ->withoutVerifying()
                            ->withHeaders([
                                'User-Agent' => 'SIMS-Settings-UI/' . config('app.version', '2.5.2'),
                                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                                'Pragma' => 'no-cache',
                            ])
                            ->get($url);

                        if ($response && $response->successful()) {
                            $json = $response->json();
                            if (is_array($json) && !empty($json['version'])) {
                                $manifest = $json;
                                break;
                            }
                        }
                    } catch (\Throwable) {}
                }

                // Native Windows fallback if PHP cURL is blocked or has network issues
                if (!$manifest && PHP_OS_FAMILY === 'Windows') {
                    $cdnUrl = 'https://cdn.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust;
                    $rawUrl = 'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json' . $cacheBust;
                    $psScript = "\$urls = @('{$cdnUrl}', '{$rawUrl}'); foreach (\$u in \$urls) { try { (Invoke-RestMethod -Uri \$u -TimeoutSec 4 -Headers @{'User-Agent'='SIMS-Updater'}) | ConvertTo-Json -Compress; break } catch {} }";
                    $encoded = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
                    $psOut = shell_exec("powershell.exe -NoProfile -ExecutionPolicy Bypass -EncodedCommand {$encoded}");
                    if (!empty($psOut)) {
                        $json = json_decode(trim($psOut), true);
                        if (is_array($json) && !empty($json['version'])) {
                            $manifest = $json;
                        }
                    }
                }
            } else {
                $filePath = str_starts_with($manifestSource, 'file://') ? substr($manifestSource, 7) : $manifestSource;
                if (!file_exists($filePath)) {
                    $filePath = base_path($manifestSource);
                }
                if (file_exists($filePath)) {
                    $raw = file_get_contents($filePath);
                    $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
                    $manifest = json_decode(trim($clean), true);
                }
            }

            if (!$manifest) {
                $this->updateCheckMessage = 'Unable to reach the update server. Please verify your internet connection.';
                return;
            }

            $latest = trim($manifest['version'] ?? '');
            $this->latestVersion = $latest;
            $this->releaseNotes = $manifest['changelog'] ?? 'Maintenance updates and bug fixes.';

            $installedChecksum = Setting::getGlobal('last_update_checksum', '');
            $manifestHash = strtolower(trim($manifest['checksum'] ?? $manifest['sha256'] ?? ''));
            $isSameVersion = version_compare($latest, $this->currentVersion, '==');
            $isChecksumDiff = (!empty($manifestHash) && $manifestHash !== $installedChecksum);

            if (!empty($latest) && (version_compare($latest, $this->currentVersion, '>') || ($isSameVersion && $isChecksumDiff))) {
                $this->updateAvailable = true;
                $this->updateCheckMessage = ($isChecksumDiff && !version_compare($latest, $this->currentVersion, '>'))
                    ? "A hotfix patch for v{$latest} is available to install!"
                    : "A newer version (v{$latest}) is available to install!";
            } else {
                $this->updateAvailable = false;
                $this->updateCheckMessage = "Your system is up to date (v{$this->currentVersion}).";
            }
        } catch (\Throwable $e) {
            $this->updateCheckMessage = 'Update check failed: ' . $e->getMessage();
        }
    }

    public function applyUpdate()
    {
        $this->updateSuccessMessage = '';
        $this->updateErrorMessage = '';

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('sims:update');
            $output = \Illuminate\Support\Facades\Artisan::output();

            if ($exitCode === 0) {
                $this->updateSuccessMessage = 'System successfully updated! All services and database migrations are synchronized.';
                $this->updateAvailable = false;
                $this->currentVersion = config('app.version', '2.5.0');
                $this->lastUpdateChecksum = Setting::getGlobal('last_update_checksum', 'None');
                $this->lastUpdatedAt = Setting::getGlobal('last_updated_at', now()->toIso8601String());
            } else {
                $this->updateErrorMessage = 'Update could not be completed cleanly. The automatic rollback safeguard restored your previous version and database safely.';
            }
        } catch (\Throwable $e) {
            $this->updateErrorMessage = 'Update execution error: ' . $e->getMessage();
        }
    }

    public function applyManualPatch()
    {
        $this->manualPatchSuccess = '';
        $this->manualPatchError = '';

        $this->validate([
            'patchArchive' => 'required|file|max:102400', // max 100MB
        ], [
            'patchArchive.required' => 'Please select a patch .zip archive to upload.',
            'patchArchive.max' => 'Patch archive size must be under 100MB.',
        ]);

        $clientName = $this->patchArchive->getClientOriginalName();
        if (!str_ends_with(strtolower($clientName), '.zip')) {
            $this->manualPatchError = 'Invalid file type. Only .zip patch archives are supported.';
            return;
        }

        try {
            $updateDir = storage_path('updates');
            @mkdir($updateDir, 0755, true);
            $filename = 'manual-patch-' . time() . '.zip';
            $savedRelative = $this->patchArchive->storeAs('updates', $filename);

            $storedPath = storage_path('app/' . $savedRelative);
            if (!file_exists($storedPath)) {
                $storedPath = storage_path('updates/' . $filename);
            }

            $exitCode = \Illuminate\Support\Facades\Artisan::call('sims:update', [
                '--package' => $storedPath,
                '--force'   => true,
            ]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            if ($exitCode === 0) {
                $this->manualPatchSuccess = 'Patch package applied successfully! System has been updated cleanly.';
                $this->currentVersion = config('app.version', '2.5.2');
                $this->lastUpdateChecksum = Setting::getGlobal('last_update_checksum', 'Manual Patch');
                $this->lastUpdatedAt = Setting::getGlobal('last_updated_at', now()->toIso8601String());
                $this->patchArchive = null;
            } else {
                $this->manualPatchError = 'Update failed. The system rolled back safely to prevent corruption. Details: ' . trim($output);
            }
        } catch (\Throwable $e) {
            $this->manualPatchError = 'Error applying manual patch: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.settings')
            ->layout('components.layouts.admin', ['title' => 'System Settings']);
    }
}
