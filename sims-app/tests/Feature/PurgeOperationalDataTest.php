<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurgeOperationalDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);
        $subject = Subject::where('code', 'PHY')->first() ?? Subject::create(['name' => 'Physics', 'code' => 'PHY', 'category' => 'Science', 'is_active' => true]);

        // Insert dummy operational records
        DB::table('timetables')->insert([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'period_no' => 1,
            'teacher_id' => $user->id,
            'room' => '10A',
        ]);

        $this->artisan('db:purge-operational-data', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertEquals(1, DB::table('timetables')->count());
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_purge_clears_operational_records_and_preserves_core_entities(): void
    {
        // 1. Setup core records
        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'regular',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);
        $subject = Subject::where('code', 'PHY')->first() ?? Subject::create(['name' => 'Physics', 'code' => 'PHY', 'category' => 'Science', 'is_active' => true]);
        $program = Program::create(['name' => 'Science Track', 'code' => 'SCI-01', 'level' => 'Matric']);

        // 2. Insert operational records
        DB::table('timetables')->insert([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'period_no' => 1,
            'teacher_id' => $user->id,
            'room' => '10A',
        ]);

        DB::table('subject_allocations')->insert([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $examId = DB::table('exams')->insertGetId([
            'name' => 'Midterm Exam',
            'academic_session_id' => $session->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-15',
        ]);

        DB::table('marks_configs')->insert([
            'exam_id' => $examId,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => 'Physics',
            'total_marks' => 100,
            'passing_marks' => 33,
        ]);

        DB::table('activity_logs')->insert([
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'User logged in',
            'created_at' => now(),
        ]);

        // Setup teacher class assignments
        $user->update(['class_id' => $class->id, 'class_subject' => 'Physics']);
        DB::table('session_user')->insert([
            'user_id' => $user->id,
            'academic_session_id' => $session->id,
            'class_id' => $class->id,
            'class_subject' => 'Physics',
            'is_active' => true,
        ]);

        $this->assertEquals(1, DB::table('timetables')->count());
        $this->assertEquals(1, DB::table('subject_allocations')->count());
        $this->assertEquals(1, DB::table('exams')->count());
        $this->assertEquals(1, DB::table('marks_configs')->count());
        $this->assertGreaterThan(0, DB::table('activity_logs')->count());
        $this->assertEquals(1, DB::table('users')->whereNotNull('class_id')->count());
        $this->assertEquals(1, DB::table('session_user')->whereNotNull('class_id')->count());

        // 3. Run purge command with --force
        $this->artisan('db:purge-operational-data', ['--force' => true])
            ->assertExitCode(0);

        // 4. Verify operational tables are completely emptied
        $this->assertEquals(0, DB::table('timetables')->count(), 'Timetables must be 0');
        $this->assertEquals(0, DB::table('subject_allocations')->count(), 'Subject allocations must be 0');
        $this->assertEquals(0, DB::table('exams')->count(), 'Exams must be 0');
        $this->assertEquals(0, DB::table('marks_configs')->count(), 'Marks configs must be 0');
        $this->assertEquals(0, DB::table('activity_logs')->count(), 'Activity logs must be 0');

        // Verify Class Teacher assignments were reset to null in canonical session_user
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'class_id'), 'Users table has no class_id column');
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'class_subject'), 'Users table has no class_subject column');
        $this->assertEquals(0, DB::table('session_user')->whereNotNull('class_id')->count(), 'Session user class_id must be null');
        $this->assertEquals(0, DB::table('session_user')->whereNotNull('class_subject')->count(), 'Session user class_subject must be null');
        $this->assertEquals(0, DB::table('subjects')->whereNotNull('class_id')->count(), 'Subjects legacy class_id must be null');

        // 5. Verify core entities are strictly preserved
        $this->assertDatabaseHas('academic_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('classes', ['id' => $class->id]);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
        $this->assertDatabaseHas('programs', ['id' => $program->id]);

        // 6. Verify zero foreign key violations
        $violations = DB::select('PRAGMA foreign_key_check');
        $this->assertEmpty($violations, 'Foreign key violations must be empty');
    }

    public function test_schedule_manager_clear_all_resets_class_teachers_and_allocations(): void
    {
        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $teacher = User::factory()->create(['role' => 'teacher']);

        $class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);
        $subject = Subject::where('code', 'PHY')->first() ?? Subject::create(['name' => 'Physics', 'code' => 'PHY', 'category' => 'Science', 'is_active' => true]);

        // Assign Class Teacher in session_user and users
        $teacher->update(['class_id' => $class->id, 'class_subject' => 'Physics']);
        DB::table('session_user')->insert([
            'user_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'class_id' => $class->id,
            'class_subject' => 'Physics',
            'is_active' => true,
        ]);

        // Insert timetable entry
        DB::table('timetables')->insert([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'period_no' => 1,
            'teacher_id' => $teacher->id,
            'room' => '10A',
        ]);

        $this->actingAs($admin);
        session(['selected_academic_session_id' => $session->id]);

        \Livewire\Livewire::test(\App\Livewire\Admin\ScheduleManager::class)
            ->set('selectedSessionId', $session->id)
            ->call('clearAllSchedule');

        $this->assertEquals(0, DB::table('timetables')->count(), 'Timetables must be 0 after clearAllSchedule');
        $this->assertEquals(0, DB::table('session_user')->whereNotNull('class_id')->count(), 'Session user class_id must be null');
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'class_id'), 'Users table has no class_id column');
    }
}
