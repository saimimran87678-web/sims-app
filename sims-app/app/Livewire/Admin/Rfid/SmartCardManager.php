<?php

namespace App\Livewire\Admin\Rfid;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\AcademicSession;
use App\Services\RfidCardService;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;

class SmartCardManager extends Component
{
    use WithPagination;

    // Filters & Search
    public $search = '';
    public $selectedClassId = '';
    public $statusFilter = 'all'; // 'all', 'unassigned', 'active', 'lost'
    public $activeTab = 'writer'; // 'writer', 'inspector'

    // Hardware State
    public $deskDeviceId = 'DESK-01';
    public $isReaderOnline = false;
    public $readerHwStatus = 'ok'; // 'ok', 'error', 'offline'
    public $readerHwVersion = null; // e.g. 'v2.0 (0x92)'

    // Arming & Provisioning State
    public $selectedStudentId = null;
    public $selectedStudent = null;
    public $isWritingArmed = false;
    public $autoAdvance = true;
    public $lastWrittenStudent = null;

    // Inspector State
    public $inspectCardUid = '';
    public $inspectResult = null;

    protected $listeners = ['cardWritten' => 'handleCardWritten'];

    public function mount()
    {
        $this->authorize('students.manage');
        $this->selectedClassId = ''; // Default to All Classes so students appear immediately
        $this->checkReaderStatus();
    }

    public function updatedSelectedClassId()
    {
        $this->resetPage();
        $this->selectedStudentId = null;
        $this->selectedStudent = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->syncTabMode();
    }

    public function updatedActiveTab($tab)
    {
        $this->syncTabMode();
    }

    private function syncTabMode()
    {
        $cardService = app(RfidCardService::class);
        if ($this->activeTab === 'inspector') {
            $this->isWritingArmed = false;
            $cardService->armDeviceForInspection($this->deskDeviceId);
        } else {
            $cardService->disarmInspection($this->deskDeviceId);
        }
    }

    public function checkReaderStatus()
    {
        $cardService = app(RfidCardService::class);

        // Auto-discover online reader if current device is offline
        if (!$cardService->isDeviceOnline($this->deskDeviceId)) {
            $detected = $cardService->getFirstOnlineDevice();
            if ($detected) {
                $this->deskDeviceId = $detected;
            }
        }

        $hwStatus = $cardService->getDeviceStatus($this->deskDeviceId);
        $this->isReaderOnline = $hwStatus['online'];
        $this->readerHwStatus = $hwStatus['rfid_status'];
        $this->readerHwVersion = $hwStatus['rfid_version'];

        // If in inspector tab, pull any live inspection scan from reader
        if ($this->activeTab === 'inspector') {
            $cardService->armDeviceForInspection($this->deskDeviceId);

            $inspected = $cardService->pullInspectResult($this->deskDeviceId);
            if ($inspected) {
                $this->inspectCardUid = $inspected['card_uid'];
                $this->inspectResult = $inspected;
            }
        }

        // If armed for writing, check if the reader reported a write handshake result
        if ($this->isWritingArmed && $this->selectedStudentId) {
            $writeResult = $cardService->pullWriteResult($this->deskDeviceId);
            if ($writeResult) {
                if ($writeResult['status'] === 'success') {
                    $student = Student::find($this->selectedStudentId);
                    if ($student && !empty($student->rfid_tag)) {
                        $this->isWritingArmed = false;
                        $this->lastWrittenStudent = [
                            'name' => $student->name,
                            'roll_no' => $student->roll_no,
                            'card_uid' => $student->rfid_tag,
                        ];
                        session()->flash('success_message', "Card successfully written & paired for {$student->name} (UID: {$student->rfid_tag})!");

                        // Auto advance to next unassigned student if enabled
                        if ($this->autoAdvance) {
                            $this->selectNextUnassignedStudent();
                        } else {
                            $this->selectedStudentId = null;
                            $this->selectedStudent = null;
                        }
                    }
                } elseif ($writeResult['status'] === 'failed') {
                    $this->isWritingArmed = false;
                    session()->flash('error_message', "Card writing failed on reader [{$this->deskDeviceId}]: Communication error or card was pulled away too quickly. Please tap and hold the card firmly, then try again.");
                }
            } else {
                // Fallback check if job was completed without direct result queue
                $pendingJob = $cardService->getPendingWriteJob($this->deskDeviceId);
                if (!$pendingJob) {
                    $student = Student::find($this->selectedStudentId);
                    if ($student && !empty($student->rfid_tag)) {
                        $this->isWritingArmed = false;
                        $this->lastWrittenStudent = [
                            'name' => $student->name,
                            'roll_no' => $student->roll_no,
                            'card_uid' => $student->rfid_tag,
                        ];
                        session()->flash('success_message', "Card successfully written & paired for {$student->name} (UID: {$student->rfid_tag})!");

                        if ($this->autoAdvance) {
                            $this->selectNextUnassignedStudent();
                        } else {
                            $this->selectedStudentId = null;
                            $this->selectedStudent = null;
                        }
                    }
                }
            }
        }
    }

    public function selectStudent($studentId)
    {
        $this->selectedStudentId = $studentId;
        $this->selectedStudent = Student::with('class')->find($studentId);
        $this->isWritingArmed = false;
    }

