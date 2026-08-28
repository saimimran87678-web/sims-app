<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicSessionManagerPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session1;
    protected $session2;
    protected $class9;
    protected $class10Source;
    protected $class9Target;
    protected $class10;

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

        $this->session1 = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $this->session2 = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => false,
        ]);

        $this->class9 = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $this->session1->id,
        ]);

        $this->class10Source = Classes::create([
            'name' => 'Class 10',
            'numeric_value' => 10,
            'academic_session_id' => $this->session1->id,
        ]);

        $this->class9Target = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $this->session2->id,
        ]);

        $this->class10 = Classes::create([
            'name' => 'Class 10',
            'numeric_value' => 10,
            'academic_session_id' => $this->session2->id,
        ]);

        // Define next class mapping in session 1
        $this->class9->update(['next_class_id' => $this->class10Source->id]);
    }

    public function test_student_promotion_promote_action()
    {
        $this->actingAs($this->admin);

        // Create student and enroll in session1
        $student = Student::create([
            'name' => 'Promoted Student',
            'admission_no' => 'ADM-001',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '10',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run promotion wizard
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->assertSet('showPromotionPreview', true)
            ->set('promotionPreview', [
                [
                    'enrollment_id' => $enrollmentId,
                    'student_id' => $student->id,
                    'student_name' => 'Promoted Student',
                    'current_class_id' => $this->class9->id,
                    'current_class_name' => 'Class 9',
                    'current_class_numeric_value' => 9,
                    'current_shift' => 'morning',
                    'target_class_id' => (string) $this->class10->id,
                    'target_shift' => 'morning',
                    'roll_number' => '10',
                    'status' => 'promote',
                ]
            ])
            ->call('savePromotion')
            ->assertHasNoErrors();

        // Verify old enrollment is marked as 'promoted'
        $oldEnroll = DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('promoted', $oldEnroll->status);

        // Verify new enrollment is created in session 2 as 'active'
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNotNull($newEnroll);
        $this->assertEquals($this->class10->id, $newEnroll->class_id);
        $this->assertEquals('active', $newEnroll->status);

        // Verify global student status is still 'active'
        $student->refresh();
        $this->assertEquals('active', $student->status);
    }

    public function test_student_promotion_repeater_action()
    {
        $this->actingAs($this->admin);

        // Create student and enroll in session1
        $student = Student::create([
            'name' => 'Repeater Student',
            'admission_no' => 'ADM-002',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '11',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run promotion wizard
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->set('promotionPreview', [
                [
                    'enrollment_id' => $enrollmentId,
                    'student_id' => $student->id,
                    'student_name' => 'Repeater Student',
                    'current_class_id' => $this->class9->id,
                    'current_class_name' => 'Class 9',
                    'current_class_numeric_value' => 9,
                    'current_shift' => 'morning',
                    'target_class_id' => (string) $this->class9Target->id,
                    'target_shift' => 'morning',
                    'roll_number' => '11',
                    'status' => 'repeater',
                ]
            ])
            ->call('savePromotion')
            ->assertHasNoErrors();

        // Verify old enrollment is marked as 'held_back'
        $oldEnroll = DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('held_back', $oldEnroll->status);

        // Verify new enrollment in target session is in the same class (Class 9)
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNotNull($newEnroll);
        $this->assertEquals($this->class9Target->id, $newEnroll->class_id);
        $this->assertEquals('active', $newEnroll->status);

        // Verify global student status is still 'active'
        $student->refresh();
        $this->assertEquals('active', $student->status);
    }

    public function test_student_promotion_passed_out_action()
    {
        $this->actingAs($this->admin);

        // Create student and enroll in session1
        $student = Student::create([
            'name' => 'Graduated Student',
            'admission_no' => 'ADM-003',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '12',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run promotion wizard
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->set('promotionPreview', [
                [
                    'enrollment_id' => $enrollmentId,
                    'student_id' => $student->id,
                    'student_name' => 'Graduated Student',
                    'current_class_id' => $this->class9->id,
                    'current_class_name' => 'Class 9',
                    'current_class_numeric_value' => 9,
                    'current_shift' => 'morning',
                    'target_class_id' => '',
                    'target_shift' => 'morning',
                    'roll_number' => '12',
                    'status' => 'passed_out',
                ]
            ])
            ->call('savePromotion')
            ->assertHasNoErrors();

        // Verify old enrollment is marked as 'passed_out'
        $oldEnroll = DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('passed_out', $oldEnroll->status);

        // Verify NO new enrollment in session 2 is created
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNull($newEnroll);

        // Verify global student status is updated to 'inactive'
        $student->refresh();
        $this->assertEquals('inactive', $student->status);
    }

    public function test_student_promotion_left_school_action()
    {
        $this->actingAs($this->admin);

        // Create student and enroll in session1
        $student = Student::create([
            'name' => 'Left Student',
            'admission_no' => 'ADM-004',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '13',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run promotion wizard
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->set('promotionPreview', [
                [
                    'enrollment_id' => $enrollmentId,
                    'student_id' => $student->id,
                    'student_name' => 'Left Student',
                    'current_class_id' => $this->class9->id,
                    'current_class_name' => 'Class 9',
                    'current_class_numeric_value' => 9,
                    'current_shift' => 'morning',
                    'target_class_id' => '',
                    'target_shift' => 'morning',
                    'roll_number' => '13',
                    'status' => 'left_school',
                ]
            ])
            ->call('savePromotion')
            ->assertHasNoErrors();

        // Verify old enrollment is marked as 'transferred'
        $oldEnroll = DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('transferred', $oldEnroll->status);

        // Verify NO new enrollment in session 2 is created
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNull($newEnroll);

        // Verify global student status is updated to 'inactive'
        $student->refresh();
        $this->assertEquals('inactive', $student->status);
    }

    public function test_student_promotion_to_regular_shift_session()
    {
        $this->actingAs($this->admin);

        // Update target session to be 'Regular' shift configuration
        $this->session2->update(['shift_type' => 'Regular']);

        // Create student and enroll in session1
        $student = Student::create([
            'name' => 'Regular Shift Student',
            'admission_no' => 'ADM-005',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '15',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run promotion wizard
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->assertSet('toSessionIsRegular', true)
            ->assertSet('promotionPreview', [
                [
                    'enrollment_id' => $enrollmentId,
                    'student_id' => $student->id,
                    'student_name' => 'Regular Shift Student',
                    'current_class_id' => $this->class9->id,
                    'current_class_name' => 'Class 9',
                    'current_class_numeric_value' => 9,
                    'current_shift' => 'morning',
                    'target_class_id' => (string) $this->class10->id,
                    'target_shift' => 'regular', // default should map to regular
                    'roll_number' => '15',
                    'status' => 'promote',
                ]
            ])
            ->call('savePromotion')
            ->assertHasNoErrors();

        // Verify old enrollment is marked as 'promoted'
        $oldEnroll = DB::table('enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('promoted', $oldEnroll->status);

        // Verify new enrollment is created in target session as 'active' with shift_type 'regular'
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNotNull($newEnroll);
        $this->assertEquals($this->class10->id, $newEnroll->class_id);
        $this->assertEquals('regular', $newEnroll->status === 'active' ? $newEnroll->shift_type : '');
    }

    public function test_student_promotion_mass_update_actions()
    {
        $this->actingAs($this->admin);

        $student1 = Student::create([
            'name' => 'Mass Student 1',
            'admission_no' => 'ADM-M01',
            'status' => 'active',
        ]);
        $enrollmentId1 = DB::table('enrollments')->insertGetId([
            'student_id' => $student1->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '20',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student2 = Student::create([
            'name' => 'Mass Student 2',
            'admission_no' => 'ADM-M02',
            'status' => 'active',
        ]);
        $enrollmentId2 = DB::table('enrollments')->insertGetId([
            'student_id' => $student2->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '21',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Test applyMassStatus sets all statuses to 'passed_out'
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion')
            ->set('massStatus', 'passed_out')
            ->call('applyMassStatus')
            ->assertViewHas('promotionPreview', function ($preview) {
                return $preview[0]['status'] === 'passed_out' && $preview[1]['status'] === 'passed_out';
            })
            // 2. Test applyMassShift sets all shifts to 'evening'
            ->set('massShift', 'evening')
            ->call('applyMassShift')
            ->assertViewHas('promotionPreview', function ($preview) {
                return $preview[0]['target_shift'] === 'evening' && $preview[1]['target_shift'] === 'evening';
            });
    }

    public function test_promotion_wizard_auto_promotes_passing_students_and_requires_manual_for_failed()
    {
        $this->actingAs($this->admin);

        // 1. Create a Final-Term exam
        $exam = \App\Models\Exam::create([
            'name' => 'Final Term 2025',
            'academic_session_id' => $this->session1->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'is_active' => true,
            'type' => 'Final-Term',
        ]);

        // 2. Create subjects for class 9
        $subjectMath = \App\Models\Subject::create([
            'name' => 'Math',
            'class_id' => $this->class9->id,
        ]);
        $subjectEng = \App\Models\Subject::create([
            'name' => 'English',
            'class_id' => $this->class9->id,
        ]);

        // 3. Create marks configs for both subjects
        DB::table('marks_configs')->insert([
            [
                'exam_id' => $exam->id,
                'class_id' => $this->class9->id,
                'subject' => 'Math',
                'total_marks' => 100,
                'passing_marks' => 33,
                'academic_session_id' => $this->session1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_id' => $exam->id,
                'class_id' => $this->class9->id,
                'subject' => 'English',
                'total_marks' => 100,
                'passing_marks' => 33,
                'academic_session_id' => $this->session1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. Create Students and Enrollments
        // Student A (Passed all)
        $studentA = Student::create([
            'name' => 'Student A Passed',
            'admission_no' => 'ADM-A',
            'status' => 'active',
        ]);
        $enrollA = DB::table('enrollments')->insertGetId([
            'student_id' => $studentA->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '1',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Student B (Failed Math)
        $studentB = Student::create([
            'name' => 'Student B Failed',
            'admission_no' => 'ADM-B',
            'status' => 'active',
        ]);
        $enrollB = DB::table('enrollments')->insertGetId([
            'student_id' => $studentB->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '2',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Student C (Absent in English)
        $studentC = Student::create([
            'name' => 'Student C Absent',
            'admission_no' => 'ADM-C',
            'status' => 'active',
        ]);
        $enrollC = DB::table('enrollments')->insertGetId([
            'student_id' => $studentC->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '3',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Insert Exam Marks
        DB::table('exam_marks')->insert([
            // Student A: Passed Math (50) & Eng (60)
            [
                'exam_id' => $exam->id,
                'student_id' => $studentA->id,
                'subject_id' => $subjectMath->id,
                'marks_obtained' => 50.00,
                'max_marks' => 100.00,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_id' => $exam->id,
                'student_id' => $studentA->id,
                'subject_id' => $subjectEng->id,
                'marks_obtained' => 60.00,
                'max_marks' => 100.00,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Student B: Failed Math (25) & Passed Eng (70)
            [
                'exam_id' => $exam->id,
                'student_id' => $studentB->id,
                'subject_id' => $subjectMath->id,
                'marks_obtained' => 25.00,
                'max_marks' => 100.00,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_id' => $exam->id,
                'student_id' => $studentB->id,
                'subject_id' => $subjectEng->id,
                'marks_obtained' => 70.00,
                'max_marks' => 100.00,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Student C: Passed Math (65) & Absent Eng
            [
                'exam_id' => $exam->id,
                'student_id' => $studentC->id,
                'subject_id' => $subjectMath->id,
                'marks_obtained' => 65.00,
                'max_marks' => 100.00,
                'is_absent' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'exam_id' => $exam->id,
                'student_id' => $studentC->id,
                'subject_id' => $subjectEng->id,
                'marks_obtained' => 0.00,
                'max_marks' => 100.00,
                'is_absent' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 6. Test component
        $component = Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion');

        // Verify Student A is in auto-promote list
        $autoList = $component->get('autoPromoteList');
        $this->assertCount(1, $autoList);
        $this->assertEquals($studentA->id, $autoList[0]['student_id']);

        // Verify Student B and Student C are in the manual review list (promotionPreview)
        $previewList = $component->get('promotionPreview');
        $this->assertCount(2, $previewList);
        $studentIdsInPreview = collect($previewList)->pluck('student_id')->toArray();
        $this->assertContains($studentB->id, $studentIdsInPreview);
        $this->assertContains($studentC->id, $studentIdsInPreview);
        $this->assertNotContains($studentA->id, $studentIdsInPreview);

        // Process promotion
        $component->call('savePromotion')->assertHasNoErrors();

        // Verify all 3 students were successfully promoted (both auto-promoted and reviewed)
        // Student A:
        $this->assertEquals('promoted', DB::table('enrollments')->where('id', $enrollA)->value('status'));
        $this->assertTrue(DB::table('enrollments')
            ->where('student_id', $studentA->id)
            ->where('academic_session_id', $this->session2->id)
            ->where('class_id', $this->class10->id)
            ->exists());

        // Student B:
        $this->assertEquals('promoted', DB::table('enrollments')->where('id', $enrollB)->value('status'));
        $this->assertTrue(DB::table('enrollments')
            ->where('student_id', $studentB->id)
            ->where('academic_session_id', $this->session2->id)
            ->where('class_id', $this->class10->id)
            ->exists());

        // Student C:
        $this->assertEquals('promoted', DB::table('enrollments')->where('id', $enrollC)->value('status'));
        $this->assertTrue(DB::table('enrollments')
            ->where('student_id', $studentC->id)
            ->where('academic_session_id', $this->session2->id)
            ->where('class_id', $this->class10->id)
            ->exists());
    }

    public function test_repeater_selection_filters_classes_by_same_grade_level()
    {
        $this->actingAs($this->admin);

        // Create an alternative Class 9 section in target session (same grade level 9)
        $class9TargetB = Classes::create([
            'name' => 'Class 9-B',
            'numeric_value' => 9,
            'academic_session_id' => $this->session2->id,
        ]);

        // Create a different grade class in target session (grade level 11)
        $class11Target = Classes::create([
            'name' => 'Class 11',
            'numeric_value' => 11,
            'academic_session_id' => $this->session2->id,
        ]);

        $student = Student::create([
            'name' => 'Repeater B Student',
            'admission_no' => 'ADM-REP-9',
            'status' => 'active',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id' => $student->id,
            'class_id' => $this->class9->id,
            'academic_session_id' => $this->session1->id,
            'shift_type' => 'morning',
            'roll_number' => '99',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Preview promotion and verify initial fields are populated
        $component = Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $this->session1->id)
            ->set('toSessionId', $this->session2->id)
            ->call('previewPromotion');

        $preview = $component->get('promotionPreview');
        $this->assertCount(1, $preview);
        $this->assertEquals($student->id, $preview[0]['student_id']);
        $this->assertEquals(9, $preview[0]['current_class_numeric_value']);

        // 2. Change status to repeater and trigger updated hook
        // Livewire calls updated("promotionPreview.0.status", "repeater")
        $component->set('promotionPreview.0.status', 'repeater');

        // Verify it auto-populated the class with same name
        $this->assertEquals((string)$this->class9Target->id, $component->get('promotionPreview.0.target_class_id'));

        // 3. Manually select the alternative Class 9 section (Class 9-B)
        $component->set('promotionPreview.0.target_class_id', (string)$class9TargetB->id);

        // 4. Save and verify student is enrolled in Class 9-B
        $component->call('savePromotion')->assertHasNoErrors();

        // Verify old enrollment is held_back
        $this->assertEquals('held_back', DB::table('enrollments')->where('id', $enrollmentId)->value('status'));

        // Verify new enrollment is in Class 9-B (not the default Class 9)
        $newEnroll = DB::table('enrollments')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $this->session2->id)
            ->first();
        $this->assertNotNull($newEnroll);
        $this->assertEquals($class9TargetB->id, $newEnroll->class_id);
    }
}
