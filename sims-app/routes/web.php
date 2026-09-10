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

    // Schedule Management
    Route::get('/timetable', \App\Livewire\Admin\TimetableManager::class)->name('timetable');
    
    // Timetable Printing
    Route::get('/timetable/print/class/{id}', [\App\Http\Controllers\TimetablePDFController::class, 'downloadClassTimetable'])->name('timetable.print.class');
    Route::get('/timetable/print/teacher/{id}', [\App\Http\Controllers\TimetablePDFController::class, 'downloadTeacherTimetable'])->name('timetable.print.teacher');
    Route::get('/timetable/print/master', [\App\Http\Controllers\TimetablePDFController::class, 'downloadMasterTimetable'])->name('timetable.print.master');

    Route::get('/classes', \App\Livewire\Admin\ClassManager::class)->name('classes');
    Route::get('/academic-sessions', \App\Livewire\Admin\AcademicSessionManager::class)->name('academic-sessions');
    
    // Global Management
    Route::get('/attendance', \App\Livewire\Admin\AttendanceManager::class)->name('attendance');
    Route::get('/whatsapp-setup', \App\Livewire\Admin\WhatsAppSetup::class)->name('whatsapp-setup');
    
    // Teacher Attendance PDF (Legacy)
    Route::get('/teacher-attendance/pdf', [\App\Http\Controllers\TeacherAttendancePDFController::class, 'download'])->name('teacher-attendance.pdf');

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

    // Shared Admin Features (Accessible via permissions granted by Feature Sharing)
    Route::middleware(['permission:classes.manage'])->group(function () {
        Route::get('/shared/classes', \App\Livewire\Admin\ClassManager::class)->name('shared.classes');
    });
});

require __DIR__.'/auth.php';
