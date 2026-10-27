<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Services\SystemNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationUiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::clearRuntimeCache();
        Setting::setGlobal('app_installed', true);
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);
        $this->teacher->assignRole('Teacher');
    }

    public function test_system_notification_service_returns_structured_notifications_for_admin(): void
    {
        // 1. Simulate update available
        Setting::setGlobal('installed_version', '2.5.0');
        Setting::setGlobal('update_available', json_encode([
            'version' => '2.5.5',
            'mandatory' => true,
            'changelog' => 'High priority security patch and UI performance upgrade.'
        ]));

        $this->actingAs($this->admin);
        $notifications = SystemNotificationService::getActiveNotifications();

        $this->assertIsArray($notifications);
        $this->assertNotEmpty($notifications);

        $updateNotice = collect($notifications)->firstWhere('type', 'update');
        $this->assertNotNull($updateNotice);
        $this->assertEquals('2.5.5', $updateNotice['version']);
        $this->assertArrayHasKey('id', $updateNotice);
        $this->assertArrayHasKey('title', $updateNotice);
        $this->assertArrayHasKey('message', $updateNotice);
        $this->assertArrayHasKey('badge', $updateNotice);
        $this->assertArrayHasKey('timestamp', $updateNotice);
    }

    public function test_teachers_do_not_see_update_or_license_notifications(): void
    {
        // Setup pending update and expiring license
        Setting::setGlobal('installed_version', '2.5.0');
        Setting::setGlobal('update_available', json_encode([
            'version' => '2.5.5',
            'mandatory' => true,
            'changelog' => 'High priority security patch.'
        ]));
        
        \Illuminate\Support\Facades\Cache::put(
            \App\Services\LicenseStatus::CACHE_KEY,
            [
                'stage' => \App\Services\LicenseStatus::STAGE_WARNING,
                'days_left' => 2,
                'message' => 'Your subscription will expire in 2 days. Please renew soon.'
            ],
            300
        );

        // 1. Test as Teacher
        $this->actingAs($this->teacher);
        $teacherNotifications = SystemNotificationService::getActiveNotifications();

        // Teacher must NOT have update or license notifications
        $this->assertNull(collect($teacherNotifications)->firstWhere('type', 'update'));
        $this->assertNull(collect($teacherNotifications)->firstWhere('type', 'license'));

        // Teacher dashboard request check
        $teacherResponse = $this->get('/teacher/dashboard');
        $teacherResponse->assertStatus(200);
        $teacherResponse->assertDontSee('System Update (v2.5.5)');
        $teacherResponse->assertDontSee('License Subscription Notice');
        $teacherResponse->assertDontSee('Release Manager');
        $teacherResponse->assertSee('Notification Center');

        // 2. Test as Admin
        $this->actingAs($this->admin);
        $adminNotifications = SystemNotificationService::getActiveNotifications();

        // Admin must have update and license notifications
        $this->assertNotNull(collect($adminNotifications)->firstWhere('type', 'update'));
        $this->assertNotNull(collect($adminNotifications)->firstWhere('type', 'license'));

        $adminResponse = $this->get('/admin/dashboard');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Release Manager');
    }

    public function test_notification_bell_renders_in_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Notifications');
        $response->assertSee('activeTab');
        $response->assertSee('sims-cloud-notification');
        $response->assertSee('sims-toast');
        $response->assertSee('Release Manager');
    }

    public function test_notification_bell_renders_in_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Notifications');
        $response->assertSee('activeTab');
        $response->assertSee('Notification Center');
        $response->assertDontSee('Release Manager');
    }

    public function test_notification_bell_view_structure(): void
    {
        $rendered = view('components.notification-bell')->render();

        $this->assertStringContainsString('Notifications', $rendered);
        $this->assertStringContainsString('All', $rendered);
        $this->assertStringContainsString('Unread', $rendered);
        $this->assertStringContainsString('toggleSound()', $rendered);
        $this->assertStringContainsString('markAllAsRead()', $rendered);
        $this->assertStringContainsString('showToast', $rendered);
    }

    public function test_notification_bell_empty_state_structure(): void
    {
        $rendered = view('components.notification-bell', [
            'notifications' => []
        ])->render();

        $this->assertStringContainsString('No Notifications Yet', $rendered);
        $this->assertStringContainsString('0 unread', $rendered);
        $this->assertStringContainsString('All read', $rendered);
        $this->assertStringContainsString('Live', $rendered);
    }
}
