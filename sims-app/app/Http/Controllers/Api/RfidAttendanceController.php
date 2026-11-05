<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Services\RfidCardService;
use App\Services\ActivityLogger;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RfidAttendanceController extends Controller
{
    protected RfidCardService $cardService;

    public function __construct(RfidCardService $cardService)
    {
        $this->cardService = $cardService;
    }

    /**
     * POST /api/v1/rfid/scan
     * Real-time gate scan endpoint (<20ms response time).
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'card_uid' => 'required|string|max:64',
            'device_id' => 'nullable|string|max:50',
            'device_secret' => 'nullable|string',
            'scan_timestamp' => 'nullable|date',
        ]);

        $deviceId = $validated['device_id'] ?? 'GATE-01-MAIN';
        $rawUid = trim($validated['card_uid']);
        $normalizedUid = strtoupper(str_replace([' ', '-'], ':', $rawUid));
        $compactUid = strtoupper(str_replace([':', ' ', '-'], '', $rawUid));

        // Record device heartbeat
        $this->cardService->recordHeartbeat($deviceId, $request->ip());

        // 1. CHECK FOR PENDING CARD WRITING JOB
        $pendingStudentId = $this->cardService->getPendingWriteJob($deviceId);
        if ($pendingStudentId) {
            $student = Student::with('class')->find($pendingStudentId);
            if ($student) {
                $payloadData = $this->cardService->generatePayload($student, $normalizedUid);
                return response()->json([
                    'status' => 'write_mode',
                    'action' => 'write_card',
                    'device_id' => $deviceId,
                    'card_uid' => $normalizedUid,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'roll_no' => $payloadData['roll_no'],
                    'class_name' => $payloadData['class_name'],
                    'sector' => 1,
                    'start_block' => 4,
                    'num_blocks' => 3,
                    'key' => 'FFFFFFFFFFFF',
                    'payload_hex' => $payloadData['payload_hex'],
                    'hmac_hex' => $payloadData['hmac_hex'],
                    'message' => 'Write armed. Flash blocks 4, 5, 6.',
                ], 200);
            }
        }

        // 2. CHECK FOR INSPECTION MODE (Card Inspector Active)
        if ($this->cardService->getDeviceMode($deviceId) === 'inspect') {
            return response()->json([
                'status' => 'inspect_mode',
                'action' => 'inspect_card',
                'device_id' => $deviceId,
                'card_uid' => $normalizedUid,
                'sector' => 1,
                'start_block' => 4,
                'num_blocks' => 3,
                'key' => 'FFFFFFFFFFFF',
                'message' => 'Reader armed in inspect mode. Read blocks 4-6 and submit to /api/v1/rfid/card/inspect.',
            ], 200);
        }

        // 3. NORMAL ATTENDANCE SCAN MODE
        $student = Student::where('rfid_tag', $normalizedUid)
            ->orWhere('rfid_tag', $compactUid)
            ->orWhere('rfid_tag', $rawUid)
            ->first();

        if (!$student) {
            return response()->json([
                'status' => 'unknown_card',
                'action' => 'unregistered',
                'card_uid' => $normalizedUid,
                'message' => 'Card not registered in SIMS. Assign in Smart Card Manager.',
            ], 404);
        }

        // Check if card is revoked or lost
        if (in_array($student->card_status, ['suspended', 'lost'])) {
            return response()->json([
                'status' => 'blocked_card',
                'action' => 'rejected',
                'card_uid' => $normalizedUid,
                'student_name' => $student->name,
                'message' => 'Card reported ' . strtoupper($student->card_status) . '. Access Denied.',
            ], 403);
        }

        $activeSessionId = AcademicSession::getActiveSessionId();
        if (!$activeSessionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active academic session found.',
            ], 500);
        }

        // Determine current shift based on scan time
        $scanTime = !empty($validated['scan_timestamp']) ? Carbon::parse($validated['scan_timestamp']) : now();
        $scanDate = $scanTime->format('Y-m-d');
        $hour = (int) $scanTime->format('H');
        $preferredShift = ($hour < 13) ? 'morning' : 'evening';

        // Resolve active enrollment
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('academic_session_id', $activeSessionId)
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN shift_type = '{$preferredShift}' THEN 0 ELSE 1 END")
            ->orderBy('id', 'desc')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'status' => 'no_enrollment',
                'action' => 'rejected',
                'student_name' => $student->name,
                'message' => 'Student has no active enrollment in current session.',
            ], 422);
        }

        // Check existing attendance for this enrollment today
        $existing = Attendance::where('enrollment_id', $enrollment->id)
            ->where('date', $scanDate)
            ->first();

        $className = $enrollment->class?->name ?? 'Class';
        $rollNo = $enrollment->roll_number ?: ($student->roll_no ?: '-');

        // Debounce: Repeated tap within 5 minutes
        if ($existing && $existing->check_in_at && $scanTime->diffInMinutes($existing->check_in_at) < 5) {
            return response()->json([
                'status' => 'success',
                'action' => 'already_marked',
                'card_uid' => $normalizedUid,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'roll_no' => $rollNo,
                    'class_name' => $className,
                    'photo' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                ],
                'check_in_at' => $existing->check_in_at->format('h:i A'),
                'message' => "Already recorded at " . $existing->check_in_at->format('h:i A'),
            ], 200);
        }

        // Departure Check-out tap (e.g. Afternoon window, > 2 hours after check_in)
        if ($existing && $existing->check_in_at && $scanTime->diffInMinutes($existing->check_in_at) >= 120 && empty($existing->check_out_at)) {
            $existing->update([
                'check_out_at' => $scanTime,
            ]);

            return response()->json([
                'status' => 'success',
                'action' => 'checked_out',
                'card_uid' => $normalizedUid,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'roll_no' => $rollNo,
                    'class_name' => $className,
                    'photo' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                ],
                'check_in_at' => $existing->check_in_at->format('h:i A'),
                'check_out_at' => $scanTime->format('h:i A'),
                'message' => "Departure confirmed. Goodbye {$student->name}!",
            ], 200);
        }

        // Mark / Record Attendance
        $attendance = Attendance::updateOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'date' => $scanDate,
            ],
            [
                'student_id' => $student->id,
                'academic_session_id' => $activeSessionId,
                'status' => 'P',
                'source' => 'rfid',
                'check_in_at' => $scanTime,
                'device_id' => $deviceId,
                'marked_at' => now(),
            ]
        );

        // Fetch Class Attendance Progress
        $classTotal = Enrollment::where('class_id', $enrollment->class_id)
            ->where('academic_session_id', $activeSessionId)
            ->where('status', 'active')
            ->count();

        $classPresent = Attendance::where('academic_session_id', $activeSessionId)
            ->where('date', $scanDate)
            ->where('status', 'P')
            ->whereIn('enrollment_id', function ($q) use ($enrollment, $activeSessionId) {
                $q->select('id')->from('enrollments')
                    ->where('class_id', $enrollment->class_id)
                    ->where('academic_session_id', $activeSessionId)
                    ->where('status', 'active');
            })->count();

        // Audit Trail
        ActivityLogger::log(
            action: 'scanned',
            module: 'attendance',
            description: "RFID Gate Scan: {$student->name} (Roll #{$rollNo}, {$className}) checked in at {$scanTime->format('h:i A')}",
            subjectLabel: "Gate Scan: {$student->name} [{$normalizedUid}]",
            newValues: [
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'card_uid' => $normalizedUid,
                'device_id' => $deviceId,
                'check_in_at' => $scanTime->toIso8601String(),
            ]
        );

        // Send parent WhatsApp notification if phone exists
        if (!empty($student->phone)) {
            try {
                $whatsapp = app(WhatsAppService::class);
                if ($whatsapp->isConnected()) {
                    $whatsapp->sendAttendanceNotifications([
                        [
                            'id' => $student->id,
                            'name' => $student->name,
                            'roll_no' => $rollNo,
                            'phone' => $student->phone,
                        ]
                    ], 'P', $scanDate);
                }
            } catch (\Throwable $e) {
                // Non-blocking for WhatsApp error
            }
        }

        return response()->json([
            'status' => 'success',
            'action' => 'marked_present',
            'card_uid' => $normalizedUid,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'roll_no' => $rollNo,
                'class_name' => $className,
                'photo' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
            ],
            'attendance_summary' => [
                'class_present' => $classPresent,
                'class_total' => $classTotal,
                'check_in_time' => $scanTime->format('h:i A'),
            ],
            'message' => "Welcome, {$student->name}!",
        ], 200);
    }

    /**
     * POST /api/v1/rfid/card/write-confirm
     * Confirm card EEPROM writing and pair card UID with student.
     */
    public function writeConfirm(Request $request)
    {
        $validated = $request->validate([
            'card_uid' => 'required|string|max:64',
            'student_id' => 'required|integer|exists:students,id',
            'device_id' => 'nullable|string|max:50',
            'status' => 'required|string|in:success,failed',
        ]);

        $deviceId = $validated['device_id'] ?? 'DESK-01';
        $normalizedUid = strtoupper(str_replace([' ', '-'], ':', trim($validated['card_uid'])));

        // Disarm write mode
        $this->cardService->disarmDevice($deviceId);

        if ($validated['status'] !== 'success') {
            $this->cardService->recordWriteResult($deviceId, 'failed', $validated['student_id'], $normalizedUid, 'Hardware reported write failure');
            return response()->json([
                'status' => 'error',
                'message' => 'Hardware reported write failure.',
            ], 422);
        }

        $student = Student::findOrFail($validated['student_id']);

        // Check for duplicate card assigned to another student
        $existingOwner = Student::where('rfid_tag', $normalizedUid)->where('id', '!=', $student->id)->first();
        if ($existingOwner) {
            $existingOwner->update([
                'rfid_tag' => null,
                'card_status' => 'unassigned',
            ]);
        }

        $student->update([
            'rfid_tag' => $normalizedUid,
            'card_issued_at' => now(),
            'card_status' => 'active',
        ]);

        $this->cardService->recordWriteResult($deviceId, 'success', $student->id, $normalizedUid);

        ActivityLogger::log(
            action: 'updated',
            module: 'students',
            description: "Smart Card Paired: {$student->name} assigned RFID tag {$normalizedUid}",
            subjectLabel: "Card Paired: {$student->name}",
            newValues: ['rfid_tag' => $normalizedUid, 'card_status' => 'active']
        );

        return response()->json([
            'status' => 'paired',
            'student_id' => $student->id,
            'student_name' => $student->name,
            'card_uid' => $normalizedUid,
            'message' => "Card successfully paired with {$student->name}!",
        ], 200);
    }

    /**
     * POST /api/v1/rfid/card/inspect
     * Inspect card sectors and verify cryptographic HMAC seal.
     */
    public function inspect(Request $request)
    {
        try {
            $validated = $request->validate([
                'card_uid' => 'required|string',
                'device_id' => 'nullable|string',
                'payload_hex' => 'nullable|string',
            ]);

            $deviceId = $validated['device_id'] ?? 'DESK-01';
            $normalizedUid = strtoupper(str_replace([' ', '-'], ':', trim($validated['card_uid'])));
            $student = Student::with(['class'])->where('rfid_tag', $normalizedUid)->first();

            $decoded = null;
            $hmacValid = false;
            $isAuthentic = false;
            $isCloned = false;
            $verdict = 'unregistered';

            if (!empty($validated['payload_hex'])) {
                $decoded = $this->cardService->decodePayload($validated['payload_hex']);
                if ($decoded && $student) {
                    $hmacValid = RfidCardService::verifyHmac(
                        $normalizedUid,
                        $student->id,
                        $decoded['roll_no'],
                        $decoded['hmac_hex']
                    );

                    if ($hmacValid && (int) $decoded['student_id'] === (int) $student->id) {
                        $isAuthentic = true;
                        $verdict = 'authentic';
                    } else {
                        $isCloned = true;
                        $verdict = 'cloned_or_tampered';
                    }
                } elseif ($student && !$decoded) {
                    $isCloned = true;
                    $verdict = 'unsealed_clone';
                } elseif ($decoded && !$student) {
                    $verdict = 'unregistered_sealed';
                }
            } elseif ($student) {
                // UID in DB but no physical payload
                $isCloned = true;
                $verdict = 'unsealed_clone';
            }

            $auditPayload = [
                'device_id' => $deviceId,
                'card_uid' => $normalizedUid,
                'is_registered' => (bool) $student,
                'is_authentic' => $isAuthentic,
                'is_cloned' => $isCloned,
                'hmac_verified' => $hmacValid,
                'verdict' => $verdict,
                'student' => $student ? [
                    'id' => $student->id,
                    'name' => $student->name,
                    'roll_no' => $student->roll_no ?: '-',
                    'admission_no' => $student->admission_no,
                    'class_name' => $student->class?->name ?? 'Unassigned',
                    'card_status' => $student->card_status,
                    'card_issued_at' => $student->card_issued_at ? (\Illuminate\Support\Carbon::parse($student->card_issued_at)->format('d M Y, h:i A')) : null,
                    'photo' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                ] : null,
                'on_card_data' => $decoded,
            ];

            // Cache result for real-time Livewire UI auto-fetch
            $this->cardService->recordInspectResult($deviceId, $auditPayload);

            return response()->json(array_merge([
                'status' => 'success',
            ], $auditPayload), 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RFID Inspect Error: ' . $e->getMessage(), [
                'card_uid' => $request->input('card_uid'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Inspection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/rfid/sync
     * Batch offline buffered scan synchronization.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'nullable|string',
            'scans' => 'required|array',
            'scans.*.card_uid' => 'required|string',
            'scans.*.scan_timestamp' => 'required|date',
        ]);

        $deviceId = $validated['device_id'] ?? 'GATE-01-MAIN';
        $activeSessionId = AcademicSession::getActiveSessionId();
        $processed = 0;
        $skipped = 0;

        foreach ($validated['scans'] as $scan) {
            $normalizedUid = strtoupper(str_replace([' ', '-'], ':', trim($scan['card_uid'])));
            $student = Student::where('rfid_tag', $normalizedUid)->first();
            if (!$student) {
                $skipped++;
                continue;
            }

            $scanTime = Carbon::parse($scan['scan_timestamp']);
            $scanDate = $scanTime->format('Y-m-d');
            $preferredShift = ($scanTime->hour < 13) ? 'morning' : 'evening';

            $enrollment = Enrollment::where('student_id', $student->id)
                ->where('academic_session_id', $activeSessionId)
                ->where('status', 'active')
                ->orderByRaw("CASE WHEN shift_type = '{$preferredShift}' THEN 0 ELSE 1 END")
                ->orderBy('id', 'desc')
                ->first();

            if (!$enrollment) {
                $skipped++;
                continue;
            }

            Attendance::firstOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'date' => $scanDate,
                ],
                [
                    'student_id' => $student->id,
                    'academic_session_id' => $activeSessionId,
                    'status' => 'P',
                    'source' => 'rfid',
                    'check_in_at' => $scanTime,
                    'device_id' => $deviceId,
                    'marked_at' => now(),
                ]
            );
            $processed++;
        }

        return response()->json([
            'status' => 'success',
            'processed' => $processed,
            'skipped' => $skipped,
            'message' => "Successfully synced {$processed} offline scans.",
        ]);
    }

    /**
     * GET /api/v1/rfid/status
     */
    public function status(Request $request)
    {
        $deviceId = $request->query('device_id', 'GATE-01-MAIN');
        $rfidStatus = $request->query('rfid_status', 'ok');
        $rfidVersion = $request->query('rfid_version');

        $this->cardService->recordHeartbeat($deviceId, $request->ip(), $rfidStatus, $rfidVersion);

        return response()->json([
            'status' => 'online',
            'device_id' => $deviceId,
            'rfid_status' => $rfidStatus,
            'rfid_version' => $rfidVersion,
            'server_time' => now()->toIso8601String(),
            'active_session_id' => AcademicSession::getActiveSessionId(),
            'pending_write_job' => $this->cardService->getPendingWriteJob($deviceId),
        ]);
    }
}
