<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use App\Models\AcademicSession;
use App\Livewire\Setup\SetupWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninstalled_system_redirects_to_setup_wizard(): void
    {
        Setting::truncate();
        User::truncate();
        Setting::setGlobal('app_installed', false);

        $response = $this->get('/login');
        $response->assertRedirect(route('setup.wizard'));
    }

    public function test_uninstalled_system_can_access_setup_wizard(): void
    {
        Setting::truncate();
        User::truncate();
        Setting::setGlobal('app_installed', false);

        $response = $this->get('/setup');
        $response->assertStatus(200);
    }

    public function test_installed_system_redirects_away_from_setup_wizard(): void
    {
        Setting::setGlobal('app_installed', true);

        $response = $this->get('/setup');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_setup_wizard_requires_valid_license_key(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('license_key', '')
            ->call('verifyLicense')
            ->assertHasErrors(['license_key'])
            ->assertSet('currentStep', 1);
    }

    public function test_setup_wizard_full_flow_and_super_admin_creation(): void
    {
        Setting::truncate();
        User::truncate();

        // Simulate step 1 passed by unlocking step 2
        $component = Livewire::test(SetupWizard::class)
            ->set('license_verified', true)
            ->set('currentStep', 2)
            ->set('institute_name', 'Test Islamic Academy')
            ->set('institute_short_name', 'TIA')
            ->set('institute_formal_name', 'Test Islamic Academy Islamabad')
            ->set('institute_phone', '051-1234567')
            ->call('goToStep3')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 3)
            ->set('shift_mode', 'Dual')
            ->set('weekend_mode', 'sat_sun')
            ->set('session_name', '2026-2027')
            ->call('goToStep4')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 4)
            ->set('admin_name', 'Chief Administrator')
            ->set('admin_email', 'superadmin@school.test')
            ->set('admin_password', 'password123')
            ->set('admin_password_confirmation', 'password123')
            ->call('finishSetup')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        // Verify Database State
        $this->assertEquals('Test Islamic Academy', Setting::getGlobal('institute_name'));
        $this->assertEquals('Dual', Setting::getGlobal('default_session_shift_mode'));
        $this->assertTrue((bool) Setting::getGlobal('app_installed'));

        // Verify Academic Session
        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2026-2027',
            'is_active' => true,
        ]);

        // Verify Super Admin User & Role
        $admin = User::where('email', 'superadmin@school.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertAuthenticatedAs($admin);
    }
}
