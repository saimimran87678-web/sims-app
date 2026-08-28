<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Vouchers/Receipts (Guest-accessible)
Route::get('/v/{token}', [\App\Http\Controllers\PublicVoucherController::class, 'show'])
    ->name('public.voucher.show')
    ->middleware('throttle:60,1');
Route::get('/v/{token}/pdf', [\App\Http\Controllers\PublicVoucherController::class, 'downloadPdf'])
    ->name('public.voucher.pdf')
    ->middleware('throttle:60,1');

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
    
    Route::post('/change-session', function (\Illuminate\Http\Request $request) {
        $sessionId = $request->input('academic_session_id');
        if ($sessionId && \App\Models\AcademicSession::where('id', $sessionId)->exists()) {
            session(['selected_academic_session_id' => $sessionId]);
            // Clear current_session_id to ensure the admin override takes precedence
            session()->forget('current_session_id');
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        if ($sessionObj && $sessionObj->shift_type === 'Regular') {
            session(['selected_shift_type' => 'regular']);
        } else {
            $shiftType = $request->input('shift_type');
            if ($shiftType && in_array($shiftType, ['morning', 'evening', 'both'])) {
                session(['selected_shift_type' => $shiftType]);
            }
            if (session('selected_shift_type') === 'regular') {
                session(['selected_shift_type' => 'morning']);
            }
        }

        return redirect()->back();
    })->name('change-session');
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
    Route::get('/substitutions', \App\Livewire\Admin\SubstitutionManager::class)->name('substitutions');
    Route::get('/substitutions/print', function() {
        $user = request()->user();
        abort_if($user->cannot('schedule.manage') && $user->cannot('substitutions.manage'), 403);
        $manager = new \App\Livewire\Admin\SubstitutionManager();
        $manager->selectedDate = request('date', now()->format('Y-m-d'));
        // Load the session requested by the user, fallback to active session
        $manager->selectedSessionId = request('session_id', \App\Models\AcademicSession::getActiveSessionId());
        $manager->loadData();
        return view('pdf.daily-substitutions', ['date' => $manager->selectedDate, 'data' => $manager->prepareReportData()]);
    })->name('substitutions.print');
    Route::get('/classes', \App\Livewire\Admin\ClassManager::class)->name('classes');
    Route::get('/students', \App\Livewire\Admin\StudentManager::class)->name('students');
    Route::get('/students/import', \App\Livewire\Admin\StudentImportManager::class)->name('students.import');
    Route::get('/academic-sessions', \App\Livewire\Admin\AcademicSessionManager::class)->name('academic-sessions');
    Route::get('/reports', \App\Livewire\Admin\Reports\ReportManager::class)->name('reports');
    
    // Global Management
    Route::get('/grades', \App\Livewire\Admin\GradeManager::class)->name('grades');
    Route::get('/attendance', \App\Livewire\Admin\AttendanceManager::class)->name('attendance');
    Route::get('/whatsapp-setup', \App\Livewire\Admin\WhatsAppSetup::class)->name('whatsapp-setup');
    Route::get('/whatsapp-templates', \App\Livewire\Admin\WhatsAppSetup::class)->name('whatsapp-templates');
    Route::get('/communication-hub', \App\Livewire\Admin\CommunicationHub::class)->name('communication-hub');
    Route::get('/settings', \App\Livewire\Admin\Settings::class)->name('settings');

    // Fee Management System
    Route::prefix('fee')->name('fee.')->group(function () {
        Route::get('/invoice-generator', \App\Livewire\Admin\Fee\InvoiceGenerator::class)->name('generator');
        Route::get('/collect', \App\Livewire\Admin\Fee\RecordPayment::class)->name('record-payment');
        Route::get('/defaulters', \App\Livewire\Admin\Fee\DefaulterList::class)->name('defaulters');
        Route::get('/ledger/{studentId}', \App\Livewire\Admin\Fee\StudentLedger::class)->name('ledger');
        Route::get('/invoice/{record}/download', \App\Http\Controllers\Admin\Fee\DownloadInvoiceController::class)->name('invoice.download');
        Route::get('/receipt/{payment}/download', \App\Http\Controllers\Admin\Fee\DownloadReceiptController::class)->name('receipt.download');
    });
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
        Route::get('/shared/students-import', \App\Livewire\Admin\StudentImportManager::class)->name('shared.students.import');
    });

    Route::middleware(['permission:classes.manage'])->group(function () {
        Route::get('/shared/classes', \App\Livewire\Admin\ClassManager::class)->name('shared.classes');
    });

    Route::middleware(['permission:sessions.manage'])->group(function () {
        Route::get('/shared/academic-sessions', \App\Livewire\Admin\AcademicSessionManager::class)->name('shared.academic-sessions');
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

    Route::middleware(['permission:substitutions.manage'])->group(function () {
        Route::get('/shared/substitutions', \App\Livewire\Admin\SubstitutionManager::class)->name('shared.substitutions');
        Route::get('/shared/substitutions/print', function() {
            $manager = new \App\Livewire\Admin\SubstitutionManager();
            $manager->selectedDate = request('date', now()->format('Y-m-d'));
            $manager->selectedSessionId = request('session_id', \App\Models\AcademicSession::getActiveSessionId());
            $manager->loadData();
            return view('pdf.daily-substitutions', ['date' => $manager->selectedDate, 'data' => $manager->prepareReportData()]);
        })->name('shared.substitutions.print');
    });

    Route::middleware(['permission:fees.manage'])->group(function () {
        Route::prefix('shared/fee')->name('shared.fee.')->group(function () {
            Route::get('/invoice-generator', \App\Livewire\Admin\Fee\InvoiceGenerator::class)->name('generator');
            Route::get('/collect', \App\Livewire\Admin\Fee\RecordPayment::class)->name('record-payment');
            Route::get('/defaulters', \App\Livewire\Admin\Fee\DefaulterList::class)->name('defaulters');
            Route::get('/ledger/{studentId}', \App\Livewire\Admin\Fee\StudentLedger::class)->name('ledger');
            Route::get('/invoice/{record}/download', \App\Http\Controllers\Admin\Fee\DownloadInvoiceController::class)->name('invoice.download');
            Route::get('/receipt/{payment}/download', \App\Http\Controllers\Admin\Fee\DownloadReceiptController::class)->name('receipt.download');
        });
    });
});

Route::get('/license-blocked', function () {
    return response()
        ->view('pages.license-blocked')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
})->name('license.blocked');

Route::get('/domain-blocked', function () {
    return response()
        ->view('pages.domain-blocked')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
})->name('domain.blocked');

Route::post('/license-blocked/activate', [\App\Http\Controllers\LicenseController::class, 'activate'])
    ->name('license.activate.post');

Route::post('/license/sync', [\App\Http\Controllers\LicenseController::class, 'sync'])
    ->name('license.sync');

Route::get('/ping', function () {
    return response()->json(['status' => 'alive']);
})->middleware('auth')->name('ping');

Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.refresh');

require __DIR__.'/auth.php';
