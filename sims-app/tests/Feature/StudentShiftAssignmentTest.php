<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Livewire\Admin\StudentManager;
use Livewire\Livewire;

class StudentShiftAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $regularSession;
    private $dualSession;
    private $regularClass;
    private $dualClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->regularSession = AcademicSession::create([
            'name' => 'Regular Session',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->dualSession = AcademicSession::create([
            'name' => 'Dual Session',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Dual',
            'is_active' => false,
        ]);

        $this->regularClass = Classes::create([
            'name' => 'Regular Class',
            'numeric_value' => 10,
            'academic_session_id' => $this->regularSession->id,
        ]);

        $this->dualClass = Classes::create([
            'name' => 'Dual Class Morning',
            'numeric_value' => 10,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
        ]);

        $this->dualClassEvening = Classes::create([
            'name' => 'Dual Class Evening',
            'numeric_value' => 10,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'evening',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin);
    }

    public function test_regular_session_student_add_defaults_to_regular_shift(): void
    {
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->regularSession->id)
            ->call('openModal')
            ->assertSet('student_shift', 'regular')
            ->set('class_id', $this->regularClass->id)
            ->set('name', 'John Regular')
            ->set('admission_no', 'ADM-REG-1')
            ->set('roll_no', '1')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('name', 'John Regular')->first();
        $this->assertNotNull($student);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('academic_session_id', $this->regularSession->id)
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertEquals('regular', $enrollment->shift_type);
    }

    public function test_dual_session_student_add_allows_morning_and_evening_shifts(): void
    {
        // 1. Morning Student
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->call('openModal')
            ->assertSet('student_shift', 'morning')
            ->set('student_shift_option', 'morning')
            ->set('singleStudentClasses.morning', $this->dualClass->id)
            ->set('name', 'John Morning')
            ->set('admission_no', 'ADM-AM-1')
            ->set('roll_no', '1')
            ->call('save')
            ->assertHasNoErrors();

        $studentAm = Student::where('name', 'John Morning')->first();
        $this->assertNotNull($studentAm);

        $enrollmentAm = Enrollment::where('student_id', $studentAm->id)
            ->where('academic_session_id', $this->dualSession->id)
            ->first();

        $this->assertNotNull($enrollmentAm);
        $this->assertEquals('morning', $enrollmentAm->shift_type);

        // 2. Evening Student
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->call('openModal')
            ->set('student_shift_option', 'evening')
            ->set('singleStudentClasses.evening', $this->dualClassEvening->id)
            ->set('name', 'John Evening')
            ->set('admission_no', 'ADM-PM-1')
            ->set('roll_no', '2')
            ->call('save')
            ->assertHasNoErrors();

        $studentPm = Student::where('name', 'John Evening')->first();
        $this->assertNotNull($studentPm);

        $enrollmentPm = Enrollment::where('student_id', $studentPm->id)
            ->where('academic_session_id', $this->dualSession->id)
            ->first();

        $this->assertNotNull($enrollmentPm);
        $this->assertEquals('evening', $enrollmentPm->shift_type);
    }

    public function test_bulk_status_update_deactivates_and_activates_students(): void
    {
        $student1 = Student::create([
            'name' => 'Bulk Student 1',
            'admission_no' => 'ADM-B1',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->dualClass->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '10',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'name' => 'Bulk Student 2',
            'admission_no' => 'ADM-B2',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->dualClass->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '11',
            'status' => 'active',
        ]);

        // Bulk deactivate
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->set('bulkStatus', 'inactive')
            ->call('bulkUpdateStatus')
            ->assertHasNoErrors();

        $student1->refresh();
        $student2->refresh();
        $this->assertEquals('inactive', $student1->status);
        $this->assertEquals('inactive', $student2->status);

        $enrollment1 = Enrollment::where('student_id', $student1->id)->where('academic_session_id', $this->dualSession->id)->first();
        $enrollment2 = Enrollment::where('student_id', $student2->id)->where('academic_session_id', $this->dualSession->id)->first();
        $this->assertEquals('inactive', $enrollment1->status);
        $this->assertEquals('inactive', $enrollment2->status);
    }

    public function test_bulk_shift_update_changes_shift_and_matches_class(): void
    {
        // Create matching evening class with same name as dualClass
        $this->dualClass->update(['name' => 'Class X']);
        $this->dualClassEvening->update(['name' => 'Class X']);

        $student = Student::create([
            'name' => 'Shift Student',
            'admission_no' => 'ADM-S1',
            'status' => 'active',
        ]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->dualClass->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '12',
            'status' => 'active',
        ]);

        // Bulk shift update to evening
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student->id])
            ->set('bulkShift', 'evening')
            ->call('bulkUpdateShift')
            ->assertHasNoErrors();

        $enrollment->refresh();
        $this->assertEquals('evening', $enrollment->shift_type);
        $this->assertEquals($this->dualClassEvening->id, $enrollment->class_id);
    }

    public function test_delete_student_removes_enrollment_and_cleans_up(): void
    {
        // 1. Create a student with enrollments in two sessions
        $student = Student::create([
            'name' => 'Session Student',
            'admission_no' => 'ADM-DEL-1',
            'status' => 'active',
        ]);

        $enrollment1 = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->dualClass->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '20',
            'status' => 'active',
        ]);

        $enrollment2 = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->regularClass->id,
            'academic_session_id' => $this->regularSession->id,
            'shift_type' => 'regular',
            'roll_number' => '20',
            'status' => 'active',
        ]);

        // 2. Call delete on StudentManager when dualSession is selected
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->call('delete', $student->id)
            ->assertHasNoErrors();

        // 3. Enrollment for dualSession should be deleted, but regularSession should remain
        $this->assertDatabaseMissing('enrollments', [
            'id' => $enrollment1->id
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment2->id
        ]);

        // 4. Student model should still exist because it has another enrollment
        $this->assertDatabaseHas('students', [
            'id' => $student->id
        ]);

        // 5. Delete student from StudentManager under regularSession
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->regularSession->id)
            ->call('delete', $student->id)
            ->assertHasNoErrors();

        // 6. Both enrollments are now gone, so the student model should be fully deleted
        $this->assertDatabaseMissing('enrollments', [
            'id' => $enrollment2->id
        ]);
        $this->assertDatabaseMissing('students', [
            'id' => $student->id
        ]);
    }
}
