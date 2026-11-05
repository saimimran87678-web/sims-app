<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSchedule;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Services\GradeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ExamMarkNullabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;
    protected $studentUser;
    protected $session;
    protected $class;
    protected $subject;
    protected $student;
    protected $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed license
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->assignRole('Teacher');

        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->studentUser->assignRole('Student');

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'shift_type' => 'Morning',
        ]);

        $this->class = Classes::create([
            'name' => 'Grade 10',
            'numeric_value' => 10,
            'shift_type' => 'morning',
            'academic_session_id' => $this->session->id,
        ]);

        $this->subject = Subject::firstOrCreate(['name' => 'Physics'], ['class_id' => $this->class->id]);

        $this->student = Student::create([
            'name' => 'Hamza Ali',
            'admission_no' => 'STU-1001',
            'user_id' => $this->studentUser->id,
            'class_id' => $this->class->id,
        ]);

        $this->enrollment = $this->student->enrollments()->first() ?: Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_session_id' => $this->session->id,
            'roll_number' => '1',
            'shift_type' => 'morning',
            'status' => 'active',
        ]);
        $this->enrollment->update(['roll_number' => '1', 'status' => 'active']);
    }

    public function test_exam_marks_can_be_null_for_unentered_students()
    {
        $exam = Exam::create([
            'name' => 'Midterm 2026',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-15',
            'type' => 'Term',
            'lifecycle_status' => Exam::STATUS_ENTERED,
        ]);

        // Insert pending mark (marks_obtained is NULL)
        $mark = ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => null,
            'max_marks' => 100.00,
            'is_absent' => false,
        ]);

        $this->assertNull($mark->marks_obtained);
        $this->assertTrue($mark->isPending());
        $this->assertFalse($mark->isScored());
        $this->assertFalse($mark->isAbsent());

        // Verify scopePending and scopeEntered
        $this->assertEquals(1, ExamMark::pending()->count());
        $this->assertEquals(0, ExamMark::entered()->count());
    }

    public function test_scored_zero_is_distinguishable_from_pending_and_absent()
    {
        $exam = Exam::create([
            'name' => 'Midterm 2026',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-15',
            'type' => 'Term',
            'lifecycle_status' => Exam::STATUS_ENTERED,
        ]);

        // Student scored explicit zero
        $zeroMark = ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 0.00,
            'max_marks' => 100.00,
            'is_absent' => false,
        ]);

        $this->assertNotNull($zeroMark->marks_obtained);
        $this->assertEquals(0.00, (float) $zeroMark->marks_obtained);
        $this->assertTrue($zeroMark->isScored());
        $this->assertFalse($zeroMark->isPending());
        $this->assertFalse($zeroMark->isAbsent());
        $this->assertEquals(0.00, $zeroMark->percentage);
        $this->assertFalse($zeroMark->isPassed(33.0));

        $this->assertEquals(1, ExamMark::entered()->count());
        $this->assertEquals(0, ExamMark::pending()->count());
    }

    public function test_absent_student_stores_is_absent_true_and_null_marks()
    {
        $exam = Exam::create([
            'name' => 'Midterm 2026',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-15',
            'type' => 'Term',
            'lifecycle_status' => Exam::STATUS_ENTERED,
        ]);

        $absentMark = ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => null,
            'max_marks' => 100.00,
            'is_absent' => true,
        ]);

        $this->assertTrue($absentMark->isAbsent());
        $this->assertFalse($absentMark->isScored());
        $this->assertFalse($absentMark->isPending());
        $this->assertEquals(1, ExamMark::absent()->count());
    }

    public function test_grade_calculation_service_matches_configured_scales()
    {
        $service = new GradeCalculationService();

        // 95% -> A+
        $evalAplus = $service->calculateFromMark(95.0, 100.0, false);
        $this->assertEquals('A+', $evalAplus['grade']);
        $this->assertEquals(4.00, $evalAplus['gpa_point']);
        $this->assertTrue($evalAplus['is_passing']);

        // 82% -> A
        $evalA = $service->calculateFromMark(82.0, 100.0, false);
        $this->assertEquals('A', $evalA['grade']);
        $this->assertEquals(3.70, $evalA['gpa_point']);
        $this->assertTrue($evalA['is_passing']);

        // 74% -> B
        $evalB = $service->calculateFromMark(74.0, 100.0, false);
        $this->assertEquals('B', $evalB['grade']);
        $this->assertTrue($evalB['is_passing']);

        // 63% -> C
        $evalC = $service->calculateFromMark(63.0, 100.0, false);
        $this->assertEquals('C', $evalC['grade']);
        $this->assertTrue($evalC['is_passing']);

        // 52% -> D
        $evalD = $service->calculateFromMark(52.0, 100.0, false);
        $this->assertEquals('D', $evalD['grade']);
        $this->assertTrue($evalD['is_passing']);

        // 35% -> E
        $evalE = $service->calculateFromMark(35.0, 100.0, false);
        $this->assertEquals('E', $evalE['grade']);
        $this->assertTrue($evalE['is_passing']);

        // 20% -> F
        $evalF = $service->calculateFromMark(20.0, 100.0, false);
        $this->assertEquals('F', $evalF['grade']);
        $this->assertEquals(0.00, $evalF['gpa_point']);
        $this->assertFalse($evalF['is_passing']);

        // Absent
        $evalAbs = $service->calculateFromMark(null, 100.0, true);
        $this->assertEquals('ABS', $evalAbs['grade']);
        $this->assertFalse($evalAbs['is_passing']);

        // Pending
        $evalPending = $service->calculateFromMark(null, 100.0, false);
        $this->assertEquals('-', $evalPending['grade']);
        $this->assertNull($evalPending['is_passing']);
    }

    public function test_grade_calculation_service_aggregate_calculation()
    {
        $service = new GradeCalculationService();

        $marks = [
            ['marks_obtained' => 90.0, 'max_marks' => 100.0, 'is_absent' => false], // A+ (4.0)
            ['marks_obtained' => 80.0, 'max_marks' => 100.0, 'is_absent' => false], // A (3.7)
            ['marks_obtained' => 70.0, 'max_marks' => 100.0, 'is_absent' => false], // B (3.0)
        ];

        $aggregate = $service->calculateAggregate($marks);

        $this->assertEquals(240.0, $aggregate['total_obtained']);
        $this->assertEquals(300.0, $aggregate['total_max']);
        $this->assertEquals(80.0, $aggregate['percentage']);
        $this->assertEquals('A', $aggregate['grade']);
        $this->assertEquals(3.57, $aggregate['gpa']);
        $this->assertEquals('Pass', $aggregate['result_status']);
    }

    public function test_exam_lifecycle_state_machine_transitions()
    {
        $exam = Exam::create([
            'name' => 'Annual Exam 2026',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
            'type' => 'Final',
        ]);

        // Default state: Draft
        $this->assertTrue($exam->isDraft());
        $this->assertTrue($exam->canEnterMarks());
        $this->assertFalse($exam->isPublished());
        $this->assertFalse($exam->isLocked());

        // Open marks entry
        $exam->openForEntry($this->admin);
        $this->assertTrue($exam->fresh()->isEntered());
        $this->assertTrue($exam->fresh()->canEnterMarks());

        // Lock exam
        $exam->lock($this->admin);
        $this->assertTrue($exam->fresh()->isLocked());
        $this->assertFalse($exam->fresh()->canEnterMarks());
        $this->assertNotNull($exam->fresh()->locked_at);
        $this->assertEquals($this->admin->id, $exam->fresh()->locked_by_user_id);

        // Publish exam
        $exam->publish($this->admin);
        $this->assertTrue($exam->fresh()->isPublished());
        $this->assertFalse($exam->fresh()->canEnterMarks());
        $this->assertNotNull($exam->fresh()->published_at);
        $this->assertEquals($this->admin->id, $exam->fresh()->published_by_user_id);

        // Revert to draft
        $exam->revertToDraft($this->admin);
        $this->assertTrue($exam->fresh()->isDraft());
        $this->assertNull($exam->fresh()->published_at);
        $this->assertNull($exam->fresh()->locked_at);
    }

    public function test_student_portal_only_displays_published_exam_results()
    {
        // Published Exam
        $publishedExam = Exam::create([
            'name' => 'Published Midterm',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-15',
            'type' => 'Term',
            'lifecycle_status' => Exam::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by_user_id' => $this->admin->id,
        ]);

        ExamMark::create([
            'exam_id' => $publishedExam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 88.00,
            'max_marks' => 100.00,
            'is_absent' => false,
        ]);

        // Draft / Unofficial Exam
        $draftExam = Exam::create([
            'name' => 'Draft Final Exam',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'type' => 'Final',
            'lifecycle_status' => Exam::STATUS_DRAFT,
        ]);

        ExamMark::create([
            'exam_id' => $draftExam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 45.00,
            'max_marks' => 100.00,
            'is_absent' => false,
        ]);

        // Locked Exam (In Audit, Not Yet Published)
        $lockedExam = Exam::create([
            'name' => 'Locked Quiz',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'type' => 'Quiz',
            'lifecycle_status' => Exam::STATUS_LOCKED,
            'locked_at' => now(),
        ]);

        ExamMark::create([
            'exam_id' => $lockedExam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'marks_obtained' => 70.00,
            'max_marks' => 100.00,
            'is_absent' => false,
        ]);

        $this->actingAs($this->studentUser);
        session(['student_portal_id' => $this->student->id]);

        // Test Student Portal Dashboard
        Livewire::test(\App\Livewire\Student\PortalDashboard::class)
            ->set('activeTab', 'results')
            ->assertViewHas('examMarks', function ($examMarks) {
                // Must contain published exam
                $hasPublished = $examMarks->has('Published Midterm');
                // Must NOT contain draft or locked exams
                $hasDraft = $examMarks->has('Draft Final Exam');
                $hasLocked = $examMarks->has('Locked Quiz');

                return $hasPublished && !$hasDraft && !$hasLocked;
            });
    }

    public function test_exam_manager_lifecycle_actions()
    {
        $this->actingAs($this->admin);

        $exam = Exam::create([
            'name' => 'Lifecycle Test Exam',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'type' => 'Midterm',
            'lifecycle_status' => Exam::STATUS_DRAFT,
        ]);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->call('openMarksEntry', $exam->id);

        $this->assertEquals(Exam::STATUS_ENTERED, $exam->fresh()->lifecycle_status);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->call('lockExam', $exam->id);

        $this->assertEquals(Exam::STATUS_LOCKED, $exam->fresh()->lifecycle_status);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->call('publishExam', $exam->id);

        $this->assertEquals(Exam::STATUS_PUBLISHED, $exam->fresh()->lifecycle_status);
        $this->assertNotNull($exam->fresh()->published_at);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->call('revertToDraft', $exam->id);

        $this->assertEquals(Exam::STATUS_DRAFT, $exam->fresh()->lifecycle_status);
        $this->assertNull($exam->fresh()->published_at);
    }

    public function test_admin_grade_manager_prevents_saving_when_exam_is_locked()
    {
        // Non-super-admin user with exams.manage
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $regularAdmin = User::factory()->create(['role' => 'admin']);
        $regularAdmin->syncRoles(['Admin']);
        $regularAdmin->givePermissionTo('exams.manage');

        $this->actingAs($regularAdmin);

        $exam = Exam::create([
            'name' => 'Locked Annual Exam',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'type' => 'Final',
            'lifecycle_status' => Exam::STATUS_LOCKED,
        ]);

        ExamSchedule::create([
            'exam_id' => $exam->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'max_marks' => 100,
        ]);

        Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->set('selectedExamId', $exam->id)
            ->set('selectedClassId', $this->class->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('grades', [$this->student->id => 85])
            ->call('save')
            ->assertSee('Marks entry is locked for this exam (Current status: Locked (Auditing)).');

        // Verify mark was NOT written to DB
        $this->assertDatabaseMissing('exam_marks', [
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    public function test_teacher_grade_manager_prevents_saving_when_exam_is_locked()
    {
        $this->actingAs($this->teacher);

        $exam = Exam::create([
            'name' => 'Locked Teacher Exam',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'type' => 'Midterm',
            'lifecycle_status' => Exam::STATUS_LOCKED,
        ]);

        ExamSchedule::create([
            'exam_id' => $exam->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'max_marks' => 100,
            'is_locked' => false,
        ]);

        DB::table('subject_allocations')->insert([
            'user_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->set('selectedExamId', $exam->id)
            ->set('selectedClassId', $this->class->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('grades', [$this->student->id => 90])
            ->call('save')
            ->assertSee('Marks entry is locked for this exam (Current status: Locked (Auditing)).');

        $this->assertDatabaseMissing('exam_marks', [
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
        ]);
    }
}
