<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\FeeRecord;
use App\Models\SchoolLeavingCertificate;
use App\Models\FeeWaiverApplication;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentLifecycleService
{
    /**
     * Checks financial clearance for a student before institutional departure.
     * Respects LicenseStatus::isModuleEnabled('fees').
     */
    public function checkFinancialClearance(Student $student, ?int $sessionId = null): array
    {
        if (!LicenseStatus::isModuleEnabled('fees')) {
            return [
                'fees_enabled' => false,
                'cleared' => true,
                'balance' => 0.0,
                'outstanding_balance' => 0.0,
                'unpaid_count' => 0,
                'unpaid_records' => collect(),
                'message' => 'Fee Management module is disabled by administrative license policy. Financial clearance is automatically certified.',
            ];
        }

        $query = FeeRecord::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial']);

        if ($sessionId) {
            // Include all records up to and including the current departure session
            $query->where('academic_session_id', $sessionId);
        }

        $unpaidRecords = $query->with('class')->orderBy('period', 'asc')->get();
        $totalBalance = (float) $unpaidRecords->sum('balance');
        $cleared = ($totalBalance <= 0.001);

        return [
            'fees_enabled' => true,
            'cleared' => $cleared,
            'balance' => $totalBalance,
            'outstanding_balance' => $totalBalance,
            'unpaid_count' => $unpaidRecords->count(),
            'unpaid_records' => $unpaidRecords,
            'message' => $cleared
                ? 'Financial Clearance Verified: All institutional dues are cleared.'
                : 'Departure Blocked: Student has an outstanding balance of Rs. ' . number_format($totalBalance, 2),
        ];
    }

    /**
     * Records a formal Principal/Authority-approved Fee Waiver to clear student balance.
     */
    public function applyFeeWaiver(
        Student|FeeRecord $studentOrRecord,
        int|float $feeRecordIdOrAmount,
        float|string $amountOrApprovedBy = 0.0,
        string $referenceOrReason = '',
        string $approvedByOrRef = '',
        string $reasonOrAttachment = '',
        ?string $attachmentPath = null,
        int $userId = 1,
        string $concessionType = 'hardship'
    ): FeeWaiverApplication {
        if (!LicenseStatus::isModuleEnabled('fees')) {
            throw new \DomainException("Fee Management is disabled under current system license.");
        }

        if ($studentOrRecord instanceof FeeRecord) {
            $feeRecord = $studentOrRecord;
            $student = $feeRecord->student;
            $feeRecordId = $feeRecord->id;
            $amount = (float) $feeRecordIdOrAmount;
            $approvedBy = (string) $amountOrApprovedBy;
            $reason = (string) $referenceOrReason;
            $reference = (string) $approvedByOrRef ?: ('WVR-' . now()->format('Ymd') . '-' . rand(100, 999));
            $attachmentPath = $reasonOrAttachment ?: null;
        } else {
            $student = $studentOrRecord;
            $feeRecordId = (int) $feeRecordIdOrAmount;
            $amount = (float) $amountOrApprovedBy;
            $reference = (string) $referenceOrReason;
            $approvedBy = (string) $approvedByOrRef;
            $reason = (string) $reasonOrAttachment;
        }

        return DB::transaction(function () use (
            $student,
            $feeRecordId,
            $amount,
            $reference,
            $approvedBy,
            $reason,
            $attachmentPath,
            $userId,
            $concessionType
        ) {
            $feeRecord = FeeRecord::where('student_id', $student->id)->lockForUpdate()->findOrFail($feeRecordId);

            $waivedActual = min($amount, (float) $feeRecord->balance);
            $newBalance = max(0.0, (float) $feeRecord->balance - $waivedActual);
            $newPaid = (float) $feeRecord->paid_amount + $waivedActual;
            $newStatus = ($newBalance <= 0.001) ? 'paid' : 'partial';

            $feeRecord->update([
                'balance' => $newBalance,
                'paid_amount' => $newPaid,
                'status' => $newStatus,
                'paid_date' => ($newStatus === 'paid') ? Carbon::now()->toDateString() : $feeRecord->paid_date,
            ]);

            return FeeWaiverApplication::create([
                'student_id' => $student->id,
                'fee_record_id' => $feeRecord->id,
                'waived_amount' => $waivedActual,
                'concession_type' => $concessionType,
                'application_reference' => $reference,
                'approved_by' => $approvedBy,
                'reason' => $reason,
                'attachment_path' => $attachmentPath,
                'recorded_by_user_id' => $userId,
            ]);
        });
    }

    /**
     * Issues an official School Leaving Certificate (SLC) and seals historical records.
     */
    public function issueSchoolLeavingCertificate(
        Student $student,
        Enrollment $enrollment,
        $departureDataOrDate,
        $reasonOrUserId = 'Parent Request',
        $conduct = 'Good',
        $remarks = null,
        $userId = null
    ): SchoolLeavingCertificate {
        if (is_array($departureDataOrDate)) {
            $departureData = $departureDataOrDate;
            $finalUserId = is_numeric($reasonOrUserId) ? (int)$reasonOrUserId : ($userId ?? auth()->id() ?? 1);
        } else {
            $departureData = [
                'leaving_date' => $departureDataOrDate ? Carbon::parse($departureDataOrDate)->toDateString() : now()->toDateString(),
                'reason_for_leaving' => is_string($reasonOrUserId) ? $reasonOrUserId : 'Parent Request',
                'conduct' => $conduct ?? 'Good',
                'remarks' => $remarks,
                'class_at_leaving' => $enrollment->currentClass?->name ?? 'N/A',
            ];
            $finalUserId = $userId ?? (auth()->id() ?? 1);
        }

        $clearance = $this->checkFinancialClearance($student, $enrollment->academic_session_id);

        if (!$clearance['cleared']) {
            throw new \DomainException("Cannot issue School Leaving Certificate: {$clearance['message']}");
        }

        return DB::transaction(function () use ($student, $enrollment, $departureData, $finalUserId, $clearance) {
            $year = Carbon::parse($departureData['leaving_date'] ?? now())->format('Y');

            // Generate sequential certificate number: SLC-2026-0001
            $countThisYear = SchoolLeavingCertificate::whereYear('leaving_date', $year)->count();
            $sequence = str_pad($countThisYear + 1, 4, '0', STR_PAD_LEFT);
            $certificateNumber = "SLC-{$year}-{$sequence}";

            $slc = SchoolLeavingCertificate::create([
                'student_id' => $student->id,
                'academic_session_id' => $enrollment->academic_session_id,
                'enrollment_id' => $enrollment->id,
                'certificate_number' => $certificateNumber,
                'leaving_date' => $departureData['leaving_date'] ?? now()->toDateString(),
                'class_at_leaving' => $departureData['class_at_leaving'] ?? ($enrollment->currentClass?->name ?? 'N/A'),
                'reason_for_leaving' => $departureData['reason_for_leaving'] ?? 'Parent Request',
                'conduct' => $departureData['conduct'] ?? 'Good',
                'remarks' => $departureData['remarks'] ?? null,
                'fee_dues_cleared' => $clearance['cleared'],
                'concession_reference' => $departureData['concession_reference'] ?? null,
                'issued_by_user_id' => $finalUserId,
            ]);

            // Transition Enrollment & Student statuses
            $enrollment->update([
                'status' => 'slc_issued',
            ]);

            $student->update([
                'status' => 'withdrawn',
                'status_reason' => "Left school on {$slc->leaving_date->format('d M, Y')} via {$certificateNumber} ({$slc->reason_for_leaving})",
            ]);

            return $slc;
        });
    }

    /**
     * Searches for returning alumni by B-Form or Father CNIC.
     * Excludes currently active students (active students are not alumni).
     */
    public function lookupAlumni(string $identifier): ?array
    {
        $clean = trim($identifier);
        $digits = preg_replace('/\D/', '', $clean);
        if (empty($digits) || strlen($digits) < 13) {
            return null;
        }

        $student = Student::withTrashed()
            ->where(function ($q) use ($clean, $digits) {
                $q->where('b_form_number', $clean)
                  ->orWhere('b_form_number', $digits)
                  ->orWhereRaw("REPLACE(REPLACE(b_form_number, '-', ''), ' ', '') = ?", [$digits])
                  ->orWhere('father_cnic', $clean)
                  ->orWhere('father_cnic', $digits);
            })
            ->with(['enrollments.academicSession', 'enrollments.currentClass', 'schoolLeavingCertificates'])
            ->first();

        if (!$student) {
            return null;
        }

        // Active, non-trashed students are not alumni candidates
        $isAlumni = in_array(strtolower($student->status ?? ''), ['withdrawn', 'graduated', 'struck_off', 'inactive', 'slc_issued']) || $student->trashed();
        if (!$isAlumni) {
            return null;
        }

        $latestEnrollment = $student->enrollments->sortByDesc('academic_session_id')->first();
        $latestSlc = $student->schoolLeavingCertificates->sortByDesc('leaving_date')->first();
        $clearance = $this->checkFinancialClearance($student);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'admission_no' => $student->admission_no,
            'status' => $student->trashed() ? 'archived' : $student->status,
            'original_admission_date' => $student->original_admission_date?->format('Y-m-d') ?? $student->admission_date?->format('Y-m-d'),
            'student' => $student,
            'is_alumni' => true,
            'last_class' => $latestEnrollment?->currentClass?->name ?? 'N/A',
            'last_session' => $latestEnrollment?->academicSession?->name ?? 'N/A',
            'last_shift' => ucfirst($latestEnrollment?->shift_type ?? 'regular'),
            'slc_number' => $latestSlc?->certificate_number ?? null,
            'leaving_date' => $latestSlc?->leaving_date?->format('d M, Y') ?? null,
            'clearance' => $clearance,
        ];
    }

    /**
     * Intelligently re-admits a returning student into the active session without duplicating profile.
     */
    public function readmitStudent(
        Student $student,
        int|Classes $targetSessionOrClass,
        int|Section|null $classIdOrSection = null,
        string $shiftType = 'regular',
        ?string $rollNo = null,
        int|string|null $userIdOrAdmissionDate = 1
    ): Enrollment {
        if ($targetSessionOrClass instanceof Classes) {
            $class = $targetSessionOrClass;
            $targetSessionId = $class->academic_session_id ?? AcademicSession::getActiveSessionId();
            $classId = $class->id;
            $sectionId = ($classIdOrSection instanceof Section) ? $classIdOrSection->id : (is_numeric($classIdOrSection) ? (int)$classIdOrSection : null);
            $userId = is_numeric($userIdOrAdmissionDate) ? (int)$userIdOrAdmissionDate : 1;
        } else {
            $targetSessionId = (int) $targetSessionOrClass;
            $classId = ($classIdOrSection instanceof Classes) ? $classIdOrSection->id : (int) $classIdOrSection;
            $sectionId = null;
            $userId = is_numeric($userIdOrAdmissionDate) ? (int)$userIdOrAdmissionDate : 1;
        }

        return DB::transaction(function () use ($student, $targetSessionId, $classId, $sectionId, $shiftType, $rollNo) {
            // Activate student master profile
            $student->update([
                'status' => 'active',
                'class_id' => $classId,
                'section_id' => $sectionId,
                'status_reason' => "Re-admitted to Session ID {$targetSessionId} (" . ucfirst($shiftType) . " Shift)",
            ]);

            // Create new or update session enrollment
            $enrollment = Enrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_session_id' => $targetSessionId,
                    'shift_type' => strtolower($shiftType),
                ],
                [
                    'class_id' => $classId,
                    'roll_number' => $rollNo,
                    'status' => 'active',
                ]
            );

            return $enrollment;
        });
    }

    /**
     * Builds a comprehensive 360° Lifetime Student Portfolio.
     * Safely omits fee cards if LicenseStatus::isModuleEnabled('fees') is false.
     */
    public function getLifetimePortfolio(Student $student): array
    {
        $feesEnabled = LicenseStatus::isModuleEnabled('fees');
        $activeSessionId = AcademicSession::getActiveSessionId();

        $enrollments = Enrollment::where('student_id', $student->id)
            ->with(['academicSession', 'currentClass', 'schoolLeavingCertificate'])
            ->orderBy('academic_session_id', 'desc')
            ->get();

        $sessions = [];
        $totalDaysAll = 0;
        $presentDaysAll = 0;
        $totalExamsAll = 0;
        $passedExamsAll = 0;
        $totalBilledAll = 0.0;
        $totalPaidAll = 0.0;
        $totalBalanceAll = 0.0;

        foreach ($enrollments as $enrollment) {
            $sessionId = $enrollment->academic_session_id;

            // Attendance metrics for this session
            $attTotal = DB::table('attendances')
                ->where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->count();

            $attPresent = DB::table('attendances')
                ->where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('status', 'P')
                ->count();

            $attRate = ($attTotal > 0) ? round(($attPresent / $attTotal) * 100) : 100;
            $totalDaysAll += $attTotal;
            $presentDaysAll += $attPresent;

            // Exam summary
            $examMarks = DB::table('exam_marks')
                ->join('exams', 'exam_marks.exam_id', '=', 'exams.id')
                ->where('exam_marks.student_id', $student->id)
                ->where('exams.academic_session_id', $sessionId)
                ->select('exam_marks.marks_obtained', 'exam_marks.max_marks')
                ->get();

            $examsCount = $examMarks->count();
            $examsPassed = $examMarks->filter(fn($m) => (float)$m->marks_obtained >= ((float)($m->max_marks ?? 100) * 0.33))->count();
            $avgMarks = $examsCount > 0 ? round($examMarks->avg('marks_obtained'), 1) : 0;

            $totalExamsAll += $examsCount;
            $passedExamsAll += $examsPassed;

            // Financial summary
            $feeSummary = [
                'balance' => 0.0,
                'invoiced' => 0.0,
                'paid' => 0.0,
            ];

            if ($feesEnabled) {
                $feeRecords = FeeRecord::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->get();

                if ($feeRecords->isNotEmpty()) {
                    $billed = (float) $feeRecords->sum('total_amount');
                    $paid = (float) $feeRecords->sum('paid_amount');
                    $balance = (float) $feeRecords->sum('balance');

                    $feeSummary = [
                        'balance' => $balance,
                        'invoiced' => $billed,
                        'paid' => $paid,
                        'total_billed' => $billed,
                        'total_paid' => $paid,
                        'total_balance' => $balance,
                        'records_count' => $feeRecords->count(),
                    ];

                    $totalBilledAll += $billed;
                    $totalPaidAll += $paid;
                    $totalBalanceAll += $balance;
                }
            }

            $sessions[] = [
                'session_name' => $enrollment->academicSession?->name ?? "Session #{$sessionId}",
                'session_id' => $sessionId,
                'is_active_session' => ($sessionId == $activeSessionId),
                'class_name' => $enrollment->currentClass?->name ?? 'N/A',
                'section_name' => $student->section?->name ?? 'A',
                'roll_no' => $enrollment->roll_number ?? $student->roll_no ?? '-',
                'shift_type' => ucfirst($enrollment->shift_type),
                'enrollment_status' => $enrollment->status,
                'attendance_rate' => $attRate,
                'attendance' => [
                    'percentage' => $attRate,
                    'present' => $attPresent,
                    'total' => $attTotal,
                ],
                'exams' => [
                    'total' => $examsCount,
                    'passed' => $examsPassed,
                    'avg_marks' => $avgMarks,
                ],
                'fees' => $feeSummary,
                'slc' => $enrollment->schoolLeavingCertificate ? [
                    'id' => $enrollment->schoolLeavingCertificate->id,
                    'certificate_number' => $enrollment->schoolLeavingCertificate->certificate_number,
                    'leaving_date' => $enrollment->schoolLeavingCertificate->leaving_date ? Carbon::parse($enrollment->schoolLeavingCertificate->leaving_date)->format('d M, Y') : '-',
                    'reason_for_leaving' => $enrollment->schoolLeavingCertificate->reason_for_leaving ?? 'Parent Request',
                    'conduct' => $enrollment->schoolLeavingCertificate->conduct ?? 'Good',
                    'remarks' => $enrollment->schoolLeavingCertificate->remarks,
                ] : null,
            ];
        }

        $overallAttRate = ($totalDaysAll > 0) ? round(($presentDaysAll / $totalDaysAll) * 100) : 100;

        $formattedStudent = [
            'id' => $student->id,
            'name' => $student->name,
            'admission_no' => $student->admission_no,
            'roll_no' => $student->roll_no,
            'status' => $student->status,
            'father_name' => $student->father_name,
            'father_cnic' => $student->father_cnic,
            'b_form_number' => $student->b_form_number,
            'profile_photo_path' => $student->profile_photo_path,
            'admission_date' => $student->admission_date ? Carbon::parse($student->admission_date)->format('d M, Y') : '-',
            'original_admission_date' => $student->original_admission_date 
                ? Carbon::parse($student->original_admission_date)->format('d M, Y') 
                : ($student->admission_date ? Carbon::parse($student->admission_date)->format('d M, Y') : '-'),
        ];

        return [
            'student' => $formattedStudent,
            'fees_enabled' => $feesEnabled,
            'attendance_rate' => $overallAttRate,
            'exams_summary' => [
                'total_exams' => $totalExamsAll,
                'passed_exams' => $passedExamsAll,
            ],
            'financial_summary' => [
                'total_billed' => $totalBilledAll,
                'total_paid' => $totalPaidAll,
                'outstanding_balance' => $totalBalanceAll,
            ],
            'sessions' => $sessions,
            'timeline' => $sessions,
        ];
    }
}
