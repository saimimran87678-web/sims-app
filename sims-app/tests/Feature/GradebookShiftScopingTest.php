<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Subject;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookShiftScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;
    protected $session;
    protected $morningClass;
    protected $eveningClass;
    protected $morningExam;
    protected $eveningExam;

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

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $this->session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'shift_type' => 'Morning & Evening',
        ]);

        // Create morning class
        $this->morningClass = Classes::create([
            'name' => 'Class 8-Morning',
            'numeric_value' => 8,
            'shift_type' => 'morning',
            'academic_session_id' => $this->session->id,
        ]);

        // Create evening class
        $this->eveningClass = Classes::create([
            'name' => 'Class 8-Evening',
            'numeric_value' => 8,
            'shift_type' => 'evening',
            'academic_session_id' => $this->session->id,
        ]);

        // Create subjects
        $morningSubject = Subject::create([
            'name' => 'English',
            'class_id' => $this->morningClass->id,
        ]);

        $eveningSubject = Subject::create([
            'name' => 'English',
            'class_id' => $this->eveningClass->id,
        ]);

        // Create exams
        $this->morningExam = Exam::create([
            'name' => 'Morning midterm',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
            'type' => 'Midterm',
            'is_active' => true,
        ]);

        $this->eveningExam = Exam::create([
            'name' => 'Evening midterm',
            'academic_session_id' => $this->session->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
            'type' => 'Midterm',
            'is_active' => true,
        ]);

        // Create schedules
        ExamSchedule::create([
            'exam_id' => $this->morningExam->id,
            'class_id' => $this->morningClass->id,
            'subject_id' => $morningSubject->id,
        ]);

        ExamSchedule::create([
            'exam_id' => $this->eveningExam->id,
            'class_id' => $this->eveningClass->id,
            'subject_id' => $eveningSubject->id,
        ]);
    }

    public function test_admin_grade_manager_filters_exams_by_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('loadExams')
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Morning midterm') &&
                       !$exams->pluck('name')->contains('Evening midterm');
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\GradeManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('loadExams')
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Evening midterm') &&
                       !$exams->pluck('name')->contains('Morning midterm');
            });
    }

    public function test_admin_result_report_filters_exams_by_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\Reports\ResultReport::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('loadDropdowns')
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Morning midterm') &&
                       !$exams->pluck('name')->contains('Evening midterm');
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\Reports\ResultReport::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('loadDropdowns')
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Evening midterm') &&
                       !$exams->pluck('name')->contains('Morning midterm');
            });
    }

    public function test_teacher_grade_manager_filters_classes_by_shift()
    {
        $this->actingAs($this->teacher);

        // Assign teacher as class teacher for morning class, subject allocator for evening class
        DB::table('session_user')->insert([
            'user_id' => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'class_id' => $this->morningClass->id,
            'class_subject' => 'English',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $eveningSubject = Subject::where('class_id', $this->eveningClass->id)->first();

        DB::table('subject_allocations')->insert([
            'user_id' => $this->teacher->id,
            'class_id' => $this->eveningClass->id,
            'subject_id' => $eveningSubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->assertViewHas('availableClasses', function ($classes) {
                return collect($classes)->pluck('id')->contains($this->morningClass->id) &&
                       !collect($classes)->pluck('id')->contains($this->eveningClass->id);
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Teacher\GradeManager::class)
            ->assertViewHas('availableClasses', function ($classes) {
                return collect($classes)->pluck('id')->contains($this->eveningClass->id) &&
                       !collect($classes)->pluck('id')->contains($this->morningClass->id);
            });
    }

    public function test_exam_manager_filters_exams_by_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Morning midterm') &&
                       !$exams->pluck('name')->contains('Evening midterm');
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->assertViewHas('exams', function ($exams) {
                return $exams->pluck('name')->contains('Evening midterm') &&
                       !$exams->pluck('name')->contains('Morning midterm');
            });
    }

    public function test_datesheet_manager_filters_classes_by_shift()
    {
        $this->actingAs($this->admin);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\Datesheet\DatesheetManager::class, ['examId' => $this->morningExam->id])
            ->assertViewHas('allClassIds', function ($classIds) {
                return in_array($this->morningClass->id, $classIds) &&
                       !in_array($this->eveningClass->id, $classIds);
            });

        // Set shift to evening
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\Datesheet\DatesheetManager::class, ['examId' => $this->eveningExam->id])
            ->assertViewHas('allClassIds', function ($classIds) {
                return in_array($this->eveningClass->id, $classIds) &&
                       !in_array($this->morningClass->id, $classIds);
            });
    }

    public function test_exam_manager_config_modal_filters_classes_by_shift()
    {
        $this->actingAs($this->admin);

        // Create a MarksConfig for both morning and evening classes
        \App\Models\MarksConfig::create([
            'exam_id' => $this->morningExam->id,
            'class_id' => $this->morningClass->id,
            'subject' => 'English',
            'total_marks' => 100,
            'passing_marks' => 33,
        ]);

        \App\Models\MarksConfig::create([
            'exam_id' => $this->morningExam->id,
            'class_id' => $this->eveningClass->id,
            'subject' => 'English',
            'total_marks' => 100,
            'passing_marks' => 33,
        ]);

        // Under morning shift, config modal should only pre-select/contain the morning class
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('openConfigModal', $this->morningExam->id)
            ->assertSet('selectedClasses', [(string)$this->morningClass->id])
            ->assertViewHas('availableClasses', function ($classes) {
                return $classes->pluck('id')->contains($this->morningClass->id) &&
                       !$classes->pluck('id')->contains($this->eveningClass->id);
            });

        // Under evening shift, config modal should only pre-select/contain the evening class
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\ExamManager::class)
            ->set('selectedSessionId', $this->session->id)
            ->call('openConfigModal', $this->morningExam->id)
            ->assertSet('selectedClasses', [(string)$this->eveningClass->id])
            ->assertViewHas('availableClasses', function ($classes) {
                return $classes->pluck('id')->contains($this->eveningClass->id) &&
                       !$classes->pluck('id')->contains($this->morningClass->id);
            });
    }
}
