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

class StudentSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_are_sorted_numerically_by_roll_number(): void
    {
        // Seed permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
        ]);

        // Create students with roll numbers 1, 10, 2, 11
        $student1 = Student::create([
            'name' => 'Student A',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $class->id,
        ]);

        $student10 = Student::create([
            'name' => 'Student B',
            'roll_no' => '10',
            'admission_no' => 'ADM-010',
            'class_id' => $class->id,
        ]);

        $student2 = Student::create([
            'name' => 'Student C',
            'roll_no' => '2',
            'admission_no' => 'ADM-002',
            'class_id' => $class->id,
        ]);

        $student11 = Student::create([
            'name' => 'Student D',
            'roll_no' => '11',
            'admission_no' => 'ADM-011',
            'class_id' => $class->id,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);

        // Test Livewire StudentManager component sorts numerically (1, 2, 10, 11)
        Livewire::test(StudentManager::class)
            ->set('selectedClassId', $class->id)
            ->set('sortBy', 'roll_no')
            ->set('sortDir', 'asc')
            ->assertViewHas('students', function ($students) {
                $rollNumbers = $students->pluck('roll_no')->toArray();
                return $rollNumbers === ['1', '2', '10', '11'];
            });
            
        // Test descending order (11, 10, 2, 1)
        Livewire::test(StudentManager::class)
            ->set('selectedClassId', $class->id)
            ->set('sortBy', 'roll_no')
            ->set('sortDir', 'desc')
            ->assertViewHas('students', function ($students) {
                $rollNumbers = $students->pluck('roll_no')->toArray();
                return $rollNumbers === ['11', '10', '2', '1'];
            });
    }
}
