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

        $hash = Student::hashBlindIndex($clean);
        $student = Student::withTrashed()
            ->where(function ($q) use ($hash) {
                if ($hash) {
                    $q->where('b_form_hash', $hash)
                      ->orWhere('father_cnic_hash', $hash)
                      ->orWhere('guardian_cnic_hash', $hash);
                }
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
     * Authoritatively creates a new student and their session enrollment atomically.
     * Decouples student creation from HTTP request globals.
     */
    public function createStudentWithEnrollment(
        array $studentData,
        int $classId,
        ?string $rollNo = null,
        ?int $sectionId = null,
        string $shiftType = 'regular',
        ?int $sessionId = null,
        ?int $programId = null
    ): array {
        return DB::transaction(function () use ($studentData, $classId, $rollNo, $sectionId, $shiftType, $sessionId, $programId) {
            $targetSessionId = $sessionId ?? AcademicSession::getActiveSessionId();
            $sessionObj = AcademicSession::find($targetSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $normalizedShift = $isRegular ? 'regular' : strtolower($shiftType);
            if ($normalizedShift === 'both') {
                $normalizedShift = 'morning';
            }

            // Ensure student is active by default unless specified
            if (!isset($studentData['status'])) {
                $studentData['status'] = 'active';
            }

            $student = Student::create($studentData);

            $targetProgramId = $programId ?? DB::table('class_programs')
                ->where('class_id', $classId)
                ->orderByDesc('is_default')
                ->value('program_id');

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'academic_session_id' => $targetSessionId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'program_id' => $targetProgramId,
                'roll_number' => $rollNo,
                'shift_type' => $normalizedShift,
                'status' => $student->status ?? 'active',
            ]);

            return [
                'student' => $student,
                'enrollment' => $enrollment,
            ];
        });
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
        int|string|null $userIdOrAdmissionDate = 1,
        ?int $programId = null,
        int|Section|null $targetSection = null
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
            $sectionId = ($targetSection instanceof Section) ? $targetSection->id : (is_numeric($targetSection) ? (int)$targetSection : null);
            $userId = is_numeric($userIdOrAdmissionDate) ? (int)$userIdOrAdmissionDate : 1;
        }

        $targetProgramId = $programId ?? DB::table('class_programs')
            ->where('class_id', $classId)
            ->orderByDesc('is_default')
            ->value('program_id');

        return DB::transaction(function () use ($student, $targetSessionId, $classId, $sectionId, $shiftType, $rollNo, $targetProgramId) {
            // Activate student master profile
            $student->update([
                'status' => 'active',
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
                    'section_id' => $sectionId,
                    'program_id' => $targetProgramId,
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
            ->with(['academicSession', 'currentClass', 'schoolLeavingCertificate', 'section'])
            ->orderBy('academic_session_id', 'desc')
            ->orderBy('id', 'desc')
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

            // Exam summary with distinct exam grouping and paper-level audit
            $examMarks = DB::table('exam_marks')
                ->join('exams', 'exam_marks.exam_id', '=', 'exams.id')
                ->join('subjects', 'exam_marks.subject_id', '=', 'subjects.id')
                ->where('exam_marks.student_id', $student->id)
                ->where('exams.academic_session_id', $sessionId)
                ->select(
                    'exams.id as exam_id',
                    'exams.name as exam_name',
                    'exams.start_date as exam_date',
                    'subjects.name as subject_name',
                    'exam_marks.marks_obtained',
                    'exam_marks.max_marks',
                    'exam_marks.is_absent'
                )
                ->orderBy('exams.start_date', 'asc')
                ->orderBy('exams.id', 'asc')
                ->orderBy('subjects.name', 'asc')
                ->get();

            $examsList = [];
            $sessionExamsTotal = 0;
            $sessionExamsPassed = 0;
            $sessionTotalMarksObtained = 0;
            $sessionTotalMaxMarks = 0;
            $totalPapersCount = $examMarks->count();
            $passedPapersCount = 0;

            $groupedByExam = $examMarks->groupBy('exam_id');
            foreach ($groupedByExam as $examId => $marksCollection) {
                $examName = $marksCollection->first()->exam_name;
                $examDate = $marksCollection->first()->exam_date;
                $subjectCount = $marksCollection->count();

                $examMarksObtained = 0;
                $examMaxMarks = 0;
                $passedSubjects = 0;
                $failedSubjects = 0;
                $absentSubjects = 0;
                $subjectsList = [];

                foreach ($marksCollection as $mark) {
                    $isAbsent = (bool) ($mark->is_absent ?? false);
                    $max = (float) ($mark->max_marks ?? 100);
                    $obtained = $isAbsent ? 0 : (float) ($mark->marks_obtained ?? 0);
                    $passingThreshold = $max * 0.33; // 33% standard pass threshold

                    $isPass = !$isAbsent && ($obtained >= $passingThreshold);
                    if ($isAbsent) {
                        $absentSubjects++;
                        $subjectStatus = 'absent';
                    } elseif ($isPass) {
                        $passedSubjects++;
                        $passedPapersCount++;
                        $subjectStatus = 'passed';
                    } else {
                        $failedSubjects++;
                        $subjectStatus = 'failed';
                    }

                    $examMarksObtained += $obtained;
                    $examMaxMarks += $max;

                    $subjectsList[] = [
                        'subject_name' => $mark->subject_name,
                        'marks_obtained' => $isAbsent ? 0 : $mark->marks_obtained,
                        'max_marks' => $max,
                        'percentage' => $max > 0 ? round(($obtained / $max) * 100, 1) : 0,
                        'is_absent' => $isAbsent,
                        'status' => $subjectStatus,
                    ];
                }

                $examPercentage = $examMaxMarks > 0 ? round(($examMarksObtained / $examMaxMarks) * 100, 1) : 0;
                $examPassed = ($passedSubjects > 0 && $examPercentage >= 33.0);
                
                $examStatus = 'passed';
                if ($absentSubjects === $subjectCount) {
                    $examStatus = 'absent';
                } elseif (!$examPassed) {
                    $examStatus = 'failed';
                }

                $sessionExamsTotal++;
                if ($examPassed) {
                    $sessionExamsPassed++;
                }

                $sessionTotalMarksObtained += $examMarksObtained;
                $sessionTotalMaxMarks += $examMaxMarks;

                $examsList[] = [
                    'exam_id' => $examId,
                    'exam_name' => $examName,
                    'exam_date' => $examDate ? Carbon::parse($examDate)->format('d M, Y') : null,
                    'total_subjects' => $subjectCount,
                    'passed_subjects' => $passedSubjects,
                    'failed_subjects' => $failedSubjects,
                    'absent_subjects' => $absentSubjects,
                    'total_obtained' => $examMarksObtained,
                    'total_max' => $examMaxMarks,
                    'percentage' => $examPercentage,
                    'is_passed' => $examPassed,
                    'status' => $examStatus,
                    'subjects' => $subjectsList,
                ];
            }

            $avgMarks = $sessionTotalMaxMarks > 0 ? round(($sessionTotalMarksObtained / $sessionTotalMaxMarks) * 100, 1) : 0;

            $totalExamsAll += $sessionExamsTotal;
            $passedExamsAll += $sessionExamsPassed;

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
                'section_name' => $enrollment->section?->name ?? $student->section?->name ?? 'A',
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
                    'total' => $sessionExamsTotal,
                    'passed' => $sessionExamsPassed,
                    'total_papers' => $totalPapersCount,
                    'passed_papers' => $passedPapersCount,
                    'avg_marks' => $avgMarks,
                    'list' => $examsList,
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

        // DOB & Age
        $dobFormatted = $student->dob ? Carbon::parse($student->dob)->format('d M, Y') : null;
        $age = $student->dob ? Carbon::parse($student->dob)->age : null;

        // Resolve terminal/graduated class & section if student is not actively enrolled in the active session
        $latestEnrollment = $enrollments->first();
        $resolvedClass = $student->class 
            ?? $latestEnrollment?->currentClass 
            ?? $student->terminal_class;

        $resolvedSection = $student->section 
            ?? $latestEnrollment?->section 
            ?? $student->terminal_section;

        $className = $resolvedClass?->name ?? 'N/A';
        $sectionName = $resolvedSection?->name ?? '';

        $formattedStudent = [
            'id' => $student->id,
            'name' => $student->name,
            'admission_no' => $student->admission_no,
            'roll_no' => $student->roll_no ?? $latestEnrollment?->roll_number ?? '-',
            'status' => $student->status,
            'class_name' => $className,
            'section_name' => $sectionName,
            'gender' => ucfirst($student->gender ?? 'unspecified'),
            'dob' => $dobFormatted,
            'age' => $age,
            'phone' => $student->phone,
            'email' => $student->email,
            'address' => $student->address,
            'father_name' => $student->father_name,
            'father_cnic' => $student->father_cnic,
            'guardian_cnic' => $student->guardian_cnic,
            'guardian_relation' => $student->guardian_relation,
            'b_form_number' => $student->b_form_number,
            'profile_photo_path' => $student->profile_photo_path,
            'transport_mode' => $student->transport_mode ?? 'none',
            'vehicle_number' => $student->vehicle_number,
            'sports' => $student->sports,
            'extra_curriculars' => $student->extra_curriculars,
            'sibling_concession_status' => $student->sibling_concession_status ?? 'none',
            'custom_concession_amount' => $student->custom_concession_amount,
            'sibling_rank' => $student->sibling_rank + 1, // 1st child, 2nd child...
            'admission_date' => $student->admission_date ? Carbon::parse($student->admission_date)->format('d M, Y') : '-',
            'original_admission_date' => $student->original_admission_date 
                ? Carbon::parse($student->original_admission_date)->format('d M, Y') 
                : ($student->admission_date ? Carbon::parse($student->admission_date)->format('d M, Y') : '-'),
        ];

        // Sibling & Family Unit (Ordered Chronologically by Date of Birth)
        $familyMembers = $student->family_unit->map(function ($sib) use ($student) {
            $isSelf = ($sib->id === $student->id);
            $dobFormatted = $sib->dob ? Carbon::parse($sib->dob)->format('d M, Y') : 'N/A';
            $age = $sib->dob ? Carbon::parse($sib->dob)->age : null;
            $sibClass = $sib->class?->name ?? $sib->terminal_class?->name ?? 'Class';
            $sibSection = $sib->section?->name ?? $sib->terminal_section?->name ?? '';
            return [
                'id' => $sib->id,
                'name' => $sib->name,
                'roll_no' => $sib->roll_no ?? '-',
                'admission_no' => $sib->admission_no,
                'class_name' => $sibClass,
                'section_name' => $sibSection,
                'status' => $sib->status,
                'is_self' => $isSelf,
                'sibling_rank' => $sib->sibling_rank + 1,
                'dob' => $dobFormatted,
                'age' => $age,
                'concession_status' => $sib->sibling_concession_status ?? 'none',
                'custom_concession_amount' => $sib->custom_concession_amount,
                'profile_photo_path' => $sib->profile_photo_path,
            ];
        })->values()->all();

        return [
            'student' => $formattedStudent,
            'family' => $familyMembers,
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
