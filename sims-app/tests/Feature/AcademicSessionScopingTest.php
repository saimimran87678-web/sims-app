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

    public function test_user_class_id_resolves_to_active_session_class_by_name(): void
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

        // Disable global scope temporarily or bypass to create class in inactive session if needed,
        // but wait, classes table insert doesn't filter, only queries filter.
        // Let's create the class for session 2.
        $classInSession2 = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $session2->id,
        ]);

        // 3. Create a teacher and assign to the class from the old session (session 1)
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'class_id' => $classInSession1->id,
        ]);

        // 4. Verify that calling class_id returns the class in the active session (session 2)
        $this->assertEquals($classInSession2->id, $teacher->class_id);

        // 5. Switch active session back to session 1
        $session2->update(['is_active' => false]);
        $session1->update(['is_active' => true]);

        // Clear user instance cache / refresh to verify it updates
        $teacher = $teacher->fresh();

        // 6. Verify that class_id now returns class in session 1
        $this->assertEquals($classInSession1->id, $teacher->class_id);
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
}
