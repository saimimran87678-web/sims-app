<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    
    if ($user->role === 'teacher') {
        return redirect()->route('teacher.dashboard');
    }
    
    // Fallback for other roles (e.g., student)
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');

    Route::get('/users', \App\Livewire\Admin\UserManager::class)->name('users');
    Route::get('/exams', \App\Livewire\Admin\ExamManager::class)->name('exams');
    
    // Datesheet System
    Route::get('/datesheet/{examId}', \App\Livewire\Admin\Datesheet\DatesheetManager::class)->name('datesheet.manage');
    Route::get('/datesheet/{examId}/print', [\App\Http\Controllers\DatesheetController::class, 'print'])->name('datesheet.print');
    
    // Legacy/Existing Routes (keeping if needed or removing if replacing)
    // Route::get('/exams/{exam}/datesheet', ...);
    Route::get('/schedule', \App\Livewire\Admin\ScheduleManager::class)->name('schedule');
    Route::get('/classes', \App\Livewire\Admin\ClassManager::class)->name('classes');
    Route::get('/students', \App\Livewire\Admin\StudentManager::class)->name('students');
    Route::get('/academic-sessions', \App\Livewire\Admin\AcademicSessionManager::class)->name('academic-sessions');
    Route::get('/reports', \App\Livewire\Admin\Reports\ReportManager::class)->name('reports');
    
    // Global Management
    Route::get('/grades', \App\Livewire\Admin\GradeManager::class)->name('grades');
    Route::get('/attendance', \App\Livewire\Admin\AttendanceManager::class)->name('attendance');
    Route::get('/whatsapp-setup', \App\Livewire\Admin\WhatsAppSetup::class)->name('whatsapp-setup');
    Route::get('/communication-hub', \App\Livewire\Admin\CommunicationHub::class)->name('communication-hub');
    
    Route::middleware(['permission:schedule.config'])->get('/period-config', \App\Livewire\Admin\PeriodConfigManager::class)->name('period-config');
    Route::middleware(['permission:schedule.view'])->get('/view-schedule', \App\Livewire\Admin\ViewSchedule::class)->name('view-schedule');
    
    Route::get('/print-schedule', \App\Livewire\Admin\PrintSchedule::class)->name('print-schedule');

    // Access Control (RBAC & Sharing)
    Route::middleware(['permission:access-control.manage'])->group(function () {
        Route::get('/feature-sharing', \App\Livewire\Admin\AccessControl\FeatureSharingManager::class)->name('feature-sharing');
    });

    // Subject Allocation Manager (Requires granular allocations.view)
    Route::get('/allocations', \App\Livewire\Admin\AccessControl\SubjectAllocationManager::class)->name('allocations');
});

Route::middleware(['auth', 'isTeacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', function () {
        $user = \Illuminate\Support\Facades\Auth::user();

        // 1. Count Allocated Subjects
        $allocatedSubjectsCount = \Illuminate\Support\Facades\DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->count();
        
        // 2. Add Class Teacher Subject if exists
        $totalSubjects = $allocatedSubjectsCount + (!empty($user->class_subject) ? 1 : 0);

        // 3. Count Students (Students in classes where teacher teaches)
        // Get all class IDs the teacher is associated with
        $classIds = \Illuminate\Support\Facades\DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->pluck('class_id')
            ->toArray();
        
        if ($user->class_id) {
            $classIds[] = $user->class_id;
        }
        $classIds = array_unique($classIds);

        $studentsCount = 0;
        if (!empty($classIds)) {
             $studentsCount = \Illuminate\Support\Facades\DB::table('students')
                ->whereIn('class_id', $classIds)
                ->count();
        }

        // 4. Fetch Today's Schedule
        $day = now()->format('l');
        $periods = \Illuminate\Support\Facades\DB::table('period_configs')->orderBy('period_no')->get();
        $todaySchedule = \Illuminate\Support\Facades\DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('teacher_id', $user->id)
            ->where('classes.academic_session_id', \Illuminate\Support\Facades\DB::table('academic_sessions')->where('is_active', true)->value('id'))
            ->where('day', $day)
            ->where('is_substitute', 0)
            ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
            ->get()
            ->keyBy('period_no');

         $stats = [
            'students' => $studentsCount,
            'subjects' => $totalSubjects,
            'classes_today' => $todaySchedule->count(),
        ];
        return view('teacher.dashboard', compact('stats', 'periods', 'todaySchedule'));
    })->name('dashboard');

    Route::get('/attendance', \App\Livewire\Teacher\AttendanceManager::class)->name('attendance');
    Route::get('/grades', \App\Livewire\Teacher\GradeManager::class)->name('grades');
    Route::get('/students', \App\Livewire\Teacher\StudentList::class)->name('students');
    Route::get('/schedule', \App\Livewire\Teacher\ScheduleView::class)->name('schedule');
    Route::get('/reports', \App\Livewire\Teacher\Reports\ReportManager::class)->name('reports');
    Route::get('/communication-hub', \App\Livewire\Teacher\CommunicationHub::class)->name('communication-hub');

    // Shared Admin Features (Accessible via permissions granted by Feature Sharing)
    // These use the TEACHER layout but load Admin components
    Route::middleware(['permission:exams.manage'])->group(function () {
        Route::get('/shared/exams', \App\Livewire\Admin\ExamManager::class)->name('shared.exams');
        Route::get('/shared/datesheet/{examId}', \App\Livewire\Admin\Datesheet\DatesheetManager::class)->name('shared.datesheet');
    });
    
    Route::middleware(['permission:students.manage'])->group(function () {
        Route::get('/shared/students-manage', \App\Livewire\Admin\StudentManager::class)->name('shared.students');
    });

    Route::middleware(['permission:classes.manage'])->group(function () {
        Route::get('/shared/classes', \App\Livewire\Admin\ClassManager::class)->name('shared.classes');
    });
    
    Route::middleware(['permission:reports.view'])->group(function () {
        Route::get('/shared/reports', \App\Livewire\Admin\Reports\ReportManager::class)->name('shared.reports');
    });

    // Schedule Management (shared)
    Route::middleware(['permission:schedule.manage'])->group(function () {
        Route::get('/shared/schedule', \App\Livewire\Admin\ScheduleManager::class)->name('shared.schedule');
        Route::get('/shared/schedule-view', \App\Livewire\Admin\ViewSchedule::class)->name('shared.schedule-view');
    });

    Route::middleware(['permission:schedule.config'])->group(function () {
        Route::get('/shared/period-config', \App\Livewire\Admin\PeriodConfigManager::class)->name('shared.period-config');
    });
});

require __DIR__.'/auth.php';
