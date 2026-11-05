<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_subjects_are_deduplicated_and_unique()
    {
        $subjects = Subject::all();
        $this->assertNotEmpty($subjects);

        // Assert all subject names are unique
        $names = $subjects->pluck('name')->toArray();
        $this->assertCount(count($names), array_unique($names));

        // Assert core subjects exist
        $this->assertContains('Mathematics', $names);
        $this->assertContains('English', $names);
        $this->assertContains('Biology', $names);
        $this->assertContains('Computer Science', $names);
    }

    public function test_standard_program_bundles_exist_with_subjects()
    {
        $bioProgram = Program::where('code', 'SSC-BIO')->first();
        $this->assertNotNull($bioProgram);

        $csProgram = Program::where('code', 'SSC-CS')->first();
        $this->assertNotNull($csProgram);

        // Bio Program should contain Biology and not Computer Science
        $bioSubjects = $bioProgram->subjects->pluck('name')->toArray();
        $this->assertContains('Biology', $bioSubjects);
        $this->assertNotContains('Computer Science', $bioSubjects);
        $this->assertContains('English', $bioSubjects);

        // CS Program should contain Computer Science and not Biology
        $csSubjects = $csProgram->subjects->pluck('name')->toArray();
        $this->assertContains('Computer Science', $csSubjects);
        $this->assertNotContains('Biology', $csSubjects);
        $this->assertContains('English', $csSubjects);
    }

    public function test_class_program_allowances()
    {
        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
        ]);

        $bio = Program::where('code', 'SSC-BIO')->first();
        $cs = Program::where('code', 'SSC-CS')->first();

        $class9->programs()->attach([
            $bio->id => ['is_default' => false],
            $cs->id => ['is_default' => true],
        ]);

        $programs = $class9->fresh()->programs;
        $this->assertCount(2, $programs);
        $this->assertTrue($programs->where('code', 'SSC-CS')->first()->pivot->is_default);
        $this->assertFalse($programs->where('code', 'SSC-BIO')->first()->pivot->is_default);
    }

    public function test_enrollment_binds_program_and_resolves_curriculum_subjects()
    {
        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => 'Class 9A',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'morning',
        ]);

        $student = Student::create([
            'admission_no' => 'ADM-2026-001',
            'roll_no' => '101',
            'name' => 'Ali Ahmed',
            'b_form_number' => '35201-9999999-1',
            'father_name' => 'Ahmed Khan',
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);
        $csProgram = Program::where('code', 'SSC-CS')->first();

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_session_id' => $session->id,
            'program_id' => $csProgram->id,
            'roll_number' => '101',
            'status' => 'active',
            'shift_type' => 'morning',
        ]);

        $this->assertEquals('SSC-CS', $enrollment->program->code);
        $this->assertTrue($enrollment->program->subjects->pluck('name')->contains('Computer Science'));
        $this->assertFalse($enrollment->program->subjects->pluck('name')->contains('Biology'));
    }

    public function test_student_manager_livewire_admission_with_program_bundle()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach($csProgram->id, ['is_default' => true]);

        \Livewire\Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $session->id)
            ->call('openModal')
            ->set('class_id', $class9->id)
            ->set('name', 'Usman Tariq')
            ->set('admission_no', 'ADM-901')
            ->set('roll_no', '901')
            ->set('b_form_number', '35201-8888888-1')
            ->set('father_name', 'Tariq Mehmood')
            ->set('gender', 'Male')
            ->set('admission_date', '2026-04-01')
            ->set('transport_mode', 'none')
            ->set('program_id', $csProgram->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('admission_no', 'ADM-901')->first();
        $this->assertNotNull($student);

        $enrollment = Enrollment::where('student_id', $student->id)->where('academic_session_id', $session->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals($csProgram->id, $enrollment->program_id);
    }

    public function test_student_manager_livewire_bulk_and_inline_program_assignment()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $bioProgram = Program::where('code', 'SSC-BIO')->first();
        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach([
            $bioProgram->id => ['is_default' => false],
            $csProgram->id => ['is_default' => true],
        ]);

        $student1 = Student::create([
            'admission_no' => 'ADM-001',
            'roll_no' => '1',
            'name' => 'Student One',
            'father_name' => 'Father One',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'admission_no' => 'ADM-002',
            'roll_no' => '2',
            'name' => 'Student Two',
            'father_name' => 'Father Two',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        $enr1 = Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $csProgram->id,
            'roll_number' => '1',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $enr2 = Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $csProgram->id,
            'roll_number' => '2',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        // Test bulk assign
        \Livewire\Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedClassId', $class9->id)
            ->set('selectedStudentIds', [$student1->id, $student2->id])
            ->set('bulkProgramId', $bioProgram->id)
            ->call('bulkAssignProgram')
            ->assertHasNoErrors();

        $this->assertEquals($bioProgram->id, $enr1->fresh()->program_id);
        $this->assertEquals($bioProgram->id, $enr2->fresh()->program_id);

        // Test inline single student track switch
        \Livewire\Livewire::test(\App\Livewire\Admin\StudentManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedClassId', $class9->id)
            ->call('assignStudentProgram', $student1->id, $csProgram->id)
            ->assertHasNoErrors();

        $this->assertEquals($csProgram->id, $enr1->fresh()->program_id);
    }

    public function test_gradebook_filters_elective_students_accurately()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $bioProgram = Program::where('code', 'SSC-BIO')->first();
        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach([
            $bioProgram->id => ['is_default' => false],
            $csProgram->id => ['is_default' => true],
        ]);

        $math = Subject::where('name', 'Mathematics')->first();
        $cs = Subject::where('name', 'Computer Science')->first();
        $bio = Subject::where('name', 'Biology')->first();

        $csStudent = Student::create([
            'admission_no' => 'ADM-CS-1',
            'roll_no' => '101',
            'name' => 'CS Student',
            'father_name' => 'Father CS',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        $bioStudent = Student::create([
            'admission_no' => 'ADM-BIO-1',
            'roll_no' => '102',
            'name' => 'Bio Student',
            'father_name' => 'Father Bio',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $csStudent->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $csProgram->id,
            'roll_number' => '101',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $bioStudent->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $bioProgram->id,
            'roll_number' => '102',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $exam = \App\Models\Exam::create([
            'academic_session_id' => $session->id,
            'name' => 'Midterm Exam 2026',
            'status' => 'Ongoing',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
        ]);

        foreach ([$math, $cs, $bio] as $sub) {
            \App\Models\ExamSchedule::create([
                'exam_id' => $exam->id,
                'class_id' => $class9->id,
                'subject_id' => $sub->id,
                'exam_date' => '2026-09-05',
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'max_marks' => 100,
            ]);

            \App\Models\MarksConfig::create([
                'exam_id' => $exam->id,
                'class_id' => $class9->id,
                'subject' => $sub->name,
                'subject_id' => $sub->id,
                'academic_session_id' => $session->id,
                'total_marks' => 100,
                'passing_marks' => 33,
            ]);
        }

        // 1. Admin GradeManager: Computer Science
        \Livewire\Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $cs->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('CS Student')
            ->assertDontSee('Bio Student');

        // 2. Admin GradeManager: Biology
        \Livewire\Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $bio->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('Bio Student')
            ->assertDontSee('CS Student');

        // 3. Admin GradeManager: Mathematics (Compulsory - both students)
        \Livewire\Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $math->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('CS Student')
            ->assertSee('Bio Student');

        // 4. Teacher GradeManager
        $teacher = \App\Models\User::factory()->create();
        $teacher->assignRole('Teacher');
        foreach ([$math, $cs, $bio] as $sub) {
            \Illuminate\Support\Facades\DB::table('subject_allocations')->insert([
                'user_id' => $teacher->id,
                'class_id' => $class9->id,
                'subject_id' => $sub->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($teacher);
        session(['selected_shift_type' => 'regular']);

        // Teacher CS
        \Livewire\Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $cs->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('CS Student')
            ->assertDontSee('Bio Student');

        // Teacher Bio
        \Livewire\Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $bio->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('Bio Student')
            ->assertDontSee('CS Student');

        // Teacher Math
        \Livewire\Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->set('selectedExamId', $exam->id)
            ->call('loadAvailableClasses')
            ->set('selectedClassId', $class9->id)
            ->call('loadSubjects')
            ->set('selectedSubjectId', $math->id)
            ->call('loadStudentsAndGrades')
            ->assertSee('CS Student')
            ->assertSee('Bio Student');
    }

    public function test_result_report_renders_elective_enrollment_matrix()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $bioProgram = Program::where('code', 'SSC-BIO')->first();
        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach([
            $bioProgram->id => ['is_default' => false],
            $csProgram->id => ['is_default' => true],
        ]);

        $math = Subject::where('name', 'Mathematics')->first();
        $cs = Subject::where('name', 'Computer Science')->first();
        $bio = Subject::where('name', 'Biology')->first();

        $csStudent = Student::create([
            'admission_no' => 'ADM-CS-2',
            'roll_no' => '201',
            'name' => 'Computer Student',
            'father_name' => 'Father',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        $bioStudent = Student::create([
            'admission_no' => 'ADM-BIO-2',
            'roll_no' => '202',
            'name' => 'Biology Student',
            'father_name' => 'Father',
            'gender' => 'Male',
            'admission_date' => '2026-04-01',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $csStudent->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $csProgram->id,
            'roll_number' => '201',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $bioStudent->id,
            'class_id' => $class9->id,
            'academic_session_id' => $session->id,
            'program_id' => $bioProgram->id,
            'roll_number' => '202',
            'shift_type' => 'regular',
            'status' => 'active',
        ]);

        $exam = \App\Models\Exam::create([
            'academic_session_id' => $session->id,
            'name' => 'Annual Exam 2026',
            'status' => 'Ongoing',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
        ]);

        foreach ([$math, $cs, $bio] as $sub) {
            \App\Models\MarksConfig::create([
                'exam_id' => $exam->id,
                'class_id' => $class9->id,
                'subject' => $sub->name,
                'subject_id' => $sub->id,
                'academic_session_id' => $session->id,
                'total_marks' => 100,
                'passing_marks' => 33,
            ]);
        }

        // Insert marks for CS student in Math and CS
        \Illuminate\Support\Facades\DB::table('exam_marks')->insert([
            ['exam_id' => $exam->id, 'student_id' => $csStudent->id, 'subject_id' => $math->id, 'marks_obtained' => 85, 'is_absent' => false],
            ['exam_id' => $exam->id, 'student_id' => $csStudent->id, 'subject_id' => $cs->id, 'marks_obtained' => 90, 'is_absent' => false],
        ]);

        // Insert marks for Bio student in Math and Bio
        \Illuminate\Support\Facades\DB::table('exam_marks')->insert([
            ['exam_id' => $exam->id, 'student_id' => $bioStudent->id, 'subject_id' => $math->id, 'marks_obtained' => 75, 'is_absent' => false],
            ['exam_id' => $exam->id, 'student_id' => $bioStudent->id, 'subject_id' => $bio->id, 'marks_obtained' => 80, 'is_absent' => false],
        ]);

        $test = \Livewire\Livewire::test(\App\Livewire\Admin\Reports\ResultReport::class)
            ->set('selectedSessionId', $session->id)
            ->set('selectedExamId', $exam->id)
            ->set('selectedClassId', $class9->id)
            ->call('generate')
            ->assertHasNoErrors();

        $reportData = collect($test->get('reportData'));
        $csRow = $reportData->firstWhere('id', $csStudent->id);
        $bioRow = $reportData->firstWhere('id', $bioStudent->id);

        $this->assertNotNull($csRow);
        $this->assertNotNull($bioRow);

        // Assert CS student's CS marks vs Bio status
        $this->assertEquals(90, $csRow['subjects'][$cs->id]['score']);
        $this->assertTrue($csRow['subjects'][$bio->id]['not_enrolled']);
        $this->assertEquals('-', $csRow['subjects'][$bio->id]['score']);
        // Max total should only count enrolled subjects (Math 100 + CS 100 = 200, not 300)
        $this->assertEquals(200, $csRow['max_total']);
        $this->assertEquals(175, $csRow['total_obtained']);

        // Assert Bio student's Bio marks vs CS status
        $this->assertEquals(80, $bioRow['subjects'][$bio->id]['score']);
        $this->assertTrue($bioRow['subjects'][$cs->id]['not_enrolled']);
        $this->assertEquals('-', $bioRow['subjects'][$cs->id]['score']);
        $this->assertEquals(200, $bioRow['max_total']);
        $this->assertEquals(155, $bioRow['total_obtained']);
    }

    public function test_subject_for_class_and_for_classes_scopes()
    {
        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $unbundledClass = Classes::create([
            'name' => 'Nursery',
            'numeric_value' => 0,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach($csProgram->id, ['is_default' => true]);

        // forClass on class9: should contain CS and not Biology
        $class9Subjects = Subject::forClass($class9->id)->pluck('name')->toArray();
        $this->assertContains('Computer Science', $class9Subjects);
        $this->assertNotContains('Biology', $class9Subjects);

        // forClass on unbundledClass: should fallback to active canonical subjects
        $unbundledSubjects = Subject::forClass($unbundledClass->id)->pluck('name')->toArray();
        $this->assertContains('English', $unbundledSubjects);
        $this->assertContains('Mathematics', $unbundledSubjects);

        // forClasses on both classes
        $allSubjects = Subject::forClasses([$class9->id, $unbundledClass->id])->pluck('name')->toArray();
        $this->assertContains('Computer Science', $allSubjects);
        $this->assertContains('English', $allSubjects);

        // Accessor on Classes model
        $this->assertTrue($class9->offered_subjects->pluck('name')->contains('Computer Science'));
        $this->assertGreaterThan(0, $class9->subjects_count);
    }

    public function test_user_manager_loads_canonical_subjects_for_teaching_assignments()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach($csProgram->id, ['is_default' => true]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('addAssignment')
            ->call('loadSubjectsForClass', 0, $class9->id);

        $assignments = $component->get('teachingAssignments');
        $this->assertNotEmpty($assignments[0]['subjects']);
        $subjectNames = collect($assignments[0]['subjects'])->pluck('name')->toArray();
        $this->assertContains('Computer Science', $subjectNames);
    }

    public function test_subject_allocation_manager_loads_canonical_subjects()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $bioProgram = Program::where('code', 'SSC-BIO')->first();
        $class9->programs()->attach($bioProgram->id, ['is_default' => true]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AccessControl\SubjectAllocationManager::class)
            ->set('selectedClassId', $class9->id);

        $subjects = $component->get('subjects');
        $this->assertNotEmpty($subjects);
        $this->assertTrue(collect($subjects)->pluck('name')->contains('Biology'));
    }

    public function test_exam_manager_and_datesheet_manager_schedule_canonical_subjects()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $csProgram = Program::where('code', 'SSC-CS')->first();
        $class9->programs()->attach($csProgram->id, ['is_default' => true]);

        $exam = \App\Models\Exam::create([
            'academic_session_id' => $session->id,
            'name' => 'Term 1 Exam',
            'status' => 'Upcoming',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
        ]);

        // ExamManager: getSubjectsForClass
        $examComp = \Livewire\Livewire::test(\App\Livewire\Admin\ExamManager::class);
        $examSubjects = $examComp->instance()->getSubjectsForClass($class9->id);
        $this->assertTrue($examSubjects->pluck('name')->contains('Computer Science'));

        // DatesheetManager: autoFillSubjects
        \Livewire\Livewire::test(\App\Livewire\Admin\Datesheet\DatesheetManager::class, ['examId' => $exam->id])
            ->call('autoFillSubjects', $class9->id)
            ->assertHasNoErrors();

        $csSubject = Subject::where('name', 'Computer Science')->first();
        $config = \App\Models\MarksConfig::where('exam_id', $exam->id)
            ->where('class_id', $class9->id)
            ->where('subject', 'Computer Science')
            ->first();

        $this->assertNotNull($config);
        $this->assertEquals($csSubject->id, $config->subject_id);
    }

    public function test_academic_session_cloning_copies_programs_without_duplicate_subjects()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $initialSubjectCount = Subject::count();

        $session1 = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $session2 = \App\Models\AcademicSession::create([
            'name' => '2027-2028',
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
            'shift_type' => 'Regular',
            'is_active' => false,
        ]);

        $class1 = Classes::create([
            'name' => 'Class 9th',
            'numeric_value' => 9,
            'academic_session_id' => $session1->id,
            'shift_type' => 'regular',
        ]);

        $csProg = Program::where('code', 'SSC-CS')->first();
        $class1->programs()->attach($csProg->id, ['is_default' => true]);

        // Copy classes using AcademicSessionManager
        \Livewire\Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('fromSessionId', $session1->id)
            ->set('toSessionId', $session2->id)
            ->call('previewPromotion');

        // Assert zero new subjects were created in canonical subjects catalogue
        $this->assertEquals($initialSubjectCount, Subject::count());

        // Assert target class in session2 has the program attached
        $newClass = Classes::where('academic_session_id', $session2->id)->where('name', 'Class 9th')->first();
        $this->assertNotNull($newClass);
        $this->assertTrue($newClass->programs->pluck('code')->contains('SSC-CS'));
    }

    public function test_subject_allocation_manager_deduplicates_class_teacher_subject()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = \App\Models\AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => '12C',
            'numeric_value' => 12,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $chem = Subject::where('name', 'Chemistry')->first() ?? Subject::create([
            'name' => 'Chemistry',
            'code' => 'CHEM-12',
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        $teacher = \App\Models\User::factory()->create([
            'role' => 'teacher',
        ]);

        // Attach teacher to session as Class Teacher with subject Chemistry
        \Illuminate\Support\Facades\DB::table('session_user')->insert([
            'user_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'class_id' => $class->id,
            'class_subject' => 'Chemistry',
            'is_active' => true,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also insert an explicit subject_allocation record (as generated by timetable/schedule sync)
        \Illuminate\Support\Facades\DB::table('subject_allocations')->insert([
            'user_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $chem->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test SubjectAllocationManager
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AccessControl\SubjectAllocationManager::class)
            ->set('selectedUserId', $teacher->id);

        $allocations = $component->get('allocations');
        // Must only show 1 allocation, NOT 2 duplicates
        $this->assertCount(1, $allocations);
        $first = $allocations->first();
        $this->assertEquals('Chemistry', $first->subject_name);
        $this->assertTrue($first->is_inherent);

        // Attempting to allocate the same subject again should be rejected
        $component->set('selectedClassId', $class->id)
            ->set('selectedSubjectId', $chem->id)
            ->call('allocate')
            ->assertHasNoErrors();

        // Still exactly 1
        $this->assertCount(1, $component->get('allocations'));

        // Verify Teacher Dashboard deduplicates total subjects count
        $dashboardComponent = \Livewire\Livewire::actingAs($teacher)
            ->test(\App\Livewire\Teacher\Dashboard::class);
        $this->assertEquals(1, $dashboardComponent->viewData('stats')['subjects']);

        // Now allocate a DIFFERENT subject (e.g. Physics)
        $phys = Subject::where('name', 'Physics')->first() ?? Subject::create([
            'name' => 'Physics',
            'code' => 'PHYS-12',
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\DB::table('subject_allocations')->insert([
            'user_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $phys->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component->call('loadAllocations');
        // Now there should be 2 distinct subjects
        $this->assertCount(2, $component->get('allocations'));

        // Inherent Class Teacher subject appears first
        $updatedAllocs = $component->get('allocations');
        $this->assertTrue($updatedAllocs[0]->is_inherent);
        $this->assertEquals('Chemistry', $updatedAllocs[0]->subject_name);
        $this->assertFalse($updatedAllocs[1]->is_inherent);
        $this->assertEquals('Physics', $updatedAllocs[1]->subject_name);

        // Teacher dashboard should now report 2 total subjects
        $dashboardComponent = \Livewire\Livewire::actingAs($teacher)
            ->test(\App\Livewire\Teacher\Dashboard::class);
        $this->assertEquals(2, $dashboardComponent->viewData('stats')['subjects']);
    }
}
