<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionLockSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@sims.test',
            'password' => Hash::make('CorrectPassword123!'),
        ]);
        $this->admin->assignRole('Super Admin');

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());
    }

    public function test_locked_session_blocks_page_refresh_and_redirects_to_lock_screen(): void
    {
        $this->actingAs($this->admin);

        // Mark session as locked
        session(['session_locked' => true]);

        // Simulating F5 page refresh on admin dashboard
        $response = $this->get(route('admin.dashboard'));

        // Server MUST block and redirect to dedicated lock screen
        $response->assertRedirect(route('session.lock_screen'));

        // Visit lock screen - must render cleanly with user info
        $lockScreenResponse = $this->get(route('session.lock_screen'));
        $lockScreenResponse->assertStatus(200);
        $lockScreenResponse->assertSee('Session Locked');
        $lockScreenResponse->assertSee('admin@sims.test', false);
        $lockScreenResponse->assertSee('Unlock Session');
    }

    public function test_inactivity_timeout_automatically_locks_session_on_server(): void
    {
        $this->actingAs($this->admin);

        // Configure 15 minutes idle timeout
        Setting::setGlobal('session_idle_timeout', 15);

        // Simulate 16 minutes of idle time on server
        session(['last_activity_time' => now()->subMinutes(16)->timestamp]);

        // User attempts any request
        $response = $this->get(route('admin.dashboard'));

        // Middleware must automatically detect timeout, lock session, and redirect
        $response->assertRedirect(route('session.lock_screen'));
        $this->assertTrue(session('session_locked'));
    }

    public function test_ajax_or_livewire_request_receives_423_locked_when_session_is_locked(): void
    {
        $this->actingAs($this->admin);

        session(['session_locked' => true]);

        // Request with X-Livewire header
        $response = $this->get(route('admin.dashboard'), [
            'X-Livewire' => 'true',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(423);
        $response->assertJson([
            'locked' => true,
        ]);
    }

    public function test_unlocking_session_with_correct_password_restores_access(): void
    {
        $this->actingAs($this->admin);

        session(['session_locked' => true]);

        // 1. Attempt unlock with wrong password
        $failResponse = $this->postJson(route('session.unlock'), [
            'email' => $this->admin->email,
            'password' => 'WrongPassword!',
        ]);

        $failResponse->assertStatus(422);
        $failResponse->assertJson([
            'success' => false,
        ]);
        $this->assertTrue(session('session_locked'));

        // 2. Unlock with correct password
        $successResponse = $this->postJson(route('session.unlock'), [
            'email' => $this->admin->email,
            'password' => 'CorrectPassword123!',
        ]);

        $successResponse->assertStatus(200);
        $successResponse->assertJson([
            'success' => true,
        ]);

        // Verify session flags are cleared
        $this->assertFalse((bool) session('session_locked', false));
        $this->assertNotNull(session('last_activity_time'));

        // Subsequent web request must now succeed without redirection to lock screen
        $dashResponse = $this->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
    }

    public function test_disabled_timeout_policy_does_not_lock_session(): void
    {
        $this->actingAs($this->admin);

        // Configure timeout as 0 (disabled)
        Setting::setGlobal('session_idle_timeout', 0);

        // Simulate 2 hours of idle time
        session(['last_activity_time' => now()->subHours(2)->timestamp]);

        // Request must NOT be locked
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $this->assertFalse((bool) session('session_locked', false));
    }
}
