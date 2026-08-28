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
use Illuminate\Support\Facades\DB;

class StudentBulkEditTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $dualSession;
    private $morningClass1;
    private $morningClass2;
    private $eveningClass1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->dualSession = AcademicSession::create([
            'name' => 'Dual Shift Session',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Dual',
            'is_active' => true,
        ]);

        $this->morningClass1 = Classes::create([
            'name' => 'Morning Class 1',
            'numeric_value' => 10,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
        ]);

        $this->morningClass2 = Classes::create([
            'name' => 'Morning Class 2',
            'numeric_value' => 10,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
        ]);

        $this->eveningClass1 = Classes::create([
            'name' => 'Evening Class 1',
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

    public function test_bulk_status_update(): void
    {
        $student1 = Student::create([
            'name' => 'Student One',
            'admission_no' => 'ADM-001',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->morningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'name' => 'Student Two',
            'admission_no' => 'ADM-002',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->morningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '2',
            'status' => 'active',
        ]);

        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->call('openBulkEditModal')
            ->assertSet('bulkActionType', 'status')
            ->set('bulkStatus', 'inactive')
            ->call('saveBulkEdit')
            ->assertHasNoErrors();

        $student1->refresh();
        $student2->refresh();
        $this->assertEquals('inactive', $student1->status);
        $this->assertEquals('inactive', $student2->status);

        $enrollment1 = Enrollment::where('student_id', $student1->id)->first();
        $enrollment2 = Enrollment::where('student_id', $student2->id)->first();
        $this->assertEquals('inactive', $enrollment1->status);
        $this->assertEquals('inactive', $enrollment2->status);
    }

    public function test_bulk_shift_change_to_morning_only_requires_class_and_applies_to_all(): void
    {
        $student1 = Student::create([
            'name' => 'Morning Student',
            'admission_no' => 'ADM-M1',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->morningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'name' => 'Evening Student',
            'admission_no' => 'ADM-E1',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->eveningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'evening',
            'roll_number' => '2',
            'status' => 'active',
        ]);

        // Attempting to save without selecting a morning class should trigger validation error
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->call('openBulkEditModal')
            ->set('bulkActionType', 'shift')
            ->set('bulkShiftOption', 'morning')
            ->call('saveBulkEdit')
            ->assertHasErrors(["bulkClassMorning"]);

        // Now select morning class and save
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->call('openBulkEditModal')
            ->set('bulkActionType', 'shift')
            ->set('bulkShiftOption', 'morning')
            ->set("bulkClassMorning", $this->morningClass2->id)
            ->call('saveBulkEdit')
            ->assertHasNoErrors();

        // Check enrollments: student1 should now be in morningClass2 (morning shift only)
        $student1Enrollments = Enrollment::where('student_id', $student1->id)->get();
        $this->assertCount(1, $student1Enrollments);
        $this->assertEquals('morning', $student1Enrollments->first()->shift_type);
        $this->assertEquals($this->morningClass2->id, $student1Enrollments->first()->class_id);

        // Check enrollments: student2 should have a morning enrollment in morningClass2 and NO evening enrollment
        $student2Enrollments = Enrollment::where('student_id', $student2->id)->get();
        $this->assertCount(1, $student2Enrollments);
        $this->assertEquals('morning', $student2Enrollments->first()->shift_type);
        $this->assertEquals($this->morningClass2->id, $student2Enrollments->first()->class_id);
    }

    public function test_bulk_shift_change_to_evening_only_requires_class_and_applies_to_all(): void
    {
        $student1 = Student::create([
            'name' => 'Morning Student 2',
            'admission_no' => 'ADM-M2',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->morningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id])
            ->call('openBulkEditModal')
            ->set('bulkActionType', 'shift')
            ->set('bulkShiftOption', 'evening')
            ->call('saveBulkEdit')
            ->assertHasErrors(["bulkClassEvening"]);

        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id])
            ->call('openBulkEditModal')
            ->set('bulkActionType', 'shift')
            ->set('bulkShiftOption', 'evening')
            ->set("bulkClassEvening", $this->eveningClass1->id)
            ->call('saveBulkEdit')
            ->assertHasNoErrors();

        $student1Enrollments = Enrollment::where('student_id', $student1->id)->get();
        $this->assertCount(1, $student1Enrollments);
        $this->assertEquals('evening', $student1Enrollments->first()->shift_type);
        $this->assertEquals($this->eveningClass1->id, $student1Enrollments->first()->class_id);
    }

    public function test_bulk_shift_change_to_both_shifts_adds_remaining_shift_enrollment(): void
    {
        // student1 in morning
        $student1 = Student::create([
            'name' => 'Morning Student 3',
            'admission_no' => 'ADM-M3',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->morningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        // student2 in evening
        $student2 = Student::create([
            'name' => 'Evening Student 3',
            'admission_no' => 'ADM-E3',
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->eveningClass1->id,
            'academic_session_id' => $this->dualSession->id,
            'shift_type' => 'evening',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->dualSession->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->call('openBulkEditModal')
            ->set('bulkActionType', 'shift')
            ->set('bulkShiftOption', 'both')
            ->set("bulkClassMorning", $this->morningClass2->id)
            ->set("bulkClassEvening", $this->eveningClass1->id)
            ->call('saveBulkEdit')
            ->assertHasNoErrors();

        // Check student1 has both
        $student1Enrollments = Enrollment::where('student_id', $student1->id)->get();
        $this->assertCount(2, $student1Enrollments);
        $this->assertTrue($student1Enrollments->contains('shift_type', 'morning'));
        $this->assertTrue($student1Enrollments->contains('shift_type', 'evening'));
        $this->assertEquals($this->morningClass2->id, $student1Enrollments->firstWhere('shift_type', 'morning')->class_id);
        $this->assertEquals($this->eveningClass1->id, $student1Enrollments->firstWhere('shift_type', 'evening')->class_id);

        // Check student2 has both
        $student2Enrollments = Enrollment::where('student_id', $student2->id)->get();
        $this->assertCount(2, $student2Enrollments);
        $this->assertTrue($student2Enrollments->contains('shift_type', 'morning'));
        $this->assertTrue($student2Enrollments->contains('shift_type', 'evening'));
        $this->assertEquals($this->morningClass2->id, $student2Enrollments->firstWhere('shift_type', 'morning')->class_id);
        $this->assertEquals($this->eveningClass1->id, $student2Enrollments->firstWhere('shift_type', 'evening')->class_id);
    }
}
