<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use App\Livewire\Admin\Rfid\SmartCardManager;
use App\Livewire\Admin\Rfid\RfidGateMonitor;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\AttendanceManager;
use Database\Seeders\RolesAndPermissionsSeeder;

class RfidLicenseGatingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicSession $session;
    protected Classes $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'shift_type' => 'Morning & Evening',
        ]);

        $this->class = Classes::create([
            'name' => '10-A',
            'numeric_value' => 10,
            'shift_type' => 'morning',
            'academic_session_id' => $this->session->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');
    }

    protected function setLicenseModules(array $modules, bool $isV3 = true): void
    {
        $sslConfig = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        if (file_exists('C:/Program Files/SIMS/runtime/php/extras/ssl/openssl.cnf')) {
            $sslConfig['config'] = 'C:/Program Files/SIMS/runtime/php/extras/ssl/openssl.cnf';
        }

        $keys = openssl_pkey_new($sslConfig);
        openssl_pkey_export($keys, $privateKey, null, $sslConfig);
        $details = openssl_pkey_get_details($keys);
        $publicKey = $details['key'];
        config(['services.license.rsa_public_key' => $publicKey]);

        $licenseKey = 'LIC-TEST-KEY-001';
        $expiresAtStr = now()->addYear()->utc()->format('Y-m-d H:i:s');
        $statusStr = 'active';
        $schoolId = 'TEST-SCHOOL';
        $allowedDomains = 'localhost';

        $payload = LicenseVerifier::buildPayload($licenseKey, $expiresAtStr, $statusStr);
        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $sig = base64_encode($signature);
        $hash = LicenseVerifier::computeIntegrityHash($licenseKey, $expiresAtStr, $statusStr, $schoolId, $allowedDomains);

        DB::table('software_licenses')->truncate();
        DB::table('software_licenses')->insert([
            'license_key'             => encrypt($licenseKey),
            'school_id'               => $schoolId,
            'bound_machine_uuid'      => null,
            'firebase_refresh_token'  => encrypt('refresh_token'),
            'status'                  => encrypt($statusStr),
            'plan'                    => encrypt('premium'),
            'allowed_domains'         => encrypt($allowedDomains),
            'expires_at'              => $expiresAtStr,
            'rsa_signature'           => $sig,
            'integrity_hash'          => $hash,
            'offline_grace_days'      => 7,
            'enabled_modules'         => json_encode($modules),
            'broadcast_announcement'  => null,
            'schedule_type_policy'    => 'configurable',
            'config_version'          => 37,
            'modules_v2'              => true,
            'modules_v3'              => $isV3,
            'last_online_verified_at' => now(),
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        LicenseStatus::clearCache();
    }

    public function test_rfid_routes_and_components_accessible_when_module_enabled(): void
    {
        $this->setLicenseModules(['attendance', 'students', 'rfid'], true);
        $this->actingAs($this->admin);

        $this->assertTrue(LicenseStatus::isModuleEnabled('rfid'));

        // Web route: Smart Cards
        $response1 = $this->get('/admin/attendance/smart-cards');
        $response1->assertStatus(200);

        // Web route: Gate Monitor
        $response2 = $this->get('/admin/attendance/gate-monitor');
        $response2->assertStatus(200);

        // API route: RFID status
        $apiResponse = $this->getJson('/api/v1/rfid/status');
        $apiResponse->assertStatus(200);
    }

    public function test_rfid_web_routes_redirect_when_module_disabled(): void
    {
        $this->setLicenseModules(['attendance', 'students'], true); // 'rfid' excluded
        $this->actingAs($this->admin);

        $this->assertFalse(LicenseStatus::isModuleEnabled('rfid'));

        // Smart Cards redirect
        $response1 = $this->get('/admin/attendance/smart-cards');
        $response1->assertRedirect(route('admin.dashboard'));
        $response1->assertSessionHas('error', 'The RFID & Smart Gate module is currently disabled by administrative license policy.');

        // Gate Monitor redirect
        $response2 = $this->get('/admin/attendance/gate-monitor');
        $response2->assertRedirect(route('admin.dashboard'));
        $response2->assertSessionHas('error', 'The RFID & Smart Gate module is currently disabled by administrative license policy.');
    }

    public function test_rfid_api_routes_return_403_when_module_disabled(): void
    {
        $this->setLicenseModules(['attendance', 'students'], true); // 'rfid' excluded

        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A1:B2:C3:D4',
            'device_id' => 'GATE-01',
            'scan_timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'The RFID & Smart Gate module is currently disabled by administrative license policy.',
            ]);

        $statusResponse = $this->getJson('/api/v1/rfid/status');
        $statusResponse->assertStatus(403);
    }

    public function test_rfid_ui_links_are_hidden_when_module_disabled(): void
    {
        $this->setLicenseModules(['attendance', 'students'], true); // 'rfid' excluded
        $this->actingAs($this->admin);

        // Layout sidebar does not contain RFID routes
        $layoutHtml = view('components.layouts.admin', ['slot' => '<div>Test</div>'])->render();
        $this->assertStringNotContainsString(route('admin.attendance.smart-cards'), $layoutHtml);
        $this->assertStringNotContainsString(route('admin.attendance.gate-monitor'), $layoutHtml);

        // Attendance Manager does not show Smart Card Writer or Live Gate Monitor buttons
        $attendanceHtml = Livewire::test(AttendanceManager::class)->html();
        $this->assertStringNotContainsString('Smart Card Writer', $attendanceHtml);
        $this->assertStringNotContainsString('Live Gate Monitor', $attendanceHtml);

        // Dashboard does not show Smart Cards or Gate Monitor buttons
        $dashboardHtml = Livewire::test(Dashboard::class)->html();
        $this->assertStringNotContainsString('Smart Cards', $dashboardHtml);
        $this->assertStringNotContainsString('Gate Monitor', $dashboardHtml);
    }

    public function test_rfid_ui_links_are_shown_when_module_enabled(): void
    {
        $this->setLicenseModules(['attendance', 'students', 'rfid'], true);
        $this->actingAs($this->admin);

        // Layout sidebar contains RFID links
        $layoutHtml = view('components.layouts.admin', ['slot' => '<div>Test</div>'])->render();
        $this->assertStringContainsString(route('admin.attendance.smart-cards'), $layoutHtml);
        $this->assertStringContainsString(route('admin.attendance.gate-monitor'), $layoutHtml);

        // Attendance Manager shows Smart Card Writer & Live Gate Monitor buttons
        $attendanceHtml = Livewire::test(AttendanceManager::class)->html();
        $this->assertStringContainsString('Smart Card Writer', $attendanceHtml);
        $this->assertStringContainsString('Live Gate Monitor', $attendanceHtml);

        // Dashboard shows Smart Cards & Gate Monitor action buttons
        $dashboardHtml = Livewire::test(Dashboard::class)->html();
        $this->assertStringContainsString('Smart Cards', $dashboardHtml);
        $this->assertStringContainsString('Gate Monitor', $dashboardHtml);
    }

    public function test_rfid_livewire_components_abort_when_module_disabled(): void
    {
        $this->setLicenseModules(['attendance', 'students'], true); // 'rfid' excluded
        $this->actingAs($this->admin);

        Livewire::test(SmartCardManager::class)->assertStatus(403);
        Livewire::test(RfidGateMonitor::class)->assertStatus(403);
    }

    public function test_legacy_license_without_modules_v3_inherits_attendance(): void
    {
        // Legacy license with attendance enabled, modules_v3 = false, rfid omitted
        $this->setLicenseModules(['attendance', 'students'], false);
        $this->assertTrue(LicenseStatus::isModuleEnabled('rfid'));

        // Legacy license without attendance, modules_v3 = false
        $this->setLicenseModules(['fees', 'students'], false);
        $this->assertFalse(LicenseStatus::isModuleEnabled('rfid'));
    }
}
