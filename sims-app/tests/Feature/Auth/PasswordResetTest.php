<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('password.otp.verify'));
        $response->assertSessionHas('reset_email', $user->email);



        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_double_submission_within_grace_period_smoothly_redirects_to_otp_verify(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        // First click
        $first = $this->post('/forgot-password', ['email' => $user->email]);
        $first->assertRedirect(route('password.otp.verify'));

        // Rapid double click (1 second later during loading)
        $second = $this->post('/forgot-password', ['email' => $user->email]);
        $second->assertRedirect(route('password.otp.verify'));
        $second->assertSessionHas('status', 'OTP code has been sent to your email address.');
    }

    public function test_cooldown_throttles_deliberate_re_request_with_remaining_seconds(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        // Create token sent 20 seconds ago
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => '999888',
            'created_at' => now()->subSeconds(20),
        ]);

        $response = $this->post('/forgot-password', ['email' => $user->email]);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Please wait', session('error'));
        $this->assertGreaterThanOrEqual(38, session('secondsRemaining'));
        $this->assertLessThanOrEqual(40, session('secondsRemaining'));
    }

    public function test_verify_otp_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['reset_email' => $user->email])
                         ->get('/verify-otp');

        $response->assertStatus(200);
    }

    public function test_otp_can_be_verified(): void
    {
        $user = User::factory()->create();
        $otp = '123456';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $otp,
            'created_at' => now(),
        ]);

        $response = $this->withSession(['reset_email' => $user->email])
                         ->post('/verify-otp', ['otp' => $otp]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('otp_verified', true);
    }

    public function test_password_can_be_reset(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession([
            'reset_email' => $user->email,
            'otp_verified' => true,
        ])->post('/reset-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password', $user->fresh()->password));
    }
}
