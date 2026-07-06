<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Livewire\Teacher\StudentList;
use Livewire\Livewire;

class TeacherStudentRollNoTest extends TestCase
{
    use RefreshDatabase;

    private $teacher;
    private $class;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);
        $this->teacher->assignRole('Teacher');

        // Link teacher to class in active session
        DB::table('session_user')->insert([
            'user_id' => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->teacher);
    }

    public function test_teacher_auto_assigns_roll_number_when_creating_student(): void
    {
        Student::create([
            'name' => 'Student 1',
            'roll_no' => '10',
            'admission_no' => 'ADM-10',
            'class_id' => $this->class->id,
        ]);

        Livewire::test(StudentList::class)
            ->call('create')
            ->assertSet('roll_no', '11')
            ->set('name', 'Student 2')
            ->set('admission_no', 'ADM-11')
            ->call('store');

        $student2 = Student::where('admission_no', 'ADM-11')->first();
        $this->assertNotNull($student2);
        $this->assertEquals('11', $student2->roll_no);
    }

    public function test_teacher_manually_assigns_and_shifts_roll_numbers_with_gap_compaction(): void
    {
        $s12 = Student::create(['name' => 'S12', 'roll_no' => '12', 'admission_no' => 'ADM-12', 'class_id' => $this->class->id]);
        $s23 = Student::create(['name' => 'S23', 'roll_no' => '23', 'admission_no' => 'ADM-23', 'class_id' => $this->class->id]);
        $s24 = Student::create(['name' => 'S24', 'roll_no' => '24', 'admission_no' => 'ADM-24', 'class_id' => $this->class->id]);

        Livewire::test(StudentList::class)
            ->call('create')
            ->set('name', 'New Student')
            ->set('admission_no', 'ADM-NEW')
            ->set('auto_roll_no', false)
            ->set('roll_no', '12')
            ->call('store');

        // Assert S12 shifted to 13, S23 to 14, S24 to 15
        $this->assertEquals('13', $s12->fresh()->roll_no);
        $this->assertEquals('14', $s23->fresh()->roll_no);
        $this->assertEquals('15', $s24->fresh()->roll_no);

        $newStudent = Student::where('admission_no', 'ADM-NEW')->first();
        $this->assertNotNull($newStudent);
        $this->assertEquals('12', $newStudent->roll_no);
    }
}