    public function armWriter()
    {
        if (!$this->selectedStudentId) {
            session()->flash('error_message', 'Please select a student first.');
            return;
        }

        $student = Student::find($this->selectedStudentId);
        if (!$student) return;

        $cardService = app(RfidCardService::class);
        $cardService->armDeviceForWriting($this->deskDeviceId, $student->id);

        $this->isWritingArmed = true;
        session()->flash('arm_message', "Reader armed! Tap blank card on {$this->deskDeviceId} now...");
    }

    public function cancelArm()
    {
        $cardService = app(RfidCardService::class);
        $cardService->disarmDevice($this->deskDeviceId);
        $this->isWritingArmed = false;
        session()->flash('info_message', 'Card writing disarmed.');
    }

    public function selectNextUnassignedStudent()
    {
        $activeSessionId = AcademicSession::getActiveSessionId();
        $next = Student::query()
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.status', 'active')
            ->whereNull('students.deleted_at')
            ->when($this->selectedClassId, function ($q) {
                $q->where('enrollments.class_id', $this->selectedClassId);
            })
            ->where(function ($q) {
                $q->whereNull('students.rfid_tag')->orWhere('students.rfid_tag', '');
            })
            ->select('students.*')
            ->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')
            ->first();

        if ($next) {
            $this->selectStudent($next->id);
            // Re-arm for next student automatically
            $this->armWriter();
        } else {
            $this->selectedStudentId = null;
            $this->selectedStudent = null;
            $this->isWritingArmed = false;
            session()->flash('success_message', 'All students in this class have assigned smart cards!');
        }
    }

    public function revokeCard($studentId)
    {
        $student = Student::findOrFail($studentId);
        $oldTag = $student->rfid_tag;

        $student->update([
            'rfid_tag' => null,
            'card_status' => 'unassigned',
        ]);

        ActivityLogger::log(
            action: 'updated',
            module: 'students',
            description: "Revoked RFID card [{$oldTag}] from student {$student->name}",
            subjectLabel: "Card Revoked: {$student->name}"
        );

        session()->flash('success_message', "Card unlinked from {$student->name}.");
    }

    public function markLost($studentId)
    {
        $student = Student::findOrFail($studentId);
        $student->update([
            'card_status' => 'lost',
        ]);

        ActivityLogger::log(
            action: 'updated',
            module: 'students',
            description: "Marked RFID card [{$student->rfid_tag}] as LOST for {$student->name}",
            subjectLabel: "Card Lost: {$student->name}"
        );

        session()->flash('warning_message', "Card marked as LOST. Gate terminal will reject this card.");
    }

    public function inspectCard()
    {
        if (empty(trim($this->inspectCardUid))) {
            return;
        }

        $normalizedUid = strtoupper(str_replace([' ', '-'], ':', trim($this->inspectCardUid)));
        $student = Student::with('class')->where('rfid_tag', $normalizedUid)->first();

        $this->inspectResult = [
            'card_uid' => $normalizedUid,
            'is_registered' => (bool) $student,
            'is_authentic' => false,
            'is_cloned' => false,
            'hmac_verified' => false,
            'verdict' => $student ? 'manual_lookup_only' : 'unregistered',
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
            'on_card_data' => null,
        ];
    }

    public function clearInspectResult()
    {
        $this->inspectCardUid = '';
        $this->inspectResult = null;
    }

    public function render()
    {
        $activeSessionId = AcademicSession::getActiveSessionId();
        $classes = Classes::where('academic_session_id', $activeSessionId)->orderBy('numeric_value')->get();

        $studentsQuery = Student::query()
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.status', 'active')
            ->whereNull('students.deleted_at')
            ->select(
                'students.*',
                'classes.name as class_name',
                'enrollments.roll_number as roll_no',
                'enrollments.class_id as enrollment_class_id'
            )
            ->when($this->selectedClassId, function ($q) {
                $q->where('enrollments.class_id', $this->selectedClassId);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('students.name', 'like', '%' . $this->search . '%')
                       ->orWhere('students.admission_no', 'like', '%' . $this->search . '%')
                       ->orWhere('enrollments.roll_number', 'like', '%' . $this->search . '%')
                       ->orWhere('students.rfid_tag', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'unassigned', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('students.rfid_tag')->orWhere('students.rfid_tag', '');
                });
            })
            ->when($this->statusFilter === 'active', function ($q) {
                $q->whereNotNull('students.rfid_tag')->where('students.rfid_tag', '!=', '')->where('students.card_status', 'active');
            })
            ->when($this->statusFilter === 'lost', function ($q) {
                $q->where('students.card_status', 'lost');
            });

        $students = $studentsQuery->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')->paginate(12);

        // Stats
        $baseCountQuery = Enrollment::query()
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.status', 'active')
            ->whereNull('students.deleted_at')
            ->when($this->selectedClassId, function ($q) {
                $q->where('enrollments.class_id', $this->selectedClassId);
            });

        $totalInClass = (clone $baseCountQuery)->count();
        $issuedInClass = (clone $baseCountQuery)
            ->whereNotNull('students.rfid_tag')
            ->where('students.rfid_tag', '!=', '')
            ->count();

        return view('livewire.admin.rfid.smart-card-manager', [
            'classes' => $classes,
            'students' => $students,
            'totalInClass' => $totalInClass,
            'issuedInClass' => $issuedInClass,
        ])->layout('components.layouts.admin', ['title' => 'Smart Card Manager & Provisioner']);
    }
}
