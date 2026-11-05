<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\SessionUser;
use App\Models\Student;
use App\Models\Substitution;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SingleSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    /**
     * Area 1: Teacher's Class Teacher & Subject Assignment Single Source of Truth
     */
    public function test_area1_session_user_is_canonical_truth_and_users_table_has_no_class_columns()
    {
        // 1. Schema verification: users table has NO class_id or class_subject
        $this->assertFalse(
            Schema::hasColumn('users', 'class_id'),
            'users table must NOT contain legacy class_id column'
        );
        $this->assertFalse(
            Schema::hasColumn('users', 'class_subject'),
            'users table must NOT contain legacy class_subject column'
        );

        // 2. Setup Sessions and Classes
        $session1 = AcademicSession::firstOrCreate(
            ['name' => 'Session 2025-2026'],
            ['start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => true]
        );
        $session2 = AcademicSession::firstOrCreate(
            ['name' => 'Session 2026-2027'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => false]
        );

        $class1 = Classes::create([
            'name' => 'Grade 5-A',
            'numeric_value' => 5,
            'academic_session_id' => $session1->id,
        ]);
        $class2 = Classes::create([
            'name' => 'Grade 6-B',
            'numeric_value' => 6,
            'academic_session_id' => $session2->id,
        ]);

        $teacher = User::create([
            'name' => 'Mr. Ahmed',
            'email' => 'ahmed.single_truth@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'teacher',
        ]);

        // 3. Assign teacher as class teacher in Session 1
        SessionUser::updateOrCreate(
            ['academic_session_id' => $session1->id, 'user_id' => $teacher->id],
            ['class_id' => $class1->id]
        );

        // Set active session in runtime
        AcademicSession::flushActiveSessionMemo();
        session(['selected_session_id' => $session1->id]);

        // Accessor & relationship checks
        $freshTeacher = $teacher->fresh();
        $this->assertEquals($class1->id, $freshTeacher->class_id);
        $this->assertNotNull($freshTeacher->class);
        $this->assertEquals('Grade 5-A', $freshTeacher->class->name);

        // 4. Switch session to Session 2 - verify assignment does NOT bleed across sessions
        AcademicSession::flushActiveSessionMemo();
        session(['selected_session_id' => $session2->id]);
        $freshTeacher = $teacher->fresh();
        $this->assertNull($freshTeacher->class_id);
        $this->assertNull($freshTeacher->class);

        // Assign teacher as class teacher in Session 2 to a different class
        SessionUser::updateOrCreate(
            ['academic_session_id' => $session2->id, 'user_id' => $teacher->id],
            ['class_id' => $class2->id]
        );

        $freshTeacher = $teacher->fresh();
        $this->assertEquals($class2->id, $freshTeacher->class_id);
        $this->assertEquals('Grade 6-B', $freshTeacher->class->name);
    }

    /**
     * Area 2: User Roles Synchronization Parity (users.role <-> Spatie RBAC)
     */
    public function test_area2_user_role_automatically_syncs_with_spatie_rbac_in_lockstep()
    {
        // 1. Create teacher user
        $user = User::create([
            'name' => 'Farhan Khan',
            'email' => 'farhan.sync@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'teacher',
        ]);

        // Model event booted() should have automatically granted Spatie 'Teacher' role
        $this->assertTrue($user->hasRole('Teacher'));
        $this->assertTrue($user->isTeacher());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isSuperAdmin());

        // 2. Promote to admin
        $user->update(['role' => 'admin']);
        $user->refresh();

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Teacher'));
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isTeacher());

        // 3. Promote to superadmin
        $user->update(['role' => 'superadmin']);
        $user->refresh();

        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin()); // Superadmins are also admins conceptually
        $this->assertFalse($user->hasRole('Admin'));
    }

    /**
     * Area 3: Substitutions vs Weekly Timetable Separation
     */
    public function test_area3_timetables_has_no_substitute_columns_and_substitutions_table_is_isolated()
    {
        // 1. Schema check: timetables table has NO is_substitute or substitute_date
        $this->assertFalse(
            Schema::hasColumn('timetables', 'is_substitute'),
            'timetables table must NOT contain legacy is_substitute column'
        );
        $this->assertFalse(
            Schema::hasColumn('timetables', 'substitute_date'),
            'timetables table must NOT contain legacy substitute_date column'
        );

        $session = AcademicSession::firstOrCreate(
            ['name' => 'Session Routine 2026'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]
        );

        $class = Classes::create([
            'name' => 'Class 7-C',
            'numeric_value' => 7,
            'academic_session_id' => $session->id,
        ]);

        $originalTeacher = User::create([
            'name' => 'Mr. Original',
            'email' => 'orig.teacher@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'teacher',
        ]);

        $subTeacher = User::create([
            'name' => 'Ms. Substitute',
            'email' => 'sub.teacher@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'teacher',
        ]);

        $subject = \App\Models\Subject::firstOrCreate(
            ['name' => 'Timetable Math ' . uniqid()],
            ['code' => 'MATH-7', 'class_id' => $class->id]
        );

        // 2. Create master recurring timetable entry
        $masterEntry = Timetable::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $originalTeacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
        ]);

        $initialTimetableCount = Timetable::count();

        // 3. Assign a date-specific substitute teacher
        $subRecord = Substitution::create([
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
            'date' => '2026-11-03',
            'absent_teacher_id' => $originalTeacher->id,
            'substitute_teacher_id' => $subTeacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'period_no' => 1,
            'status' => 'assigned',
        ]);

        // Timetable count must NOT change (no temporary substitute rows injected)
        $this->assertEquals(
            $initialTimetableCount,
            Timetable::count(),
            'Timetables table count must remain constant; daily covers go to substitutions table only'
        );

        // Master timetable entry for original teacher remains untouched
        $masterFresh = $masterEntry->fresh();
        $this->assertEquals($originalTeacher->id, $masterFresh->teacher_id);

        // Verification query for active schedule is clean without needing ->where('is_substitute', false)
        $mondaySchedules = Timetable::where('day', 'Monday')
            ->where('class_id', $class->id)
            ->get();
        $this->assertCount(1, $mondaySchedules);
        $this->assertEquals($originalTeacher->id, $mondaySchedules->first()->teacher_id);
    }

    /**
     * Area 4: Student Section & Class Model Placed on Enrollments
     */
    public function test_area4_section_is_on_enrollments_not_students_and_session_scoped()
    {
        // 1. Schema check: students has NO section_id, enrollments HAS section_id
        $this->assertFalse(
            Schema::hasColumn('students', 'section_id'),
            'students table must NOT contain legacy section_id column'
        );
        $this->assertTrue(
            Schema::hasColumn('enrollments', 'section_id'),
            'enrollments table MUST contain section_id column'
        );

        // 2. Create Sessions and Sections
        $session2025 = AcademicSession::firstOrCreate(
            ['name' => 'Academic Year 2025'],
            ['start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => false]
        );
        $session2026 = AcademicSession::firstOrCreate(
            ['name' => 'Academic Year 2026'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]
        );

        $class2025 = Classes::create(['name' => 'Class 9', 'numeric_value' => 9, 'academic_session_id' => $session2025->id]);
        $class2026 = Classes::create(['name' => 'Class 10', 'numeric_value' => 10, 'academic_session_id' => $session2026->id]);

        $sectionAlpha = Section::create(['name' => 'Alpha', 'class_id' => $class2025->id]);
        $sectionBeta = Section::create(['name' => 'Beta', 'class_id' => $class2026->id]);

        // 3. Create Student
        $student = Student::create([
            'name' => 'Kamran Tariq',
            'admission_no' => 'ADM-TRUTH-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // 4. Enroll in 2025 with Section Alpha
        $enrollment2025 = Enrollment::create([
            'student_id' => $student->id,
            'academic_session_id' => $session2025->id,
            'class_id' => $class2025->id,
            'section_id' => $sectionAlpha->id,
            'roll_number' => '101',
            'shift_type' => 'regular',
            'status' => 'completed',
        ]);

        // 5. Enroll in 2026 with Section Beta
        $enrollment2026 = Enrollment::create([
            'student_id' => $student->id,
            'academic_session_id' => $session2026->id,
            'class_id' => $class2026->id,
            'section_id' => $sectionBeta->id,
            'roll_number' => '102',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        // When viewing under Session 2026
        AcademicSession::flushActiveSessionMemo();
        session(['selected_session_id' => $session2026->id]);
        $freshStudent = $student->fresh();

        $this->assertEquals($sectionBeta->id, $freshStudent->section_id);
        $this->assertNotNull($freshStudent->section);
        $this->assertEquals('Beta', $freshStudent->section->name);

        // When viewing under Session 2025
        AcademicSession::flushActiveSessionMemo();
        session(['selected_session_id' => $session2025->id]);
        $freshStudent = $student->fresh();

        $this->assertEquals($sectionAlpha->id, $freshStudent->section_id);
        $this->assertNotNull($freshStudent->section);
        $this->assertEquals('Alpha', $freshStudent->section->name);

        // 6. Test Livewire StudentManager saves section_id to enrollment
        $admin = User::firstOrCreate(
            ['email' => 'admin.test@test.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $admin->syncRoles(['Super Admin']);
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $session2026->id)
            ->set('name', 'Noman Ali')
            ->set('admission_no', 'ADM-TRUTH-02')
            ->set('gender', 'Male')
            ->set('student_shift', 'regular')
            ->set('student_shift_option', 'regular')
            ->set('admission_date', '2026-01-01')
            ->set('transport_mode', 'none')
            ->set('class_id', $class2026->id)
            ->set('section_id', $sectionBeta->id)
            ->set('roll_no', '105')
            ->call('save')
            ->assertHasNoErrors();

        $newStudent = Student::where('admission_no', 'ADM-TRUTH-02')->first();
        $this->assertNotNull($newStudent);

        $savedEnrollment = Enrollment::where('student_id', $newStudent->id)
            ->where('academic_session_id', $session2026->id)
            ->first();

        $this->assertNotNull($savedEnrollment);
        $this->assertEquals($sectionBeta->id, $savedEnrollment->section_id);
    }
}
