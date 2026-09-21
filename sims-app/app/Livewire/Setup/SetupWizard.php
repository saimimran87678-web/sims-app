<?php

namespace App\Livewire\Setup;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Models\AcademicSession;
use App\Models\User;
use App\Services\FirebaseAuth;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupWizard extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    // ── Step 1: License Activation Fields ───────────────────────────
    public string $license_key = '';
    public string $license_error = '';
    public bool $license_verified = false;
    public array $license_details = [];

    // ── Step 2: Institute Profile Fields ────────────────────────────
    public string $institute_name = '';
    public string $institute_formal_name = '';
    public string $institute_short_name = '';
    public string $institute_phone = '';
    public $logo = null;

    // ── Step 3: Shift & Academic Structure Fields ───────────────────
    public string $shift_mode = 'Regular';
    public string $weekend_mode = 'sat_sun';
    public string $session_name = '';

    // ── Step 4: Super Admin Account Fields ──────────────────────────
    public string $admin_name = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';
    public string $setup_error = '';

    public function mount(): void
    {
        // Pre-fill session name with current academic year
        $currentYear = (int) date('Y');
        $this->session_name = "{$currentYear}-" . ($currentYear + 1);

        // Pre-populate license key from .env if present
        $configuredKey = config('services.license.key', '');
        if (!empty($configuredKey)) {
            $this->license_key = $configuredKey;
        }

        // Check if a valid license is already cached/present in SQLite
        $record = LicenseStatus::getLicenseRecord();
        if ($record && LicenseVerifier::verifyIntegrity($record)) {
            try {
                $status = decrypt($record->status);
                $plan = decrypt($record->plan);
                if ($status === 'active') {
                    $this->license_verified = true;
                    $this->license_details = [
                        'school_id'  => $record->school_id,
                        'plan'       => strtoupper($plan),
                        'status'     => ucfirst($status),
                        'expires_at' => $record->expires_at ? Carbon::parse($record->expires_at)->format('d M Y') : 'Never',
                    ];
                }
            } catch (\Exception $e) {
                // Ignore and require verification
            }
        }
    }

    /**
     * Step 1: Cryptographic License Verification & Activation
     */
    public function verifyLicense(): void
    {
        $this->resetErrorBag();
        $this->license_error = '';

        $this->validate([
            'license_key' => 'required|string|min:8',
        ], [
            'license_key.required' => 'Please enter the license key provided for this school.',
        ]);

        $licenseKey = trim($this->license_key);
        $apiKey    = config('services.firebase.api_key');
        $projectId = config('services.firebase.project_id');
        $rsaKey    = config('services.license.rsa_public_key');

        if (empty($apiKey) || empty($projectId)) {
            $this->license_error = 'Firebase API configuration is missing from the environment.';
            return;
        }

        if (empty($rsaKey)) {
            $this->license_error = 'License RSA public verification key is missing from the environment.';
            return;
        }

        try {
            // 1. Establish secure Firebase anonymous session
            $authRes = Http::timeout(10)->withoutVerifying()->post(
                "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$apiKey}",
                ['returnSecureToken' => true]
            );

            if (!$authRes->successful()) {
                $this->license_error = 'Could not establish connection to the license server. Please verify your internet connection.';
                return;
            }

            $refreshToken = $authRes->json('refreshToken');

            // 2. Exchange for ID token
            $tokenData = FirebaseAuth::fetchIdToken($refreshToken);
            if (!$tokenData) {
                $this->license_error = 'Could not authenticate session with license server. Please try again.';
                return;
            }

            $idToken = $tokenData['id_token'];
            $newRefreshToken = $tokenData['refresh_token'];

            // 3. Query Firestore for license record
            $firebaseLic = FirebaseAuth::queryLicenseFirestore($licenseKey, $idToken);
            if (!$firebaseLic) {
                $this->license_error = "License key \"{$licenseKey}\" was not found on the license server. Please check the key.";
                return;
            }

            // 4. Verify RSA cryptographic signature
            $isValidSig = LicenseVerifier::verifyRsaSignature(
                $licenseKey,
                $firebaseLic['expires_at'],
                $firebaseLic['status'],
                $firebaseLic['rsa_signature']
            );

            if (!$isValidSig) {
                $this->license_error = 'Cryptographic signature verification failed. The license data could not be verified.';
                return;
            }

            // 5. Compute HMAC integrity hash with local unique APP_KEY
            $allowedDomains = $firebaseLic['allowed_domain'] ?? 'localhost';
            $integrityHash = LicenseVerifier::computeIntegrityHash(
                $licenseKey,
                $firebaseLic['expires_at'],
                $firebaseLic['status'],
                $firebaseLic['school_id'],
                $allowedDomains
            );

            // 6. Save encrypted license payload to SQLite
            DB::table('software_licenses')->truncate();
            DB::table('software_licenses')->insert([
                'license_key'             => encrypt($licenseKey),
                'school_id'               => $firebaseLic['school_id'],
                'firebase_refresh_token'  => encrypt($newRefreshToken),
                'status'                  => encrypt($firebaseLic['status']),
                'plan'                    => encrypt($firebaseLic['plan']),
                'allowed_domains'         => encrypt($allowedDomains),
                'expires_at'              => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at']) : null,
                'rsa_signature'           => $firebaseLic['rsa_signature'],
                'integrity_hash'          => $integrityHash,
                'offline_grace_days'      => $firebaseLic['offline_grace'] ?? 7,
                'last_online_verified_at' => Carbon::now(),
                'created_at'              => Carbon::now(),
                'updated_at'              => Carbon::now(),
            ]);

            // 7. Update .env with validated key
            $this->updateEnvFile('LICENSE_KEY', $licenseKey);
            LicenseStatus::clearCache();

            $this->license_verified = true;
            $this->license_details = [
                'school_id'  => $firebaseLic['school_id'],
                'plan'       => strtoupper($firebaseLic['plan']),
                'status'     => ucfirst($firebaseLic['status']),
                'expires_at' => $firebaseLic['expires_at'] ? Carbon::parse($firebaseLic['expires_at'])->format('d M Y') : 'Lifetime',
            ];

            // Auto-populate institute name with school ID if empty
            if (empty($this->institute_name)) {
                $this->institute_name = $firebaseLic['school_id'];
                $this->institute_short_name = substr($firebaseLic['school_id'], 0, 10);
            }

            $this->currentStep = 2;
        } catch (\Exception $e) {
            Log::error('Setup wizard license verification error: ' . $e->getMessage());
            $this->license_error = 'Verification failed: ' . $e->getMessage();
        }
    }

    /**
     * Step 2 -> Step 3: Validate Institute Profile
     */
    public function goToStep3(): void
    {
        $this->validate([
            'institute_name'        => 'required|string|max:255',
            'institute_formal_name' => 'nullable|string|max:255',
            'institute_short_name'  => 'nullable|string|max:50',
            'institute_phone'       => 'nullable|string|max:50',
            'logo'                  => 'nullable|image|max:2048',
        ], [
            'institute_name.required' => 'Please enter the official name of the institute.',
            'logo.image'              => 'The logo must be an image file (PNG, JPG, WebP).',
            'logo.max'                => 'The logo file size must not exceed 2MB.',
        ]);

        $this->currentStep = 3;
    }

    /**
     * Step 3 -> Step 4: Validate Shift & Academic Structure
     */
    public function goToStep4(): void
    {
        $this->validate([
            'shift_mode'   => 'required|in:Regular,Dual',
            'weekend_mode' => 'required|in:sat_sun,sun_only',
            'session_name' => 'required|string|max:50',
        ]);

        $this->currentStep = 4;
    }

    /**
     * Step 4: Finalize Setup, Seed Spatie Roles, Create Super Admin, and Launch
     */
    public function finishSetup()
    {
        $this->setup_error = '';

        $this->validate([
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ], [
            'admin_name.required'     => 'Please provide the administrator full name.',
            'admin_email.required'    => 'Please enter a valid email address.',
            'admin_password.required' => 'A secure password of at least 8 characters is required.',
        ]);

        try {
            DB::beginTransaction();

            // 1. Save global institute settings
            Setting::setGlobal('institute_name', $this->institute_name);
            Setting::setGlobal('institute_formal_name', $this->institute_formal_name ?: $this->institute_name);
            Setting::setGlobal('institute_short_name', $this->institute_short_name ?: $this->institute_name);
            Setting::setGlobal('institute_phone', $this->institute_phone ?? '');
            Setting::setGlobal('default_session_shift_mode', $this->shift_mode);
            Setting::set('weekend_mode', $this->weekend_mode);

            // 2. Handle logo upload
            if ($this->logo) {
                $logoPath = $this->logo->store('branding', 'public');
                Setting::setGlobal('institute_logo', 'storage/' . $logoPath);
            }

            // 3. Create active Academic Session
            AcademicSession::create([
                'name'       => $this->session_name,
                'start_date' => now()->startOfYear(),
                'end_date'   => now()->endOfYear(),
                'is_active'  => true,
            ]);

            // 4. Seed Spatie Roles & Permissions
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);

            // 5. Ensure Super Admin role exists and grant all permissions
            $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
            $superAdminRole->givePermissionTo(Permission::all());

            // 6. Create Super Admin User
            $user = User::create([
                'name'     => $this->admin_name,
                'email'    => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'role'     => 'admin',
            ]);

            $user->assignRole($superAdminRole);

            // 7. Mark application as installed and schedule product tour
            Setting::setGlobal('app_installed', true);
            Setting::setGlobal('launch_first_tour', true);

            DB::commit();

            // 8. Auto-authenticate the Super Admin user
            Auth::login($user);

            // 9. Redirect straight to the dashboard
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SIMS Setup Wizard finalization error: ' . $e->getMessage());
            $this->setup_error = 'Failed to finalize setup: ' . $e->getMessage();
        }
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

    public function render()
    {
        return view('livewire.setup.setup-wizard')
            ->layout('layouts.guest', ['title' => 'System Setup Wizard']);
    }
}
