<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\PeriodConfig;
use App\Models\AcademicSession;
use App\Models\Substitution;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Carbon\Carbon;

class TeacherScheduleArrangementTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;
    protected $absentTeacher;
    protected $otherTeacher;
    protected $academicSession;
    protected $classA;
    protected $classB;
    protected $subjectMath;
    protected $subjectPhysics;
    protected $period1;
    protected $period2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Academic Session
        $this->academicSession = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'shift_type' => 'Regular',
        ]);

        // 2. Create Users
        $this->teacher = User::factory()->create(['name' => 'Mr. Main Teacher', 'role' => 'teacher']);
        $this->absentTeacher = User::factory()->create(['name' => 'Mr. Absent Teacher', 'role' => 'teacher']);
        $this->otherTeacher = User::factory()->create(['name' => 'Ms. Substitute Teacher', 'role' => 'teacher']);

        DB::table('session_user')->insert([
            ['academic_session_id' => $this->academicSession->id, 'user_id' => $this->teacher->id, 'is_active' => true, 'allowed_shifts' => 'regular'],
            ['academic_session_id' => $this->academicSession->id, 'user_id' => $this->absentTeacher->id, 'is_active' => true, 'allowed_shifts' => 'regular'],
            ['academic_session_id' => $this->academicSession->id, 'user_id' => $this->otherTeacher->id, 'is_active' => true, 'allowed_shifts' => 'regular'],
        ]);

        // 3. Create Classes
        $this->classA = Classes::create([
            'academic_session_id' => $this->academicSession->id,
            'name' => 'Grade 9-A',
            'numeric_value' => 9,
            'shift_type' => 'regular',
        ]);

        $this->classB = Classes::create([
            'academic_session_id' => $this->academicSession->id,
            'name' => 'Grade 10-B',
            'numeric_value' => 10,
            'shift_type' => 'regular',
        ]);

        // 4. Create Subjects
        $this->subjectMath = Subject::create([
            'class_id' => $this->classA->id,
            'name' => 'Mathematics',
            'code' => 'MATH9',
        ]);

        $this->subjectPhysics = Subject::create([
            'class_id' => $this->classB->id,
            'name' => 'Physics',
            'code' => 'PHY10',
        ]);

        // 5. Create or Update Periods
        $this->period1 = PeriodConfig::updateOrCreate(
            ['period_no' => 1, 'shift_type' => 'regular'],
            [
                'label' => 'Period 1',
                'start_time' => '08:00:00',
                'end_time' => '08:45:00',
                'is_break' => false,
                'is_assembly' => false,
            ]
        );

        $this->period2 = PeriodConfig::updateOrCreate(
            ['period_no' => 2, 'shift_type' => 'regular'],
            [
                'label' => 'Period 2',
                'start_time' => '08:45:00',
                'end_time' => '09:30:00',
                'is_break' => false,
                'is_assembly' => false,
            ]
        );

        session(['selected_shift_type' => 'regular']);
    }

    public function test_universal_single_schedule_loads_monday_template_for_all_days()
    {
        $this->actingAs($this->teacher);

        // Configure schedule_type as single_schedule
        Setting::set('schedule_type', 'single_schedule');

        // Insert timetable under 'Monday'
        DB::table('timetables')->insert([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'room' => 'Room 101',
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        // When teacher views Tuesday in single_schedule mode, Period 1 should be loaded from Monday's universal schedule
        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Tuesday')
            ->assertSet('scheduleType', 'single_schedule')
            ->assertSee('Universal Schedule')
            ->assertSee('Grade 9-A')
            ->assertSee('Mathematics')
            ->assertSee('Room 101');
    }

    public function test_admin_override_causes_main_period_to_be_crossed_and_shows_arrangement()
    {
        $this->actingAs($this->teacher);
        Setting::set('schedule_type', 'day_wise');

        // Teacher has regular Math in Period 1 on Monday
        DB::table('timetables')->insert([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'room' => 'Room 101',
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        // Compute Monday's date of the current week
        $mondayDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        // Admin assigns teacher as substitute for Period 1 on that date (Override!)
        Substitution::create([
            'academic_session_id' => $this->academicSession->id,
            'shift_type' => 'regular',
            'date' => $mondayDate,
            'class_id' => $this->classB->id,
            'period_no' => 1,
            'subject_id' => $this->subjectPhysics->id,
            'absent_teacher_id' => $this->absentTeacher->id,
            'substitute_teacher_id' => $this->teacher->id,
            'status' => 'assigned',
        ]);

        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Monday')
            ->assertSee('Overridden')
            ->assertSee('line-through', false)
            ->assertSee('Grade 9-A')
            ->assertSee('Grade 10-B')
            ->assertSee('Physics')
            ->assertSee('Mr. Absent Teacher')
            ->assertSee('Arrangement (Override)');
    }

    public function test_arrangement_on_free_period_shows_arrangement_duty()
    {
        $this->actingAs($this->teacher);
        Setting::set('schedule_type', 'day_wise');

        // Teacher is free in Period 2. Admin assigns an arrangement.
        $mondayDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        Substitution::create([
            'academic_session_id' => $this->academicSession->id,
            'shift_type' => 'regular',
            'date' => $mondayDate,
            'class_id' => $this->classB->id,
            'period_no' => 2,
            'subject_id' => $this->subjectPhysics->id,
            'absent_teacher_id' => $this->absentTeacher->id,
            'substitute_teacher_id' => $this->teacher->id,
            'status' => 'assigned',
        ]);

        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Monday')
            ->assertSee('Arrangement Duty')
            ->assertSee('Grade 10-B')
            ->assertSee('Physics')
            ->assertSee('Mr. Absent Teacher');
    }

    public function test_relieved_period_shows_regular_period_crossed_and_relieved_badge()
    {
        $this->actingAs($this->teacher);
        Setting::set('schedule_type', 'day_wise');

        // Teacher has regular Math in Period 1
        DB::table('timetables')->insert([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'room' => 'Room 101',
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        $mondayDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        // Another teacher covers for this teacher
        Substitution::create([
            'academic_session_id' => $this->academicSession->id,
            'shift_type' => 'regular',
            'date' => $mondayDate,
            'class_id' => $this->classA->id,
            'period_no' => 1,
            'subject_id' => $this->subjectMath->id,
            'absent_teacher_id' => $this->teacher->id,
            'substitute_teacher_id' => $this->otherTeacher->id,
            'status' => 'assigned',
        ]);

        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Monday')
            ->assertSee('Relieved')
            ->assertSee('Ms. Substitute Teacher');
    }

    public function test_teacher_dashboard_shows_overridden_regular_period_and_arrangement()
    {
        $this->actingAs($this->teacher);
        Setting::set('schedule_type', 'day_wise');

        $dayOfWeek = now()->format('l');
        $todayDate = now()->format('Y-m-d');

        // Teacher has regular Math in Period 1 today
        DB::table('timetables')->insert([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher->id,
            'day' => $dayOfWeek,
            'period_no' => 1,
            'room' => 'Room 101',
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        // Admin assigns teacher as substitute for Period 1 today (Override)
        Substitution::create([
            'academic_session_id' => $this->academicSession->id,
            'shift_type' => 'regular',
            'date' => $todayDate,
            'class_id' => $this->classB->id,
            'period_no' => 1,
            'subject_id' => $this->subjectPhysics->id,
            'absent_teacher_id' => $this->absentTeacher->id,
            'substitute_teacher_id' => $this->teacher->id,
            'status' => 'assigned',
        ]);

        Livewire::test(\App\Livewire\Teacher\Dashboard::class)
            ->assertSee('Overridden')
            ->assertSee('line-through', false)
            ->assertSee('Mathematics')
            ->assertSee('Arrangement Override')
            ->assertSee('Grade 10-B')
            ->assertSee('Physics');
    }

    public function test_teacher_dashboard_shows_universal_schedule_for_any_day()
    {
        $this->actingAs($this->teacher);
        Setting::set('schedule_type', 'single_schedule');

        // Insert timetable under Monday (template)
        DB::table('timetables')->insert([
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'room' => 'Room 101',
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        // Dashboard on any weekday should load Monday's universal schedule
        Livewire::test(\App\Livewire\Teacher\Dashboard::class)
            ->assertSee('Mathematics')
            ->assertSee('Grade 9-A');
    }
}
