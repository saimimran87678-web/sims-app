<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Livewire\Admin\StudentManager;
use Livewire\Livewire;

class StudentRollNoGenerationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin);
    }

    public function test_auto_assigns_roll_number_when_class_selected(): void
    {
        // 1. Create a student with roll no 23
        Student::create([
            'name' => 'Existing Student',
            'roll_no' => '23',
            'admission_no' => 'ADM-23',
            'class_id' => $this->class->id,
        ]);

        // Test that Livewire auto assigns roll no 24 when class is selected
        Livewire::test(StudentManager::class)
            ->call('openModal')
            ->set('class_id', $this->class->id)
            ->assertSet('roll_no', '24');
    }

    public function test_auto_assigns_roll_number_1_if_class_is_empty(): void
    {
        // Test that Livewire auto assigns roll no 1 when class is selected and has no students
        Livewire::test(StudentManager::class)
            ->call('openModal')
            ->set('class_id', $this->class->id)
            ->assertSet('roll_no', '1');
    }

    public function test_adjusts_roll_numbers_on_new_student_conflict(): void
    {
        // Create initial students: 21, 22, 23, 24, 26
        $s21 = Student::create(['name' => 'S21', 'roll_no' => '21', 'admission_no' => 'ADM-21', 'class_id' => $this->class->id]);
        $s22 = Student::create(['name' => 'S22', 'roll_no' => '22', 'admission_no' => 'ADM-22', 'class_id' => $this->class->id]);
        $s23 = Student::create(['name' => 'S23', 'roll_no' => '23', 'admission_no' => 'ADM-23', 'class_id' => $this->class->id]);
        $s24 = Student::create(['name' => 'S24', 'roll_no' => '24', 'admission_no' => 'ADM-24', 'class_id' => $this->class->id]);
        $s26 = Student::create(['name' => 'S26', 'roll_no' => '26', 'admission_no' => 'ADM-26', 'class_id' => $this->class->id]);

        // Add a new student manually with roll_no 23
        Livewire::test(StudentManager::class)
            ->call('openModal')
            ->set('class_id', $this->class->id)
            ->set('name', 'New Student')
            ->set('admission_no', 'ADM-NEW')
            ->set('roll_no', '23')
            ->call('save');

        // Assert roll numbers in DB are correctly shifted:
        // New Student should be 23
        // S23 -> 24
        // S24 -> 25
        // S26 -> remains 26
        $this->assertEquals('21', $s21->fresh()->roll_no);
        $this->assertEquals('22', $s22->fresh()->roll_no);
        $this->assertEquals('24', $s23->fresh()->roll_no);
        $this->assertEquals('25', $s24->fresh()->roll_no);
        $this->assertEquals('26', $s26->fresh()->roll_no);

        $newStudent = Student::where('admission_no', 'ADM-NEW')->first();
        $this->assertNotNull($newStudent);
        $this->assertEquals('23', $newStudent->roll_no);
    }

    public function test_adjusts_roll_numbers_on_existing_student_update_conflict(): void
    {
        // Create initial students: 1, 2, 3
        $s1 = Student::create(['name' => 'S1', 'roll_no' => '1', 'admission_no' => 'ADM-1', 'class_id' => $this->class->id]);
        $s2 = Student::create(['name' => 'S2', 'roll_no' => '2', 'admission_no' => 'ADM-2', 'class_id' => $this->class->id]);
        $s3 = Student::create(['name' => 'S3', 'roll_no' => '3', 'admission_no' => 'ADM-3', 'class_id' => $this->class->id]);

        // Edit S3 (currently roll_no 3) and change its roll_no to 2
        Livewire::test(StudentManager::class)
            ->call('edit', $s3->id)
            ->set('roll_no', '2')
            ->call('save');

        // Assert shifted:
        // S3 -> 2
        // S2 -> 3
        // S1 -> remains 1
        $this->assertEquals('1', $s1->fresh()->roll_no);
        $this->assertEquals('3', $s2->fresh()->roll_no);
        $this->assertEquals('2', $s3->fresh()->roll_no);
    }

    public function test_manual_assign_12_when_12_exists_and_13_does_not_exist(): void
    {
        // Student A has roll_no 12
        $s12 = Student::create(['name' => 'S12', 'roll_no' => '12', 'admission_no' => 'ADM-12', 'class_id' => $this->class->id]);

        // Manually assign 12 to a new student B
        Livewire::test(StudentManager::class)
            ->call('openModal')
            ->set('class_id', $this->class->id)
            ->set('name', 'New Student')
            ->set('admission_no', 'ADM-NEW')
            ->set('auto_roll_no', false)
            ->set('roll_no', '12')
            ->call('save');

        // Assert S12 is shifted to 13, and New Student gets 12
        $this->assertEquals('13', $s12->fresh()->roll_no);

        $newStudent = Student::where('admission_no', 'ADM-NEW')->first();
        $this->assertNotNull($newStudent);
        $this->assertEquals('12', $newStudent->roll_no);
    }

    public function test_fills_gaps_and_compacts_consecutive_roll_numbers(): void
    {
        // Students have roll numbers: 12, 23, 24
        $s12 = Student::create(['name' => 'S12', 'roll_no' => '12', 'admission_no' => 'ADM-12', 'class_id' => $this->class->id]);
        $s23 = Student::create(['name' => 'S23', 'roll_no' => '23', 'admission_no' => 'ADM-23', 'class_id' => $this->class->id]);
        $s24 = Student::create(['name' => 'S24', 'roll_no' => '24', 'admission_no' => 'ADM-24', 'class_id' => $this->class->id]);

        // Manually assign 12 to a new student B
        Livewire::test(StudentManager::class)
            ->call('openModal')
            ->set('class_id', $this->class->id)
            ->set('name', 'New Student')
            ->set('admission_no', 'ADM-NEW')
            ->set('auto_roll_no', false)
            ->set('roll_no', '12')
            ->call('save');

        // Assert shifted and compacted:
        // New Student -> 12
        // S12 -> 13
        // S23 -> 14
        // S24 -> 15
        $this->assertEquals('13', $s12->fresh()->roll_no);
        $this->assertEquals('14', $s23->fresh()->roll_no);
        $this->assertEquals('15', $s24->fresh()->roll_no);

        $newStudent = Student::where('admission_no', 'ADM-NEW')->first();
        $this->assertNotNull($newStudent);
        $this->assertEquals('12', $newStudent->roll_no);
    }
}
