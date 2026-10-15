<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\SubstitutionManager;
use Carbon\Carbon;

class SubstitutionManagerTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $session;
    private User $admin;
    private User $absentTeacher;
    private User $substituteTeacher;
    private Classes $class;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Active Academic Session
        $this->session = AcademicSession::create([
            'name'       => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date'   => '2027-03-31',
            'is_active'  => true,
        ]);

        // Admin user
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');

        // Absent Teacher
        $this->absentTeacher = User::factory()->create([
            'name' => 'Absent Teacher',
            'role' => 'teacher',
        ]);

        // Substitute Teacher
        $this->substituteTeacher = User::factory()->create([
            'name' => 'Substitute Teacher',
            'role' => 'teacher',
        ]);

        // Enroll teachers in session
        foreach ([$this->absentTeacher, $this->substituteTeacher] as $teacher) {
            DB::table('session_user')->insert([
                'user_id'             => $teacher->id,
                'academic_session_id' => $this->session->id,
                'is_active'           => true,
                'is_primary'          => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // Create Class
        $this->class = Classes::create([
            'name' => 'Class 10A',
            'numeric_value' => 10,
            'academic_session_id' => $this->session->id,
            'shift_type' => 'regular',
        ]);

        // Create Subject
        $this->subject = Subject::create([
            'class_id' => $this->class->id,
            'name'     => 'Mathematics',
            'code'     => 'MATH101',
        ]);
    }

    #[Test]
    public function it_displays_assigned_substitute_periods_alongside_teacher_name_in_options()
    {
        $this->actingAs($this->admin);

        $today = now()->format('Y-m-d');
        $dayOfWeek = now()->format('l');

        // Create a regular timetable entry for the absent teacher
        DB::table('timetables')->insert([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->absentTeacher->id,
            'day' => $dayOfWeek,
            'period_no' => 2,
            'is_divided' => false,
            'is_substitute' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Initialize component, mark absentTeacher as Absent
        $comp = Livewire::test(SubstitutionManager::class)
            ->set('selectedDate', $today)
            ->set('selectedSessionId', $this->session->id);

        // Simulate absent status
        DB::table('teacher_attendances')->insert([
            'teacher_id' => $this->absentTeacher->id,
            'date' => $today,
            'status' => 'Absent',
            'academic_session_id' => $this->session->id,
            'shift_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reload data to reflect attendance status
        $comp->call('loadData');

        // Assert substituteTeacher starts with no assigned substitute periods
        $comp->assertSet('teacherAssignedSubs', []);

        // Now, assign substituteTeacher to Period 2
        $comp->set('substitutions.' . $this->absentTeacher->id . '.2', $this->substituteTeacher->id)
            ->call('assignSubstitute', $this->absentTeacher->id, 2, $this->class->id, $this->subject->id);

        // Verify the database has the substitution record
        $this->assertTrue(
            DB::table('timetables')
                ->where('substitute_date', $today)
                ->where('is_substitute', true)
                ->where('teacher_id', $this->substituteTeacher->id)
                ->where('period_no', 2)
                ->exists()
        );

        // Verify component state has loaded the assigned period
        $comp->assertSet('teacherAssignedSubs', [
            $this->substituteTeacher->id => [
                [
                    'period_no' => 2,
                    'class_name' => 'Class 10A',
                ]
            ]
        ]);

        // After 1 substitution, the substitute teacher has 1 sub today
        $comp->assertSee('Substitute Teacher');
        $comp->assertSee('1 sub today');

        // Now, assign another substitution for Period 4
        // Create another timetable entry for absent teacher on Period 4
        DB::table('timetables')->insert([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->absentTeacher->id,
            'day' => $dayOfWeek,
            'period_no' => 4,
            'is_divided' => false,
            'is_substitute' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $comp->call('loadData');

        $comp->set('substitutions.' . $this->absentTeacher->id . '.4', $this->substituteTeacher->id)
            ->call('assignSubstitute', $this->absentTeacher->id, 4, $this->class->id, $this->subject->id);

        // Verify component state has both assigned periods
        $comp->assertSet('teacherAssignedSubs', [
            $this->substituteTeacher->id => [
                [
                    'period_no' => 2,
                    'class_name' => 'Class 10A',
                ],
                [
                    'period_no' => 4,
                    'class_name' => 'Class 10A',
                ]
            ]
        ]);

        // With 2 substitutions, the workload badge shows 2 subs today
        $comp->assertSee('Substitute Teacher');
        $comp->assertSee('2 subs today');

        // Remove the substitution for Period 2
        $comp->set('substitutions.' . $this->absentTeacher->id . '.2', '')
            ->call('assignSubstitute', $this->absentTeacher->id, 2, $this->class->id, $this->subject->id);

        // Verify component state is updated
        $comp->assertSet('teacherAssignedSubs', [
            $this->substituteTeacher->id => [
                [
                    'period_no' => 4,
                    'class_name' => 'Class 10A',
                ]
            ]
        ]);

        // After removing P2, 1 sub remains today => "1 sub today"
        $comp->assertSee('1 sub today');
        $comp->assertDontSee('2 subs today');
    }

    #[Test]
    public function it_excludes_teachers_with_non_present_statuses_from_available_substitutes()
    {
        $this->actingAs($this->admin);

        $today = now()->format('Y-m-d');

        // Create teachers with various non-present statuses
        $leaveTeacher = User::factory()->create(['name' => 'Leave Teacher', 'role' => 'teacher']);
        $odTeacher = User::factory()->create(['name' => 'OD Teacher', 'role' => 'teacher']);
        $shortLeaveTeacher = User::factory()->create(['name' => 'Short Leave Teacher', 'role' => 'teacher']);
        $presentTeacher = User::factory()->create(['name' => 'Present Teacher', 'role' => 'teacher']);

        foreach ([$leaveTeacher, $odTeacher, $shortLeaveTeacher, $presentTeacher] as $t) {
            DB::table('session_user')->insert([
                'user_id'             => $t->id,
                'academic_session_id' => $this->session->id,
                'is_active'           => true,
                'is_primary'          => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        $comp = Livewire::test(SubstitutionManager::class)
            ->set('selectedDate', $today)
            ->set('selectedSessionId', $this->session->id);

        // Mark statuses
        $comp->set('teacherStatuses.' . $this->absentTeacher->id, 'Absent');
        $comp->set('teacherStatuses.' . $leaveTeacher->id, 'Leave');
        $comp->set('teacherStatuses.' . $odTeacher->id, 'Official Duty');
        $comp->set('teacherStatuses.' . $shortLeaveTeacher->id, 'Short Leave');
        $comp->set('teacherStatuses.' . $presentTeacher->id, 'Present');

        $instance = $comp->instance();

        // 1. Check getUnavailableTeacherIds contains all non-present teachers
        $unavailableIds = $instance->getUnavailableTeacherIds();
        $this->assertContains($this->absentTeacher->id, $unavailableIds);
        $this->assertContains($leaveTeacher->id, $unavailableIds);
        $this->assertContains($odTeacher->id, $unavailableIds);
        $this->assertContains($shortLeaveTeacher->id, $unavailableIds);
        $this->assertNotContains($presentTeacher->id, $unavailableIds);

        // 2. Check getAvailableTeachersForPeriod strictly excludes unavailable teachers (even with showAll = true)
        $availableNormal = $instance->getAvailableTeachersForPeriod(1, null, $this->class->id, $this->absentTeacher->id, false);
        $availableShowAll = $instance->getAvailableTeachersForPeriod(1, null, $this->class->id, $this->absentTeacher->id, true);

        $normalIds = $availableNormal->pluck('id')->all();
        $showAllIds = $availableShowAll->pluck('id')->all();

        // Absent teacher can never substitute
        $this->assertNotContains($this->absentTeacher->id, $normalIds);
        $this->assertNotContains($this->absentTeacher->id, $showAllIds);

        // Leave, Official Duty, Short Leave teachers can never substitute
        $this->assertNotContains($leaveTeacher->id, $normalIds);
        $this->assertNotContains($leaveTeacher->id, $showAllIds);
        $this->assertNotContains($odTeacher->id, $normalIds);
        $this->assertNotContains($odTeacher->id, $showAllIds);
        $this->assertNotContains($shortLeaveTeacher->id, $normalIds);
        $this->assertNotContains($shortLeaveTeacher->id, $showAllIds);

        // Present teacher is available
        $this->assertContains($presentTeacher->id, $normalIds);
        $this->assertContains($presentTeacher->id, $showAllIds);
    }

    #[Test]
    public function it_blocks_assigning_an_unavailable_teacher_as_substitute()
    {
        $this->actingAs($this->admin);

        $today = now()->format('Y-m-d');

        $comp = Livewire::test(SubstitutionManager::class)
            ->set('selectedDate', $today)
            ->set('selectedSessionId', $this->session->id);

        // Mark substituteTeacher on Short Leave
        $comp->set('teacherStatuses.' . $this->substituteTeacher->id, 'Short Leave');

        // Try to assign substituteTeacher to absentTeacher's period
        $comp->set('substitutions.' . $this->absentTeacher->id . '.1', $this->substituteTeacher->id)
            ->call('assignSubstitute', $this->absentTeacher->id, 1, $this->class->id, $this->subject->id);

        // Assignment should be blocked with a warning message
        $instance = $comp->instance();
        $this->assertStringContainsString('unavailable for substitution', $instance->warningMessage);

        // Should not exist in substitutions table
        $this->assertFalse(
            \App\Models\Substitution::where('date', $today)
                ->where('period_no', 1)
                ->where('substitute_teacher_id', $this->substituteTeacher->id)
                ->exists()
        );
    }

    #[Test]
    public function it_cleans_up_substitutions_if_substitute_is_marked_non_present()
    {
        $this->actingAs($this->admin);

        $today = now()->format('Y-m-d');

        $comp = Livewire::test(SubstitutionManager::class)
            ->set('selectedDate', $today)
            ->set('selectedSessionId', $this->session->id);

        // Both start present, assign substituteTeacher for Period 1
        $comp->set('teacherStatuses.' . $this->absentTeacher->id, 'Absent');
        $comp->set('teacherStatuses.' . $this->substituteTeacher->id, 'Present');

        $comp->set('substitutions.' . $this->absentTeacher->id . '.1', $this->substituteTeacher->id)
            ->call('assignSubstitute', $this->absentTeacher->id, 1, $this->class->id, $this->subject->id);

        $this->assertTrue(
            \App\Models\Substitution::where('date', $today)
                ->where('period_no', 1)
                ->where('substitute_teacher_id', $this->substituteTeacher->id)
                ->exists()
        );

        // Now substituteTeacher goes on Official Duty
        $comp->set('teacherStatuses.' . $this->substituteTeacher->id, 'Official Duty');

        // Substitute duty should be automatically revoked
        $this->assertFalse(
            \App\Models\Substitution::where('date', $today)
                ->where('period_no', 1)
                ->where('substitute_teacher_id', $this->substituteTeacher->id)
                ->exists()
        );
    }
}
