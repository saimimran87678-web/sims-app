<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed license status
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());
    }

    public function test_disabling_admin_security_requires_verification()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        Setting::set('admin_action_pin_enabled', true);
        Setting::set('admin_action_pin', '1234');

        $this->actingAs($admin);

        // Turn toggle off, verify modal opens and value is retained as true
        Livewire::test(\App\Livewire\Admin\Settings::class)
            ->assertSet('admin_action_pin_enabled', true)
            ->set('admin_action_pin_enabled', false)
            ->assertSet('admin_action_pin_enabled', true)
            ->assertSet('isSecurityVerificationModalOpen', true);
    }

    public function test_disabling_admin_security_with_password()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        Setting::set('admin_action_pin_enabled', true);
        Setting::set('admin_action_pin', '1234');

        $this->actingAs($admin);

        // Turn toggle off, verify with password
        $comp = Livewire::test(\App\Livewire\Admin\Settings::class)
            ->set('admin_action_pin_enabled', false)
            ->set('verificationMethod', 'password')
            ->set('verificationInput', 'wrong_password')
            ->call('verifySecurityAction')
            ->assertSet('admin_action_pin_enabled', true)
            ->assertSet('isSecurityVerificationModalOpen', true)
            ->assertSet('verificationError', 'Incorrect password.')
            ->set('verificationInput', 'password123')
            ->call('verifySecurityAction')
            ->assertSet('admin_action_pin_enabled', false)
            ->assertSet('isSecurityVerificationModalOpen', false);

        $this->assertFalse((bool) Setting::get('admin_action_pin_enabled', false));
    }

    public function test_disabling_admin_security_with_otp()
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);

        Setting::set('admin_action_pin_enabled', true);
        Setting::set('admin_action_pin', '1234');

        $this->actingAs($admin);

        // Turn toggle off, send OTP, verify with correct OTP
        $component = Livewire::test(\App\Livewire\Admin\Settings::class)
            ->set('admin_action_pin_enabled', false)
            ->set('verificationMethod', 'otp')
            ->call('sendVerificationOtp')
            ->assertSet('otpSent', true);

        $otp = session('admin_security_otp');
        $this->assertNotNull($otp);

        $component->set('verificationInput', '111111') // incorrect otp
            ->call('verifySecurityAction')
            ->assertSet('admin_action_pin_enabled', true)
            ->assertSet('verificationError', 'Incorrect OTP code.')
            ->set('verificationInput', $otp) // correct otp
            ->call('verifySecurityAction')
            ->assertSet('admin_action_pin_enabled', false)
            ->assertSet('isSecurityVerificationModalOpen', false);

        $this->assertFalse((bool) Setting::get('admin_action_pin_enabled', false));
    }

    public function test_save_default_session_shift_mode()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\Settings::class)
            ->assertSet('default_session_shift_mode', 'Regular')
            ->set('default_session_shift_mode', 'Dual')
            ->set('weekend_mode', 'sat_sun')
            ->set('institute_name', 'Test Institute')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Dual', Setting::getGlobal('default_session_shift_mode'));
    }

    public function test_remove_logo()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Setting::setGlobal('institute_logo', 'storage/branding/test_logo.png');

        Livewire::test(\App\Livewire\Admin\Settings::class)
            ->assertSet('institute_logo', 'storage/branding/test_logo.png')
            ->call('removeLogo')
            ->assertSet('institute_logo', '')
            ->assertSet('logo', null);

        $this->assertEquals('', Setting::getGlobal('institute_logo'));
    }

    public function test_settings_mount_syncs_installed_version_and_checks_update_safely()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        config(['app.version' => '2.5.2']);
        Setting::setGlobal('installed_version', '2.5.2');
        Setting::setGlobal('last_update_checksum', 'test_checksum_123');

        $manifestFile = storage_path('framework/testing/test_manifest.json');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($manifestFile));
        \Illuminate\Support\Facades\File::put($manifestFile, json_encode([
            'version' => '2.5.2',
            'checksum' => 'test_checksum_123',
            'download_url' => 'https://example.com/test.zip',
        ]));
        config(['app.update_manifest_url' => 'file://' . $manifestFile]);

        Livewire::test(\App\Livewire\Admin\Settings::class)
            ->assertSet('currentVersion', '2.5.2')
            ->call('checkForUpdates')
            ->assertSet('updateAvailable', false)
            ->assertSet('updateCheckMessage', 'Your system is up to date (v2.5.2).');

        if (file_exists($manifestFile)) {
            @unlink($manifestFile);
        }
    }

    public function test_settings_apply_manual_patch_handles_uploaded_file()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $tempZip = storage_path('framework/testing/patch_test.zip');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($tempZip));
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode(['version' => '2.5.3', 'min_php_version' => '8.2.0']));
        $zip->addFromString('test_patch_file.txt', 'patch content');
        $zip->close();

        $uploaded = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'patch_test.zip',
            file_get_contents($tempZip)
        );

        Livewire::test(\App\Livewire\Admin\Settings::class)
            ->set('patchArchive', $uploaded)
            ->call('applyManualPatch')
            ->assertSet('updateAvailable', false)
            ->assertSet('currentVersion', '2.5.3')
            ->assertSet('manualPatchSuccess', 'Patch package applied successfully! System has been updated cleanly.');

        $this->assertEquals('2.5.3', Setting::getGlobal('installed_version'));

        if (file_exists($tempZip)) {
            @unlink($tempZip);
        }
    }
}
