<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSessionScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_class_id_resolves_via_session_user_pivot(): void
    {
        // 1. Create Academic Sessions
        $session1 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => false,
        ]);

        $session2 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        // 2. Create Classes with same name in both sessions
        $classInSession1 = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $session1->id,
        ]);

        $classInSession2 = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $session2->id,
        ]);

        // 3. Create a teacher
        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        // Attach to sessions via pivot with class assignments
        $teacher->academicSessions()->attach($session1->id, [
            'class_id' => $classInSession1->id,
            'class_subject' => 'Math',
            'is_active' => true,
        ]);

        $teacher->academicSessions()->attach($session2->id, [
            'class_id' => $classInSession2->id,
            'class_subject' => 'English',
            'is_active' => true,
        ]);

        // 4. Verify that getSessionClassId returns the correct class in each session
        $this->assertEquals($classInSession1->id, $teacher->getSessionClassId($session1->id));
        $this->assertEquals($classInSession2->id, $teacher->getSessionClassId($session2->id));

        // 5. Verify that getSessionClassSubject returns the correct subject in each session
        $this->assertEquals('Math', $teacher->getSessionClassSubject($session1->id));
        $this->assertEquals('English', $teacher->getSessionClassSubject($session2->id));
    }

    public function test_non_admin_user_ignores_session_store_selected_academic_session(): void
    {
        $session1 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => false,
        ]);

        $session2 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        // Mock being logged in as teacher
        $this->actingAs($teacher);

        // Put session 1 in the session store
        session(['selected_academic_session_id' => $session1->id]);

        // Verify getActiveSessionId returns session 2 (database level active) instead of session 1
        $this->assertEquals($session2->id, AcademicSession::getActiveSessionId());

        // Now mock being logged in as admin
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // Verify getActiveSessionId returns session 1 (from session store) for admin
        $this->assertEquals($session1->id, AcademicSession::getActiveSessionId());
    }
    public function test_shift_type_change_updates_enrollments(): void
    {
        $session = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
            'shift_type' => 'Regular',
        ]);

        $student = \App\Models\Student::create([
            'name' => 'Test Student',
            'admission_no' => 'ADM-X',
            'status' => 'active',
        ]);

        $class = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
        ]);

        $enrollmentId = \DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
            'roll_number' => '1',
            'status' => 'active',
        ]);

        // Mock being logged in as admin to modify the session
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        // Edit session to Dual shift type
        \Livewire\Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->call('edit', $session->id)
            ->set('shift_type', 'Dual')
            ->call('store')
            ->assertHasNoErrors();

        // Verify session was updated to Dual
        $session->refresh();
        $this->assertEquals('Dual', $session->shift_type);

        // Verify enrollment shift_type was migrated from regular to morning
        $enrollment = \DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('morning', $enrollment->shift_type);

        // Edit session back to Regular shift type
        \Livewire\Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->call('edit', $session->id)
            ->set('shift_type', 'Regular')
            ->call('store')
            ->assertHasNoErrors();

        // Verify session was updated back to Regular
        $session->refresh();
        $this->assertEquals('Regular', $session->shift_type);

        // Verify enrollment shift_type was migrated back to regular
        $enrollment = \DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('regular', $enrollment->shift_type);
    }
}

