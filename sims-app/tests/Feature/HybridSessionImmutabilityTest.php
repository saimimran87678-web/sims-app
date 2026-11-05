<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\FeeRecord;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HybridSessionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected AcademicSession $session2026;
    protected AcademicSession $session2028;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');

        $this->staff = User::factory()->create(['role' => 'operator']);
        $this->staff->assignRole('Admin'); // Regular admin, not Super Admin

        // Concluded Historical Session
        $this->session2026 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => false,
            'is_archived' => true,
        ]);

        // Active Current Session
        $this->session2028 = AcademicSession::create([
            'name' => '2028-2029',
            'start_date' => '2028-04-01',
            'end_date' => '2029-03-31',
            'is_active' => true,
            'is_archived' => false,
        ]);
    }

    public function test_future_classes_do_not_appear_in_historical_session_scope(): void
    {
        // Class created and used in 2026
        $class2026 = Classes::create([
            'name' => 'Class 7A',
            'numeric_value' => 7,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student1 = Student::create(['name' => 'Usman Tariq', 'admission_no' => 'ADM-2026-001']);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $class2026->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'completed',
        ]);

        // Future Class constructed in 2028 (never existed in 2026)
        $futureClass = Classes::create([
            'name' => 'Class 9-Robotics',
            'numeric_value' => 9,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        // Historical Session Scope: should ONLY include Class 7A, NOT Class 9-Robotics
        $historicalClasses = Classes::forSession($this->session2026->id)->pluck('name')->toArray();
        $this->assertContains('Class 7A', $historicalClasses);
        $this->assertNotContains('Class 9-Robotics', $historicalClasses);

        // Active Session Scope: should include both active classrooms
        $activeClasses = Classes::forSession($this->session2028->id)->pluck('name')->toArray();
        $this->assertContains('Class 7A', $activeClasses);
        $this->assertContains('Class 9-Robotics', $activeClasses);
    }

    public function test_archived_classes_remain_visible_in_historical_session_scope(): void
    {
        // Classroom decommissioned/archived in 2028
        $archivedRoom = Classes::create([
            'name' => 'Old ECE Hall',
            'numeric_value' => -2,
            'shift_type' => 'morning',
            'is_active' => false, // Archived
        ]);

        $student = Student::create(['name' => 'Zain Ali', 'admission_no' => 'ADM-2026-002']);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $archivedRoom->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'morning',
            'roll_number' => '2',
            'status' => 'completed',
        ]);

        // Concluded 2026 session MUST preserve Old ECE Hall in reports and records
        $historicalClasses = Classes::forSession($this->session2026->id, 'morning')->pluck('name')->toArray();
        $this->assertContains('Old ECE Hall', $historicalClasses);

        // Current 2028 session must NOT include archived room
        $activeClasses = Classes::forSession($this->session2028->id, 'morning')->pluck('name')->toArray();
        $this->assertNotContains('Old ECE Hall', $activeClasses);
    }

    public function test_historical_session_ledger_cannot_be_mutated(): void
    {
        $room = Classes::create([
            'name' => 'Class 5A',
            'numeric_value' => 5,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student = Student::create(['name' => 'Hamza Tariq', 'admission_no' => 'ADM-2026-003']);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '3',
            'status' => 'completed',
        ]);

        // Act as non-SuperAdmin staff
        $this->actingAs($this->staff);

        // Attempting to modify historical enrollment is blocked by Domain Guard
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Historical enrollments cannot be modified');

        $enrollment->roll_number = '99';
        $enrollment->save();
    }

    public function test_historical_session_blocks_attendance_mutation(): void
    {
        $room = Classes::create([
            'name' => 'Class 5B',
            'numeric_value' => 5,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student = Student::create(['name' => 'Sara Bilal', 'admission_no' => 'ADM-2026-004']);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '4',
            'status' => 'completed',
        ]);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'date' => '2026-05-10',
            'status' => 'present',
            'shift_type' => 'regular',
        ]);

        $this->actingAs($this->staff);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Historical attendance registers cannot be modified');

        $attendance->status = 'absent';
        $attendance->save();
    }

    public function test_student_biographical_typos_can_be_corrected_with_audit_trail(): void
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 8-Jinnah',
            'numeric_value' => 8,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student = Student::create([
            'name' => 'Muhammad Ahmadd', // Typo in name
            'admission_no' => 'ADM-2026-005',
            'father_name' => 'Tariqq Khan', // Typo in father name
            'dob' => '2012-05-15',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '5',
            'status' => 'completed',
        ]);

        // In StudentManager, admin selects historical session 2026 and edits student
        Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->call('edit', $student->id)
            ->set('name', 'Muhammad Ahmad') // Fixed typo
            ->set('father_name', 'Tariq Khan') // Fixed father name typo
            ->set('b_form_number', '35202-1234567-1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Student biographical information updated successfully');

        // Verify master identity was corrected
        $student->refresh();
        $this->assertEquals('Muhammad Ahmad', $student->name);
        $this->assertEquals('Tariq Khan', $student->father_name);
        $this->assertEquals('35202-1234567-1', $student->b_form_number);

        // Verify session enrollment remained completely intact
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('academic_session_id', $this->session2026->id)
            ->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals('5', $enrollment->roll_number);
        $this->assertEquals($room->id, $enrollment->class_id);

        // Verify activity log captured this update
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'students',
            'action' => 'updated',
        ]);
    }

    public function test_historical_session_blocks_new_admissions(): void
    {
        $this->actingAs($this->admin);

        $initialCount = Student::count();

        Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->set('isEditing', false)
            ->set('name', 'Illegal Student')
            ->set('admission_no', 'ADM-NEW-999')
            ->set('gender', 'male')
            ->call('save')
            ->assertSee('New admissions cannot be added to a concluded academic session');

        $this->assertEquals($initialCount, Student::count());
    }

    public function test_historical_session_allows_data_export(): void
    {
        $this->actingAs($this->admin);

        $room = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $student = Student::create([
            'name' => 'Bilal Arshad',
            'admission_no' => 'ADM-2026-010',
            'father_name' => 'Arshad Mehmood',
            'gender' => 'male',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $room->id,
            'academic_session_id' => $this->session2026->id,
            'shift_type' => 'regular',
            'roll_number' => '10',
            'status' => 'completed',
        ]);

        $component = Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $this->session2026->id)
            ->call('exportCsv');

        $response = $component->instance()->exportCsv();

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('students_export_2026-2027', $response->headers->get('Content-Disposition'));
    }
}
