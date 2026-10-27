<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use App\Models\ActivityLog;
use App\Models\AcademicSession;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;
    protected $session;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'shift_type' => 'Regular',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@sims.test',
            'role' => 'admin',
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@sims.test',
            'role' => 'teacher',
        ]);

        $this->class = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Grade 10-A',
            'numeric_value' => 10,
            'shift_type' => 'regular',
        ]);
    }

    public function test_creating_student_automatically_logs_activity()
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'admission_no' => 'ADM-101',
            'name' => 'Ahmed Khan',
            'father_name' => 'Tariq Khan',
            'gender' => 'Male',
            'status' => 'active',
        ]);

        $log = ActivityLog::where('model_type', Student::class)
            ->where('model_id', (string)$student->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Ahmed Khan', $log->new_values['name']);
        $this->assertEquals('Tariq Khan', $log->new_values['father_name']);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('Admin User', $log->user_name);
        $this->assertEquals('students', $log->module);
    }

    public function test_updating_student_logs_before_and_after_diff()
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'admission_no' => 'ADM-102',
            'name' => 'Sara Ali',
            'father_name' => 'Ali Raza',
            'gender' => 'Female',
            'status' => 'active',
        ]);

        // Update father name and name
        $student->update([
            'father_name' => 'Ali Muhammad',
            'name' => 'Sara Fatima',
        ]);

        $updateLog = ActivityLog::where('model_type', Student::class)
            ->where('model_id', (string)$student->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals('Ali Raza', $updateLog->old_values['father_name']);
        $this->assertEquals('Ali Muhammad', $updateLog->new_values['father_name']);
        $this->assertEquals('Sara Ali', $updateLog->old_values['name']);
        $this->assertEquals('Sara Fatima', $updateLog->new_values['name']);
        $this->assertContains('father_name', $updateLog->changed_fields);
        $this->assertContains('name', $updateLog->changed_fields);
    }

    public function test_deleting_student_logs_previous_snapshot()
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'admission_no' => 'ADM-103',
            'name' => 'Bilal Tariq',
            'father_name' => 'Tariq Mehmood',
            'gender' => 'Male',
        ]);

        $studentId = $student->id;
        $student->delete();

        $deleteLog = ActivityLog::where('model_type', Student::class)
            ->where('model_id', (string)$studentId)
            ->where('action', 'deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertEquals('Bilal Tariq', $deleteLog->old_values['name']);
        $this->assertEquals('Tariq Mehmood', $deleteLog->old_values['father_name']);
        $this->assertNull($deleteLog->new_values);
    }

    public function test_sensitive_attributes_like_passwords_are_masked()
    {
        $this->actingAs($this->admin);

        $newUser = User::create([
            'name' => 'New Staff',
            'email' => 'staff@sims.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'teacher',
        ]);

        $log = ActivityLog::where('model_type', User::class)
            ->where('model_id', (string)$newUser->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('••••••••', $log->new_values['password']);
    }

    public function test_admin_override_logs_transparently()
    {
        $this->actingAs($this->admin);

        $overrideLog = ActivityLogger::logOverride(
            "Admin override applied: Assigned Mr. Smith to Period 1 while already busy",
            null,
            ['period_no' => 1, 'class_id' => $this->class->id, 'override_by' => $this->admin->id]
        );

        $this->assertNotNull($overrideLog);
        $this->assertEquals('override', $overrideLog->action);
        $this->assertEquals('substitutions', $overrideLog->module);
        $this->assertEquals(1, $overrideLog->new_values['period_no']);
    }

    public function test_activity_log_manager_component_renders_and_filters()
    {
        $this->actingAs($this->admin);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => 'Admin User',
            'action' => 'created',
            'module' => 'students',
            'subject_label' => 'Student: Zainab',
            'description' => 'Created student Zainab',
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => 'Admin User',
            'action' => 'override',
            'module' => 'substitutions',
            'subject_label' => 'Substitution: Period 2',
            'description' => 'Admin override applied for Period 2',
        ]);

        Livewire::test(\App\Livewire\Admin\ActivityLogManager::class)
            ->assertSee('Activity Logger', false)
            ->assertSee('Audit Trail', false)
            ->assertSee('Zainab')
            ->assertSee('Admin Override')
            ->set('actionFilter', 'override')
            ->assertSee('Period 2')
            ->assertDontSee('Zainab');
    }

    public function test_activity_log_diff_modal_works()
    {
        $this->actingAs($this->admin);

        $log = ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => 'Admin User',
            'action' => 'updated',
            'module' => 'students',
            'subject_label' => 'Student: Hamza',
            'description' => 'Updated student Hamza',
            'old_values' => ['roll_no' => '10'],
            'new_values' => ['roll_no' => '20'],
            'changed_fields' => ['roll_no'],
        ]);

        Livewire::test(\App\Livewire\Admin\ActivityLogManager::class)
            ->call('viewDiff', $log->id)
            ->assertSet('showDiffModal', true)
            ->assertSee('Field Modifications (Before vs After)', false)
            ->assertSee('roll_no')
            ->assertSee('10')
            ->assertSee('20')
            ->call('closeDiff')
            ->assertSet('showDiffModal', false);
    }

    public function test_export_csv_streams_activity_logs()
    {
        $this->actingAs($this->admin);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'user_name' => 'Admin User',
            'user_email' => 'admin@sims.test',
            'action' => 'created',
            'module' => 'students',
            'subject_label' => 'Student: Export Test',
            'description' => 'Created student for export test',
            'ip_address' => '127.0.0.1',
        ]);

        Livewire::test(\App\Livewire\Admin\ActivityLogManager::class)
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_unauthorized_user_cannot_access_audit_logs()
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('admin.activity-logs'));
        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_student_attendance_marking_and_saving_creates_audit_log()
    {
        $this->actingAs($this->teacher);

        \Illuminate\Support\Facades\DB::table('session_user')->insert([
            'user_id' => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'class_id' => $this->class->id,
        ]);

        $student1 = Student::create([
            'admission_no' => 'ADM-201',
            'name' => 'Usman Tariq',
            'roll_no' => '1',
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'admission_no' => 'ADM-202',
            'name' => 'Ayesha Khan',
            'roll_no' => '2',
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);

        Livewire::test(\App\Livewire\Teacher\AttendanceManager::class)
            ->set('absent_rolls', '2')
            ->call('save');

        $log = ActivityLog::where('module', 'attendance')
            ->where('action', 'saved')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->teacher->id, $log->user_id);
        $this->assertEquals(1, $log->new_values['present']);
        $this->assertEquals(1, $log->new_values['absent']);
        $this->assertContains('Ayesha Khan', $log->new_values['absent_students']);
    }

    public function test_teacher_using_shared_route_is_logged_as_teacher_shared_access()
    {
        $this->actingAs($this->teacher);

        // Simulate request to a shared route
        $request = \Illuminate\Http\Request::create('/shared/schedule', 'GET');
        app()->instance('request', $request);

        $log = ActivityLogger::log(
            action: 'updated',
            module: 'schedule',
            description: 'Updated timetable period via shared access',
            subjectLabel: 'Schedule: Grade 10-A'
        );

        $this->assertNotNull($log);
        $this->assertEquals('Teacher (Shared Access)', $log->user_role);
    }

    public function test_printing_schedule_creates_audit_log()
    {
        $this->actingAs($this->admin);

        $log = ActivityLogger::logPrint(
            type: 'Master Classwise Timetable',
            description: 'Printed / Exported Master Classwise Timetable',
            details: ['format' => 'web_print', 'paper' => 'a4']
        );

        $this->assertNotNull($log);
        $this->assertEquals('printed', $log->action);
        $this->assertEquals('schedule', $log->module);
        $this->assertEquals('Master Classwise Timetable', $log->new_values['print_type']);
    }

    public function test_feature_sharing_delegation_creates_access_control_audit_log()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\AccessControl\FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('toggleClassAccess', $this->class->id);

        $log = ActivityLog::where('module', 'access_control')
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertStringContainsString('class access', $log->description);
    }

    public function test_teacher_attendance_status_update_creates_audit_log()
    {
        $this->actingAs($this->admin);

        \Illuminate\Support\Facades\DB::table('session_user')->insert([
            'user_id' => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'is_active' => true,
            'allowed_shifts' => 'morning',
        ]);

        Livewire::test(\App\Livewire\Admin\SubstitutionManager::class)
            ->set('selectedDate', '2026-10-27')
            ->set("teacherStatuses.{$this->teacher->id}", 'Leave');

        $log = ActivityLog::where('module', 'attendance')
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertStringContainsString('Teacher User', $log->description);
        $this->assertEquals('Leave', $log->new_values['status']);
    }
}
