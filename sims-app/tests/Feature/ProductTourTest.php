<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::setGlobal('app_installed', true);
    }

    public function test_admin_dashboard_renders_all_tour_target_elements_and_scripts(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');

        Setting::setGlobal('launch_first_tour', true);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="sidebar-branding"', false);
        $response->assertSee('id="session-shift-selector"', false);
        $response->assertSee('id="nav-students"', false);
        $response->assertSee('id="nav-schedule"', false);
        $response->assertSee('id="nav-substitutions"', false);
        $response->assertSee('id="nav-settings"', false);
        $response->assertSee('driver.js.iife.js');
        $response->assertSee('driver-theme.css');
        $response->assertSee('tour.js');
        $response->assertSee('window.SIMS_LAUNCH_TOUR = true;', false);
    }

    public function test_tour_complete_endpoint_marks_launch_first_tour_as_false(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');

        Setting::setGlobal('launch_first_tour', true);
        $this->assertTrue((bool) Setting::getGlobal('launch_first_tour'));

        $response = $this->actingAs($admin)->postJson('/admin/tour/complete');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertFalse((bool) Setting::getGlobal('launch_first_tour'));
    }

    public function test_unauthenticated_request_to_tour_complete_is_forbidden(): void
    {
        $response = $this->postJson('/admin/tour/complete');
        $response->assertStatus(401);
    }
}
