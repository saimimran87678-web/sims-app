<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\PeriodConfig;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleShiftScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session;
    protected $morningClass;
    protected $eveningClass;
    protected $regularSession;
    protected $regularClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed license status
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'shift_type' => 'Morning & Evening',
        ]);

        $this->morningClass = Classes::create([
            'name' => 'Class 8-Morning',
            'numeric_value' => 8,
            'shift_type' => 'morning',
            'academic_session_id' => $this->session->id,
        ]);

        $this->eveningClass = Classes::create([
            'name' => 'Class 8-Evening',
            'numeric_value' => 8,
            'shift_type' => 'evening',
            'academic_session_id' => $this->session->id,
        ]);

        $this->regularSession = AcademicSession::create([
            'name' => '2026-2027-Regular',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => false,
            'shift_type' => 'Regular',
        ]);

        $this->regularClass = Classes::create([
            'name' => 'Class 8-Regular',
            'numeric_value' => 8,
            'shift_type' => 'regular',
            'academic_session_id' => $this->regularSession->id,
        ]);

        // Clear existing period configs
        DB::table('period_configs')->delete();

        // Seed period configs for morning, evening, and regular
        PeriodConfig::create([
            'period_no' => 1,
            'start_time' => '08:00',
            'end_time' => '08:40',
            'is_break' => false,
            'label' => 'Morning Period 1',
            'shift_type' => 'morning',
        ]);

        PeriodConfig::create([
            'period_no' => 1,
            'start_time' => '13:00',
            'end_time' => '13:40',
            'is_break' => false,
            'label' => 'Evening Period 1',
            'shift_type' => 'evening',
        ]);

        PeriodConfig::create([
            'period_no' => 1,
            'start_time' => '09:00',
            'end_time' => '09:40',
            'is_break' => false,
            'label' => 'Regular Period 1',
            'shift_type' => 'regular',
        ]);
    }

    public function test_period_config_manager_filters_periods_by_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\PeriodConfigManager::class)
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Morning Period 1') &&
                       !$periods->pluck('label')->contains('Evening Period 1') &&
                       !$periods->pluck('label')->contains('Regular Period 1');
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\PeriodConfigManager::class)
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Evening Period 1') &&
                       !$periods->pluck('label')->contains('Morning Period 1') &&
                       !$periods->pluck('label')->contains('Regular Period 1');
            });
    }

    public function test_period_config_manager_creates_period_for_current_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\PeriodConfigManager::class)
            ->call('openModal')
            ->set('start_time', '14:00')
            ->set('end_time', '14:40')
            ->set('label', 'Evening Period 2')
            ->call('save');

        $this->assertTrue(PeriodConfig::where('label', 'Evening Period 2')
            ->where('shift_type', 'evening')
            ->exists());
    }

    public function test_schedule_manager_filters_periods_and_classes_by_shift()
    {
        $this->actingAs($this->admin);

        // Morning Shift
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\ScheduleManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Morning Period 1') &&
                       !$periods->pluck('label')->contains('Evening Period 1');
            })
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('id')->contains($this->morningClass->id) &&
                       !$classes->pluck('id')->contains($this->eveningClass->id);
            });

        // Evening Shift
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\ScheduleManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Evening Period 1') &&
                       !$periods->pluck('label')->contains('Morning Period 1');
            })
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('id')->contains($this->eveningClass->id) &&
                       !$classes->pluck('id')->contains($this->morningClass->id);
            });
    }

    public function test_view_schedule_filters_periods_and_classes_by_shift()
    {
        $this->actingAs($this->admin);

        // Morning Shift
        session(['selected_shift_type' => 'morning']);

        // Since ViewSchedule gets active session, make $this->session active
        DB::table('academic_sessions')->update(['is_active' => false]);
        $this->session->update(['is_active' => true]);

        Livewire::test(\App\Livewire\Admin\ViewSchedule::class)
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Morning Period 1') &&
                       !$periods->pluck('label')->contains('Evening Period 1');
            })
            ->assertViewHas('classes', function ($classes) {
                return $classes->pluck('id')->contains($this->morningClass->id) &&
                       !$classes->pluck('id')->contains($this->eveningClass->id);
            });
    }

    public function test_substitution_manager_filters_teachers_by_shift()
    {
        $this->actingAs($this->admin);

        // Create two teachers
        $morningTeacher = User::factory()->create(['role' => 'teacher']);
        $eveningTeacher = User::factory()->create(['role' => 'teacher']);

        // Insert into session_user
        DB::table('session_user')->insert([
            [
                'academic_session_id' => $this->session->id,
                'user_id' => $morningTeacher->id,
                'is_active' => true,
                'allowed_shifts' => 'morning',
            ],
            [
                'academic_session_id' => $this->session->id,
                'user_id' => $eveningTeacher->id,
                'is_active' => true,
                'allowed_shifts' => 'evening',
            ]
        ]);

        // Morning Shift
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\SubstitutionManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('teachers', function ($teachers) use ($morningTeacher, $eveningTeacher) {
                return $teachers->pluck('id')->contains($morningTeacher->id) &&
                       !$teachers->pluck('id')->contains($eveningTeacher->id);
            });

        // Evening Shift
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\SubstitutionManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('teachers', function ($teachers) use ($morningTeacher, $eveningTeacher) {
                return $teachers->pluck('id')->contains($eveningTeacher->id) &&
                       !$teachers->pluck('id')->contains($morningTeacher->id);
            });
    }

    public function test_substitution_manager_attendance_isolated_by_shift()
    {
        $this->actingAs($this->admin);

        // Create a teacher that teaches in both shifts
        $bothTeacher = User::factory()->create(['role' => 'teacher']);

        DB::table('session_user')->insert([
            'academic_session_id' => $this->session->id,
            'user_id' => $bothTeacher->id,
            'is_active' => true,
            'allowed_shifts' => 'both',
        ]);

        // 1. Set shift to morning, mark teacher as Absent
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\SubstitutionManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->set('teacherStatuses.' . $bothTeacher->id, 'Absent');

        // Verify database has 'Absent' for morning shift
        $this->assertDatabaseHas('teacher_attendances', [
            'teacher_id' => $bothTeacher->id,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'morning',
            'status' => 'Absent'
        ]);

        // 2. Set shift to evening, check that teacher status defaults to 'Present' (or not absent in database/view)
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\SubstitutionManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('teacherStatuses', function ($statuses) use ($bothTeacher) {
                return ($statuses[$bothTeacher->id] ?? 'Present') === 'Present';
            });
    }

    public function test_schedule_manager_filters_teachers_by_allowed_shift()
    {
        $this->actingAs($this->admin);

        // Create two teachers
        $morningTeacher = User::factory()->create(['role' => 'teacher']);
        $eveningTeacher = User::factory()->create(['role' => 'teacher']);

        DB::table('session_user')->insert([
            [
                'academic_session_id' => $this->session->id,
                'user_id' => $morningTeacher->id,
                'is_active' => true,
                'allowed_shifts' => 'morning',
            ],
            [
                'academic_session_id' => $this->session->id,
                'user_id' => $eveningTeacher->id,
                'is_active' => true,
                'allowed_shifts' => 'evening',
            ]
        ]);

        // Morning Shift
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\ScheduleManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('teachers', function ($teachers) use ($morningTeacher, $eveningTeacher) {
                return $teachers->pluck('id')->contains($morningTeacher->id) &&
                       !$teachers->pluck('id')->contains($eveningTeacher->id);
            });

        // Evening Shift
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\ScheduleManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('teachers', function ($teachers) use ($morningTeacher, $eveningTeacher) {
                return $teachers->pluck('id')->contains($eveningTeacher->id) &&
                       !$teachers->pluck('id')->contains($morningTeacher->id);
            });
    }

    public function test_teacher_schedule_view_filters_periods_and_timetables_by_shift()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher);

        // Make session active
        DB::table('academic_sessions')->update(['is_active' => false]);
        $this->session->update(['is_active' => true]);

        // Setup class timetable entries
        $subject = \App\Models\Subject::create([
            'class_id' => $this->morningClass->id,
            'name'     => 'Morning Math',
            'code'     => 'MMATH',
        ]);

        DB::table('timetables')->insert([
            'class_id' => $this->morningClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
            'period_no' => 1,
            'is_divided' => false,
            'is_substitute' => false,
        ]);

        // Morning Shift
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Monday')
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Morning Period 1') &&
                       !$periods->pluck('label')->contains('Evening Period 1');
            })
            ->assertSet('timetables', function ($timetables) {
                return collect($timetables)->pluck('period_no')->contains(1);
            });

        // Evening Shift
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Teacher\ScheduleView::class)
            ->set('selectedDay', 'Monday')
            ->assertViewHas('periods', function ($periods) {
                return $periods->pluck('label')->contains('Evening Period 1') &&
                       !$periods->pluck('label')->contains('Morning Period 1');
            })
            ->assertSet('timetables', function ($timetables) {
                return collect($timetables)->isEmpty();
            });
    }
}
