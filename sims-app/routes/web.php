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
    
    // Teacher Attendance PDF (Legacy)
    Route::get('/teacher-attendance/pdf', [\App\Http\Controllers\TeacherAttendancePDFController::class, 'download'])->name('teacher-attendance.pdf');

    // New Teacher Arrangement Report
    Route::get('/reports/teacher-arrangement', \App\Livewire\Admin\Reports\TeacherArrangementReport::class)->name('reports.teacher-arrangement');

    // Access Control (RBAC & Sharing)
    Route::middleware(['permission:access-control.manage'])->group(function () {
        Route::get('/feature-sharing', \App\Livewire\Admin\AccessControl\FeatureSharingManager::class)->name('feature-sharing');
    });

    // Subject Allocation Manager (Requires granular allocations.view)
    Route::get('/allocations', \App\Livewire\Admin\AccessControl\SubjectAllocationManager::class)->name('allocations');
    
    // Teacher Attendance
    Route::get('/teacher-attendance', \App\Livewire\Admin\TeacherAttendanceManager::class)->name('teacher-attendance');
});

Route::middleware(['auth', 'isTeacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Teacher\Dashboard::class)->name('dashboard');

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
