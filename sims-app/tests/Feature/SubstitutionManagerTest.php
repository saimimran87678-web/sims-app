<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    /** @test */
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

        // Verify that the view renders the option with the period badge/info
        $comp->assertSee('Substitute Teacher (Class 10A: P2)');

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

        // Verify view renders the option with both period badges/info
        $comp->assertSee('Substitute Teacher (Class 10A: P2, Class 10A: P4)');

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

        $comp->assertSee('Substitute Teacher (Class 10A: P4)');
        $comp->assertDontSee('Substitute Teacher (Class 10A: P2, Class 10A: P4)');
    }
}
