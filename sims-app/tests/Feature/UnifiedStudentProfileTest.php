<?php

namespace Tests\Feature;

use App\Livewire\Admin\StudentManager;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentLifecycleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedStudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicSession $session;
    private Classes $class;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => 'Session 2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $this->class = Classes::create([
            'name' => 'Class 9',
            'numeric_value' => 9,
            'academic_session_id' => $this->session->id,
        ]);

        $this->section = Section::create([
            'name' => 'A',
            'class_id' => $this->class->id,
        ]);
    }

    public function test_siblings_are_ranked_by_dob_chronologically(): void
    {
        // Eldest child born 2010, admitted later in 2025
        $child1 = Student::create([
            'name' => 'Ali Eldest',
            'admission_no' => 'ADM-001',
            'father_cnic' => '35201-9999999-1',
            'father_name' => 'Akram Khan',
            'dob' => '2010-01-15',
            'admission_date' => '2025-06-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Middle child born 2012, admitted earlier in 2024
        $child2 = Student::create([
            'name' => 'Bilal Middle',
            'admission_no' => 'ADM-002',
            'father_cnic' => '35201-9999999-1',
            'father_name' => 'Akram Khan',
            'dob' => '2012-05-20',
            'admission_date' => '2024-01-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Youngest child born 2016, admitted in 2025
        $child3 = Student::create([
            'name' => 'Choudhry Youngest',
            'admission_no' => 'ADM-003',
            'father_cnic' => '35201-9999999-1',
            'father_name' => 'Akram Khan',
            'dob' => '2016-11-10',
            'admission_date' => '2025-04-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $familyUnit = $child2->family_unit;
        $this->assertCount(3, $familyUnit);

        // Verification: Even though Bilal was admitted first in 2024, Ali was born first (2010), so Ali is first!
        $this->assertEquals($child1->id, $familyUnit[0]->id, 'Eldest child by DOB must be rank 1');
        $this->assertEquals($child2->id, $familyUnit[1]->id, 'Second child by DOB must be rank 2');
        $this->assertEquals($child3->id, $familyUnit[2]->id, 'Youngest child by DOB must be rank 3');
    }

    public function test_portfolio_aggregates_distinct_exams_and_subjects_cleanly(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'name' => 'Zayd Khan',
            'admission_no' => 'ADM-EXAM-01',
            'father_cnic' => '35201-8888888-2',
            'father_name' => 'Zubair Khan',
            'dob' => '2011-04-12',
            'admission_date' => '2025-04-01',
            'gender' => 'male',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'academic_session_id' => $this->session->id,
            'roll_no' => '15',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $exam = Exam::create([
            'name' => 'Midterm Assessment 2025',
            'type' => 'midterm',
            'academic_session_id' => $this->session->id,
            'start_date' => '2025-09-10',
            'end_date' => '2025-09-20',
            'lifecycle_status' => Exam::STATUS_PUBLISHED,
        ]);

        $subject1 = Subject::firstOrCreate(['name' => 'Mathematics 9'], ['code' => 'MTH9', 'class_id' => $this->class->id]);
        $subject2 = Subject::firstOrCreate(['name' => 'Physics 9'], ['code' => 'PHY9', 'class_id' => $this->class->id]);
        $subject3 = Subject::firstOrCreate(['name' => 'English 9'], ['code' => 'ENG9', 'class_id' => $this->class->id]);

        // 3 subject marks for the same 1 exam
        ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject1->id,
            'marks_obtained' => 85,
            'max_marks' => 100,
            'is_absent' => false,
        ]);
        ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject2->id,
            'marks_obtained' => 90,
            'max_marks' => 100,
            'is_absent' => false,
        ]);
        ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject3->id,
            'marks_obtained' => 78,
            'max_marks' => 100,
            'is_absent' => false,
        ]);

        $service = app(StudentLifecycleService::class);
        $portfolio = $service->getLifetimePortfolio($student);

        // Assert: 1 distinct exam attempted, 3 subject papers passed
        $this->assertEquals(1, $portfolio['exams_summary']['total_exams']);
        $this->assertEquals(1, $portfolio['exams_summary']['passed_exams']);

        // Assert session breakdown contains distinct exams and papers
        $sessionExams = $portfolio['sessions'][0]['exams'];
        $this->assertEquals(1, $sessionExams['total']);
        $this->assertEquals(1, $sessionExams['passed']);
        $this->assertEquals(3, $sessionExams['total_papers']);
        $this->assertEquals(3, $sessionExams['passed_papers']);
        $this->assertCount(1, $sessionExams['list']);
        $this->assertEquals('Midterm Assessment 2025', $sessionExams['list'][0]['exam_name']);
        $this->assertEquals('passed', $sessionExams['list'][0]['status']);
        $this->assertCount(3, $sessionExams['list'][0]['subjects']);

        // Assert Livewire component opens unified modal with tabs
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('openLifetimeHistory', $student->id)
            ->assertSet('showHistoryModal', true)
            ->assertSet('profileTab', 'timeline')
            ->assertSee('Midterm Assessment 2025')
            ->assertSee('Mathematics 9')
            ->assertSee('Physics 9')
            ->assertSee('English 9')
            ->call('setProfileTab', 'biodata')
            ->assertSet('profileTab', 'biodata')
            ->assertSee('Zayd Khan')
            ->call('setProfileTab', 'siblings')
            ->assertSet('profileTab', 'siblings')
            ->call('view', $student->id)
            ->assertSet('profileTab', 'biodata')
            ->assertSet('showHistoryModal', true);
    }

    public function test_graduated_student_profile_resolves_graduated_class_and_omits_current_labels(): void
    {
        $this->actingAs($this->admin);

        // Previous academic session
        $oldSession = AcademicSession::create([
            'name' => 'Session 2024-2025',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'shift_type' => 'Regular',
            'is_active' => false,
        ]);

        $matricClass = Classes::create([
            'name' => 'Class 10-Matric',
            'numeric_value' => 10,
            'academic_session_id' => $oldSession->id,
        ]);

        $graduatedStudent = Student::create([
            'name' => 'Saim Graduated',
            'admission_no' => 'ADM-GRAD-01',
            'father_cnic' => '35201-7777777-3',
            'father_name' => 'Senior Parent',
            'dob' => '2008-02-14',
            'admission_date' => '2020-04-01',
            'gender' => 'male',
            'status' => 'graduated',
        ]);

        // Student has enrollment only in the previous session and is graduated
        Enrollment::create([
            'student_id' => $graduatedStudent->id,
            'class_id' => $matricClass->id,
            'academic_session_id' => $oldSession->id,
            'roll_no' => '01',
            'shift_type' => 'regular',
            'status' => 'graduated',
        ]);

        // Verify service resolves terminal class instead of returning N/A
        $service = app(StudentLifecycleService::class);
        $portfolio = $service->getLifetimePortfolio($graduatedStudent);

        $this->assertEquals('Class 10-Matric', $portfolio['student']['class_name']);

        // Verify UI modal displays Graduated Class, removes "Current Class", and removes "(By Birth Date)"
        Livewire::test(StudentManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('openLifetimeHistory', $graduatedStudent->id, 'biodata')
            ->assertSet('showHistoryModal', true)
            ->assertSet('profileTab', 'biodata')
            ->assertSee('Class 10-Matric')
            ->assertSee('Graduated Class & Section')
            ->assertDontSee('Current Class & Section')
            ->assertSee('Child #1')
            ->assertDontSee('(By Birth Date)');
    }
}
