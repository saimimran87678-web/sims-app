<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Livewire\Admin\Dashboard;
use Livewire\Livewire;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_attendance_stats_only_include_current_session_data(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // 1. Create Old Session (2025)
        $oldSession = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'shift_type' => 'Regular',
            'is_active' => false,
        ]);

        // 2. Create Current Session (2026)
        $currentSession = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true, // Active
        ]);

        // 3. Create Classes
        $oldClass = Classes::create([
            'name' => 'Class 9A',
            'numeric_value' => 9,
            'academic_session_id' => $oldSession->id,
        ]);

        $currentClass = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $currentSession->id,
        ]);

        // 4. Create Student (This automatically creates the enrollment in $currentSession)
        $student = Student::create([
            'name' => 'John Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $currentClass->id,
        ]);

        // 5. Enroll student manually only in the old session
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $oldClass->id,
            'academic_session_id' => $oldSession->id,
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        // 6. Record attendance:
        // - 1 Absent in Old Session (2025-05-10)
        // - 1 Present in Current Session (2026-05-10)
        DB::table('attendances')->insert([
            [
                'student_id' => $student->id,
                'academic_session_id' => $oldSession->id,
                'date' => '2025-05-10',
                'status' => 'A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $student->id,
                'academic_session_id' => $currentSession->id,
                'date' => '2026-05-10',
                'status' => 'P',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        // Set the active session in session to the current session (2026)
        session(['selected_academic_session_id' => $currentSession->id]);

        // Assert that the dashboard only counts the 2026 attendance:
        // total: 1 (2026-05-10), present: 1 (2026-05-10) -> 100%
        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['attendance'] == 100.0;
            })
            ->assertViewHas('attendanceTrend', function ($trend) {
                // Should only contain the 2026-05-10 date
                $dates = collect($trend)->pluck('date')->toArray();
                return count($dates) === 1 && $dates[0] === '10 May';
            });

        // Set the active session in session to the old session (2025)
        session(['selected_academic_session_id' => $oldSession->id]);

        // Assert that the dashboard only counts the 2025 attendance:
        // total: 1 (2025-05-10), present: 0 -> 0%
        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['attendance'] == 0.0;
            })
            ->assertViewHas('attendanceTrend', function ($trend) {
                $dates = collect($trend)->pluck('date')->toArray();
                return count($dates) === 1 && $dates[0] === '10 May';
            });
    }

    public function test_dashboard_attendance_ignores_inactive_student_data(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
        ]);

        // 1. Create a student who is active in the session
        $activeStudent = Student::create([
            'name' => 'Active Student',
            'roll_no' => '1',
            'admission_no' => 'ADM-ACT-1',
            'class_id' => $class->id,
        ]);

        // 2. Create a student who is promoted (inactive) in the session
        $inactiveStudent = Student::create([
            'name' => 'Inactive Student',
            'roll_no' => '2',
            'admission_no' => 'ADM-INA-2',
            'class_id' => $class->id,
        ]);

        // Mark the second student's enrollment status as promoted (inactive)
        Enrollment::where('student_id', $inactiveStudent->id)
            ->where('academic_session_id', $session->id)
            ->update(['status' => 'promoted']);

        // Record attendance:
        // - Active student is present on 2026-05-10
        // - Inactive student is absent on 2026-05-10
        DB::table('attendances')->insert([
            [
                'student_id' => $activeStudent->id,
                'academic_session_id' => $session->id,
                'date' => '2026-05-10',
                'status' => 'P',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $inactiveStudent->id,
                'academic_session_id' => $session->id,
                'date' => '2026-05-10',
                'status' => 'A',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session(['selected_academic_session_id' => $session->id]);

        // Assert that the dashboard only counts the active student's attendance (1 present / 1 total = 100%)
        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['attendance'] == 100.0;
            })
            ->assertViewHas('attendanceTrend', function ($trend) {
                return count($trend) === 1 && $trend[0]['percentage'] == 100.0;
            });
    }

    public function test_dashboard_evening_shift_isolation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Dual',
            'is_active' => true,
        ]);

        $morningClass = Classes::create([
            'name' => 'Class 9A',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
        ]);

        $eveningClass = Classes::create([
            'name' => 'Class 9B',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'evening',
        ]);

        $morningStudent = Student::create([
            'name' => 'Morning Student',
            'admission_no' => 'ADM-M01',
        ]);
        Enrollment::create([
            'student_id' => $morningStudent->id,
            'class_id' => $morningClass->id,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        $eveningStudent = Student::create([
            'name' => 'Evening Student',
            'admission_no' => 'ADM-E01',
        ]);
        Enrollment::create([
            'student_id' => $eveningStudent->id,
            'class_id' => $eveningClass->id,
            'academic_session_id' => $session->id,
            'shift_type' => 'evening',
            'roll_number' => '2',
            'status' => 'active',
        ]);

        $teacher = User::create([
            'name' => 'Morning Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        DB::table('session_user')->insert([
            'user_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'is_active' => true,
            'allowed_shifts' => 'morning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session([
            'selected_academic_session_id' => $session->id,
            'selected_shift_type' => 'evening'
        ]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                // Evening dashboard should only show evening class, student, and admin user (1 user, since the teacher is morning only)
                return $stats['classes'] === 1 && $stats['students'] === 1 && $stats['users'] === 1;
            })
            ->assertViewHas('classDistribution', function ($distribution) {
                return count($distribution) === 1 && $distribution->first()->name === 'Class 9B';
            });
    }

    public function test_dashboard_both_shifts_aggregation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Dual',
            'is_active' => true,
        ]);

        $morningClass = Classes::create([
            'name' => 'Class 9A',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
        ]);

        $eveningClass = Classes::create([
            'name' => 'Class 9B',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'evening',
        ]);

        $morningStudent = Student::create([
            'name' => 'Morning Student',
            'admission_no' => 'ADM-M02',
        ]);
        Enrollment::create([
            'student_id' => $morningStudent->id,
            'class_id' => $morningClass->id,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        $eveningStudent = Student::create([
            'name' => 'Evening Student',
            'admission_no' => 'ADM-E02',
        ]);
        Enrollment::create([
            'student_id' => $eveningStudent->id,
            'class_id' => $eveningClass->id,
            'academic_session_id' => $session->id,
            'shift_type' => 'evening',
            'roll_number' => '2',
            'status' => 'active',
        ]);

        $teacher = User::create([
            'name' => 'Morning Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        DB::table('session_user')->insert([
            'user_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'is_active' => true,
            'allowed_shifts' => 'morning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session([
            'selected_academic_session_id' => $session->id,
            'selected_shift_type' => 'both'
        ]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                // "Both" dashboard should aggregate both morning and evening, including the teacher user (2 users: 1 admin + 1 teacher)
                return $stats['classes'] === 2 && $stats['students'] === 2 && $stats['users'] === 2;
            })
            ->assertViewHas('classDistribution', function ($distribution) {
                return count($distribution) === 2;
            });
    }
}
