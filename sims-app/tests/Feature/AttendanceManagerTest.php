<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Holiday;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Livewire\Teacher\AttendanceManager;
use Livewire\Livewire;
use Carbon\Carbon;

class AttendanceManagerTest extends TestCase
{
    use RefreshDatabase;

    private $teacher;
    private $class;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed license status
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(200)->format('Y-m-d'),
            'is_active' => true,
            'shift_type' => 'Shifted',
        ]);

        $this->class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'morning',
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

        session([
            'current_session_id' => $this->session->id,
            'selected_academic_session_id' => $this->session->id,
            'selected_shift_type' => 'morning',
        ]);

        $this->actingAs($this->teacher);
    }

    public function test_load_attendance_renders_correctly_with_roll_number(): void
    {
        // Create student (creates enrollment automatically)
        $student = Student::create([
            'name' => 'John Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $this->class->id,
        ]);

        // Component should load successfully and contain student name
        Livewire::test(AttendanceManager::class)
            ->assertOk()
            ->assertViewHas('students', function ($students) use ($student) {
                return collect($students)->contains('id', $student->id);
            });
    }

    public function test_missed_attendance_dates_logic(): void
    {
        session(['selected_shift_type' => 'morning']);
        
        // Define weekend mode: Sunday only
        Setting::set('weekend_mode', 'sun_only');

        // Create student
        $student = Student::create([
            'name' => 'John Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $this->class->id,
        ]);

        // Let's create a holiday 2 days ago
        $holidayDate = Carbon::today()->subDays(2);
        Holiday::create([
            'name' => 'Special Holiday',
            'start_date' => $holidayDate->format('Y-m-d'),
            'end_date' => $holidayDate->format('Y-m-d'),
            'academic_session_id' => $this->session->id,
            'shift_type' => 'morning',
            'reason' => 'Holiday',
        ]);

        // Let's mark attendance for 3 days ago
        $markedDate = Carbon::today()->subDays(3);
        
        // If 3 days ago is a Sunday, let's pick 4 days ago to be sure we can mark it
        if ($markedDate->isSunday()) {
            $markedDate = Carbon::today()->subDays(4);
        }

        DB::table('attendances')->insert([
            'student_id' => $student->id,
            'academic_session_id' => $this->session->id,
            'date' => $markedDate->format('Y-m-d'),
            'status' => 'P',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test the component
        $component = Livewire::test(AttendanceManager::class);

        $missedDates = $component->get('missedDates');

        // The holiday date should NOT be in missed dates
        $this->assertNotContains($holidayDate->format('Y-m-d'), $missedDates);

        // The marked date should NOT be in missed dates
        $this->assertNotContains($markedDate->format('Y-m-d'), $missedDates);

        // Sundays should NOT be in missed dates
        foreach ($missedDates as $date) {
            $this->assertFalse(Carbon::parse($date)->isSunday());
        }

        // A regular working day in the range (e.g. yesterday, if not Sunday or Holiday) with no attendance should be in missed dates
        $yesterday = Carbon::yesterday();
        if (!$yesterday->isSunday() && $yesterday->format('Y-m-d') !== $holidayDate->format('Y-m-d')) {
            $this->assertContains($yesterday->format('Y-m-d'), $missedDates);
        }
    }

    public function test_select_date_updates_date_and_loads_attendance(): void
    {
        session(['selected_shift_type' => 'morning']);

        $student = Student::create([
            'name' => 'John Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-001',
            'class_id' => $this->class->id,
        ]);

        $testDate = Carbon::today()->subDays(2)->format('Y-m-d');

        Livewire::test(AttendanceManager::class)
            ->call('selectDate', $testDate)
            ->assertSet('date', $testDate);
    }

    public function test_admin_attendance_manager_renders_correctly_with_calendar_grid(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session([
            'current_session_id' => $this->session->id,
            'selected_academic_session_id' => $this->session->id,
            'selected_shift_type' => 'morning',
        ]);

        $student = Student::create([
            'name' => 'Jane Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-002',
            'class_id' => $this->class->id,
        ]);

        Livewire::test(\App\Livewire\Admin\AttendanceManager::class)
            ->set('selectedClassId', $this->class->id)
            ->assertOk()
            ->assertViewHas('calendarDays', function ($days) {
                return !empty($days);
            });
    }

    public function test_admin_attendance_manager_select_date_updates_state(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session([
            'current_session_id' => $this->session->id,
            'selected_academic_session_id' => $this->session->id,
            'selected_shift_type' => 'morning',
        ]);

        $student = Student::create([
            'name' => 'Jane Doe',
            'roll_no' => '1',
            'admission_no' => 'ADM-002',
            'class_id' => $this->class->id,
        ]);

        $testDate = Carbon::today()->subDays(3)->format('Y-m-d');

        Livewire::test(\App\Livewire\Admin\AttendanceManager::class)
            ->set('selectedClassId', $this->class->id)
            ->call('selectDate', $testDate)
            ->assertSet('date', $testDate);
    }

    public function test_admin_attendance_manager_holidays_are_shift_scoped(): void
    {
        $this->session->update(['shift_type' => 'Shifted']);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        session([
            'current_session_id' => $this->session->id,
            'selected_academic_session_id' => $this->session->id,
            'selected_shift_type' => 'morning',
        ]);

        $holidayDate = Carbon::today()->addDays(5)->format('Y-m-d');

        // Create morning holiday
        Livewire::test(\App\Livewire\Admin\AttendanceManager::class)
            ->set('holidayStart', $holidayDate)
            ->set('holidayReason', 'Morning Holiday')
            ->call('saveHoliday');

        $this->assertDatabaseHas('holidays', [
            'academic_session_id' => $this->session->id,
            'shift_type' => 'morning',
            'start_date' => $holidayDate . ' 00:00:00',
            'reason' => 'Morning Holiday',
        ]);

        // Switch shift to evening
        session([
            'selected_shift_type' => 'evening',
        ]);

        // Holiday should not be listed in holidaysList
        Livewire::test(\App\Livewire\Admin\AttendanceManager::class)
            ->assertViewHas('holidaysList', function ($list) {
                return empty($list);
            });

        // Create evening holiday
        Livewire::test(\App\Livewire\Admin\AttendanceManager::class)
            ->set('holidayStart', $holidayDate)
            ->set('holidayReason', 'Evening Holiday')
            ->call('saveHoliday');

        $this->assertDatabaseHas('holidays', [
            'academic_session_id' => $this->session->id,
            'shift_type' => 'evening',
            'start_date' => $holidayDate . ' 00:00:00',
            'reason' => 'Evening Holiday',
        ]);
    }
}
