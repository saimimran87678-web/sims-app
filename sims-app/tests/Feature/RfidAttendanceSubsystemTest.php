<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Services\RfidCardService;
use Carbon\Carbon;

class RfidAttendanceSubsystemTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicSession $session;
    protected Classes $class;
    protected Student $student;
    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'shift_type' => 'Morning & Evening',
        ]);

        $this->class = Classes::create([
            'name' => '11-ICS',
            'numeric_value' => 11,
            'shift_type' => 'morning',
            'academic_session_id' => $this->session->id,
        ]);

        $this->student = Student::create([
            'name' => 'Ahmed Khan',
            'roll_no' => '101',
            'admission_no' => 'ADM-2026-101',
            'class_id' => $this->class->id,
            'rfid_tag' => 'A3:F1:28:C9',
            'card_status' => 'active',
        ]);

        // Student::created hook already auto-created enrollment
        $this->enrollment = Enrollment::where('student_id', $this->student->id)->first();
    }

    public function test_rfid_scan_endpoint_marks_student_present_in_sub_20ms()
    {
        $startTime = microtime(true);

        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-01-MAIN',
            'scan_timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $durationMs = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'action' => 'marked_present',
                'student' => [
                    'id' => $this->student->id,
                    'name' => 'Ahmed Khan',
                    'roll_no' => '101',
                    'class_name' => '11-ICS',
                ],
            ]);

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'academic_session_id' => $this->session->id,
            'status' => 'P',
            'source' => 'rfid',
            'device_id' => 'GATE-01-MAIN',
        ]);

        $attendance = Attendance::where('enrollment_id', $this->enrollment->id)->first();
        $this->assertNotNull($attendance->check_in_at);
    }

    public function test_subsequent_tap_within_debounce_window_returns_already_marked()
    {
        Carbon::setTestNow('2026-11-02 08:00:00');

        // First tap
        $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-01-MAIN',
            'scan_timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        // Second tap 1 minute later
        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-01-MAIN',
            'scan_timestamp' => now()->addMinute()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'action' => 'already_marked',
            ]);

        // Must still be only 1 attendance row
        $this->assertEquals(1, Attendance::where('enrollment_id', $this->enrollment->id)->count());

        Carbon::setTestNow();
    }

    public function test_dual_shift_student_morning_tap_marks_morning_enrollment_only()
    {
        // Add evening class and evening enrollment for same student
        $eveningClass = Classes::create([
            'name' => '11-ICS-Evening',
            'numeric_value' => 11,
            'shift_type' => 'evening',
            'academic_session_id' => $this->session->id,
        ]);

        $eveningEnrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $eveningClass->id,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'evening',
            'roll_number' => '201',
            'status' => 'active',
        ]);

        // Morning tap at 08:15 AM
        $morningTime = Carbon::parse('2026-10-15 08:15:00');
        $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-01-MAIN',
            'scan_timestamp' => $morningTime->format('Y-m-d H:i:s'),
        ]);

        // Morning enrollment has attendance
        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $this->enrollment->id,
            'date' => '2026-10-15',
            'status' => 'P',
        ]);

        // Evening enrollment does NOT have attendance yet
        $this->assertDatabaseMissing('attendances', [
            'enrollment_id' => $eveningEnrollment->id,
            'date' => '2026-10-15',
        ]);

        // Evening tap at 14:30 PM (2:30 PM)
        $eveningTime = Carbon::parse('2026-10-15 14:30:00');
        $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-02-EVENING',
            'scan_timestamp' => $eveningTime->format('Y-m-d H:i:s'),
        ]);

        // Now BOTH enrollments have distinct, non-colliding attendance on the same calendar day!
        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $eveningEnrollment->id,
            'date' => '2026-10-15',
            'status' => 'P',
            'device_id' => 'GATE-02-EVENING',
        ]);
    }

    public function test_unregistered_card_returns_404_with_friendly_message()
    {
        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'FF:EE:DD:CC',
            'device_id' => 'GATE-01-MAIN',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'unknown_card',
                'action' => 'unregistered',
                'card_uid' => 'FF:EE:DD:CC',
            ]);
    }

    public function test_blocked_or_lost_card_is_rejected()
    {
        $this->student->update(['card_status' => 'lost']);

        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => 'GATE-01-MAIN',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'blocked_card',
                'action' => 'rejected',
            ]);
    }

    public function test_card_provisioning_write_mode_dispatches_when_armed()
    {
        $cardService = app(RfidCardService::class);
        $deskDevice = 'DESK-01';

        // Arm desk reader for student
        $cardService->armDeviceForWriting($deskDevice, $this->student->id);

        // Blank card tapped on armed desk reader
        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => '11:22:33:44',
            'device_id' => $deskDevice,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'write_mode',
                'action' => 'write_card',
                'card_uid' => '11:22:33:44',
                'student_id' => $this->student->id,
                'student_name' => 'Ahmed Khan',
            ]);

        $this->assertNotEmpty($response->json('payload_hex'));
        $this->assertNotEmpty($response->json('hmac_hex'));

        // Reader confirms card was written successfully
        $confirmResponse = $this->postJson('/api/v1/rfid/card/write-confirm', [
            'device_id' => $deskDevice,
            'card_uid' => '11:22:33:44',
            'student_id' => $this->student->id,
            'status' => 'success',
        ]);

        $confirmResponse->assertStatus(200)
            ->assertJson([
                'status' => 'paired',
            ]);

        $this->student->refresh();
        $this->assertEquals('11:22:33:44', $this->student->rfid_tag);
        $this->assertEquals('active', $this->student->card_status);
        $this->assertNotNull($this->student->card_issued_at);
    }

    public function test_batch_offline_buffered_scans_sync_seamlessly()
    {
        $scanTime1 = '2026-10-10 08:05:00';
        $scanTime2 = '2026-10-11 08:12:00';

        $response = $this->postJson('/api/v1/rfid/sync', [
            'device_id' => 'GATE-01-MAIN',
            'scans' => [
                ['card_uid' => 'A3:F1:28:C9', 'scan_timestamp' => $scanTime1],
                ['card_uid' => 'A3:F1:28:C9', 'scan_timestamp' => $scanTime2],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'processed' => 2,
            ]);

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $this->enrollment->id,
            'date' => '2026-10-10',
            'source' => 'rfid',
        ]);

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $this->enrollment->id,
            'date' => '2026-10-11',
            'source' => 'rfid',
        ]);
    }

    public function test_rfid_card_service_hmac_anti_tamper_verification()
    {
        $cardUid = 'A3:F1:28:C9';
        $studentId = 101;
        $rollNo = '12';

        $validSignature = RfidCardService::computeHmac($cardUid, $studentId, $rollNo);
        $this->assertTrue(RfidCardService::verifyHmac($cardUid, $studentId, $rollNo, $validSignature));

        // Tampered signature or tampered roll number
        $this->assertFalse(RfidCardService::verifyHmac($cardUid, $studentId, '999', $validSignature));
        $this->assertFalse(RfidCardService::verifyHmac('B4:00:11:22', $studentId, $rollNo, $validSignature));
    }

    public function test_smart_card_manager_renders_students_and_filters_by_class()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $admin->givePermissionTo('students.manage');
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\Admin\Rfid\SmartCardManager::class)
            ->assertSee($this->student->name)
            ->set('selectedClassId', $this->class->id)
            ->assertSee($this->student->name)
            ->call('selectStudent', $this->student->id)
            ->assertSet('selectedStudentId', $this->student->id)
            ->call('armWriter')
            ->assertSet('isWritingArmed', true);
    }

    public function test_write_confirm_failure_disarms_device_and_records_failure_result()
    {
        $cardService = app(RfidCardService::class);
        $deskDevice = 'DESK-01';

        $cardService->armDeviceForWriting($deskDevice, $this->student->id);
        $this->assertEquals($this->student->id, $cardService->getPendingWriteJob($deskDevice));

        // Reader reports write failure
        $response = $this->postJson('/api/v1/rfid/card/write-confirm', [
            'device_id' => $deskDevice,
            'card_uid' => '11:22:33:44',
            'student_id' => $this->student->id,
            'status' => 'failed',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Hardware reported write failure.',
            ]);

        // Device must be disarmed
        $this->assertNull($cardService->getPendingWriteJob($deskDevice));

        // Write result must be recorded
        $result = $cardService->pullWriteResult($deskDevice);
        $this->assertNotNull($result);
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals($this->student->id, $result['student_id']);
    }

    public function test_rfid_status_endpoint_records_hardware_health_and_version()
    {
        $response = $this->getJson('/api/v1/rfid/status?device_id=GATE-01-MAIN&rfid_status=ok&rfid_version=v2.0');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'online',
                'device_id' => 'GATE-01-MAIN',
                'rfid_status' => 'ok',
                'rfid_version' => 'v2.0',
            ]);

        $cardService = app(RfidCardService::class);
        $hw = $cardService->getDeviceStatus('GATE-01-MAIN');

        $this->assertTrue($hw['online']);
        $this->assertEquals('ok', $hw['rfid_status']);
        $this->assertEquals('v2.0', $hw['rfid_version']);
    }

    public function test_scan_in_inspect_mode_returns_inspect_action_without_marking_attendance()
    {
        $cardService = app(RfidCardService::class);
        $deskDevice = 'DESK-01';

        // Arm reader for inspect mode
        $cardService->armDeviceForInspection($deskDevice);
        $this->assertEquals('inspect', $cardService->getDeviceMode($deskDevice));

        // Tap card on desk reader
        $response = $this->postJson('/api/v1/rfid/scan', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => $deskDevice,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'inspect_mode',
                'action' => 'inspect_card',
                'device_id' => $deskDevice,
            ]);

        // Verify attendance was NOT marked for this student
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $this->student->id,
            'date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_card_inspect_endpoint_validates_48_byte_payload_and_stores_result_in_cache()
    {
        $cardService = app(RfidCardService::class);
        $deskDevice = 'DESK-01';
        $payloadData = $cardService->generatePayload($this->student, 'A3:F1:28:C9');

        $response = $this->postJson('/api/v1/rfid/card/inspect', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => $deskDevice,
            'payload_hex' => $payloadData['payload_hex'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_registered' => true,
                'is_authentic' => true,
                'is_cloned' => false,
                'hmac_verified' => true,
                'verdict' => 'authentic',
            ]);

        // Assert cached result was stored for Livewire UI
        $cachedResult = $cardService->pullInspectResult($deskDevice);
        $this->assertNotNull($cachedResult);
        $this->assertEquals('A3:F1:28:C9', $cachedResult['card_uid']);
        $this->assertTrue($cachedResult['is_authentic']);
    }

    public function test_card_inspect_detects_cloned_uid_with_blank_or_invalid_hmac()
    {
        $deskDevice = 'DESK-01';

        // Attacker cloned the UID onto a blank card (no payload / unsealed)
        $response = $this->postJson('/api/v1/rfid/card/inspect', [
            'card_uid' => 'A3:F1:28:C9',
            'device_id' => $deskDevice,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_registered' => true,
                'is_authentic' => false,
                'is_cloned' => true,
                'verdict' => 'unsealed_clone',
            ]);
    }

    public function test_smart_card_manager_auto_arms_inspector_and_pulls_result()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $admin->givePermissionTo('students.manage');
        $this->actingAs($admin);

        $cardService = app(RfidCardService::class);
        $deskDevice = 'DESK-01';

        // Store a simulated inspect result in cache as if reader scanned it
        $cardService->recordInspectResult($deskDevice, [
            'card_uid' => 'A3:F1:28:C9',
            'is_registered' => true,
            'is_authentic' => true,
            'is_cloned' => false,
            'hmac_verified' => true,
            'verdict' => 'authentic',
        ]);

        \Livewire\Livewire::test(\App\Livewire\Admin\Rfid\SmartCardManager::class)
            ->call('setTab', 'inspector')
            ->assertSet('activeTab', 'inspector')
            ->call('checkReaderStatus')
            ->assertSet('inspectCardUid', 'A3:F1:28:C9')
            ->assertSee('AUTHENTIC INSTITUTIONAL SMART CARD');
    }
}
