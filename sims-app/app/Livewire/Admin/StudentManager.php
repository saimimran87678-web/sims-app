<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\Student;
use App\Models\Classes;

class StudentManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $onlyModal = false;

    // Search & Filter
    public $search = '';
    public $selectedClassId = '';
    public $filterSport = '';
    public $filterActivity = '';
    public $filterTransport = '';
    public $filterBus = '';
    public $filterStatus = 'active';
    public $viewMode = 'grid'; // 'grid' or 'list'
    public $sortBy = 'roll_no';
    public $sortDir = 'asc';
    
    // Modal State
    public $showModal = false;
    public $showViewModal = false; // For Profile View
    public $isEditing = false;
    public $editingStudentId = null;
    public $viewingStudent = null; // Holds the student model for viewing

    // Bulk Actions & Curriculum Programs
    public $selectedStudentIds = [];
    public $bulkProgramId = '';
    public $bulkStatus = '';
    public $bulkShift = '';
    public $program_id = null;
    public $selectAll = false;

    // Bulk Edit Modal Properties
    public $showBulkEditModal = false;
    public $bulkActionType = 'status'; // 'status' or 'shift'
    public $bulkShiftOption = 'morning'; // 'morning', 'evening', 'both'
    public $bulkStudentClasses = [];
    public $bulkClassMorning = '';
    public $bulkClassEvening = '';

    // Single Student Shift Properties
    public $student_shift_option = 'morning'; // 'morning', 'evening', 'both', 'regular'
    public $singleStudentClasses = [];

    public function updatedSingleStudentClasses()
    {
        if ($this->student_shift_option === 'morning' || $this->student_shift_option === 'both') {
            $this->class_id = $this->singleStudentClasses['morning'] ?? null;
        } else {
            $this->class_id = $this->singleStudentClasses['evening'] ?? null;
        }

        if ($this->auto_roll_no && !$this->isEditing) {
            $this->autoAssignRollNo($this->class_id);
        }
        $this->resolveProgramForClass($this->class_id);
    }

    public function updatedStudentShiftOption()
    {
        if ($this->student_shift_option === 'morning' || $this->student_shift_option === 'both') {
            $this->class_id = $this->singleStudentClasses['morning'] ?? null;
        } else {
            $this->class_id = $this->singleStudentClasses['evening'] ?? null;
        }

        if ($this->auto_roll_no && !$this->isEditing) {
            $this->autoAssignRollNo($this->class_id);
        }
        $this->resolveProgramForClass($this->class_id);
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = Student::query()
                ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
                ->where('enrollments.academic_session_id', $this->selectedSessionId);
            
            $shiftType = session('selected_shift_type', 'morning');
            if ($shiftType !== 'both') {
                $query->where('enrollments.shift_type', $shiftType);
            }
            
            // Apply current filters to selection
            if ($this->selectedClassId) {
                $query->where('enrollments.class_id', $this->selectedClassId);
            }
            if ($this->search) {
                $searchHash = \App\Models\Student::hashBlindIndex($this->search);
                $query->where(function($q) use ($searchHash) {
                    $q->where('students.name', 'like', '%' . $this->search . '%')
                      ->orWhere('enrollments.roll_number', 'like', '%' . $this->search . '%')
                      ->orWhere('students.admission_no', 'like', '%' . $this->search . '%');
                    if ($searchHash) {
                        $q->orWhere('students.father_cnic_hash', $searchHash)
                          ->orWhere('students.guardian_cnic_hash', $searchHash)
                          ->orWhere('students.b_form_hash', $searchHash)
                          ->orWhere('students.phone_hash', $searchHash);
                    }
                });
            }
            if ($this->filterSport) {
                $query->where('students.sports', 'like', '%' . $this->filterSport . '%');
            }
            if ($this->filterActivity) {
                $query->where('extra_curriculars', 'like', '%' . $this->filterActivity . '%');
            }
            if ($this->filterTransport) {
                $query->where('transport_mode', $this->filterTransport);
            }
            
            $this->selectedStudentIds = $query->pluck('students.id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedStudentIds = [];
        }
    }

    // Session Management
    public $selectedSessionId;
    public $academicSessions = [];
    public $classes = [];

    protected $queryString = [
        'selectedClassId', 
        'selectedSessionId', 
        'filterSport', 
        'filterActivity', 
        'filterTransport',
        'filterBus',
        'filterStatus',
        'sortBy',
        'sortDir'
    ];

    // Form Data
    public $name = '';
    public $roll_no = '';
    public $auto_roll_no = true;
    public $admission_no = '';
    public $b_form_number = '';
    public $father_name = '';
    public $father_cnic = '';
    public $guardian_cnic = '';
    public $guardian_relation = '';
    public $is_guardian_primary = false;
    public $guardian_whatsapp_opt_in = true;
    public $sibling_concession_status = 'none';
    public $custom_concession_amount = '';
    public $detectedSiblings = [];
    public $detectedAlumni = null;
    public $isReadmission = false;
    public $readmitStudentId = null;
    public $isExistingStudentRecord = false;
    public $isActiveStudentDuplicate = false;
    public $activeStudentDetails = [];
    public $existingStudentMessage = '';
    public $phone = '';
    public $email = '';
    public $class_id = '';
    public $section_id = '';
    public $gender = 'Male';
    public $sports = [];
    public $extra_curriculars = [];
    public $transport_mode = 'none';
    public $vehicle_number = '';
    public $dob = '';
    public $admission_date = '';
    public $address = '';
    public $status = 'active';
    public $status_reason = '';
    public $photo;
    public $student_shift = 'morning';
    public $currentSessionIsRegular = false;

    // Student Lifecycle: 3-Step SLC Departure Wizard State
    public $showSlcModal = false;
    public $slcStudentId = null;
    public $slcStudent = null;
    public $slcEnrollment = null;
    public $slcStep = 1; // 1: Financial Clearance, 2: Assessment, 3: Certificate Preview & Sealing
    public $slcClearance = [];
    public $slcLeavingDate = '';
    public $slcReason = 'Parent Relocation';
    public $slcConduct = 'Good';
    public $slcRemarks = '';
    public $issuedSlc = null;

    // SLC Step 1: Fee Waiver Application Modal State
    public $showWaiverModal = false;
    public $waiverFeeRecordId = null;
    public $waiverFeeRecord = null;
    public $waiverAmount = '';
    public $waiverReference = '';
    public $waiverApprovedBy = 'Principal';
    public $waiverReason = 'Institutional Departure Clearance / Hardship Waiver';
    public $waiverConcessionType = 'hardship';

    // Student Lifecycle: 360° Lifetime History Modal State
    public $showHistoryModal = false;
    public $historyStudentId = null;
    public $historyPortfolio = null;

    public function updatedBFormNumber($value)
    {
        $clean = trim($value);
        $this->validateCnicSecurity();

        // B-form lookup & auto-fill is strictly for New Admission mode
        if ($this->isEditing) {
            return;
        }

        $digits = preg_replace('/\D/', '', $clean);
        if (strlen($digits) === 13) {
            $hash = Student::hashBlindIndex($clean);
            $existingStudent = Student::withTrashed()
                ->where('b_form_hash', $hash)
                ->first();

            if ($existingStudent) {
                $this->applyExistingStudentData($existingStudent);
            } else {
                if ($this->isExistingStudentRecord) {
                    $this->clearExistingStudentRecordLock(true);
                }
                $this->detectedAlumni = null;
            }
        } else {
            if ($this->isExistingStudentRecord) {
                $this->clearExistingStudentRecordLock(true);
            }
            $this->detectedAlumni = null;
        }
    }

    public function updatedFatherCnic($value)
    {
        $clean = trim($value);
        $this->validateCnicSecurity();

        if (preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $clean)) {
            $hash = Student::hashBlindIndex($clean);
            $guardian = \App\Models\Guardian::where('cnic_hash', $hash)->first();

            $query = Student::where('status', 'active')
                ->when($this->isEditing, fn($q) => $q->where('id', '!=', $this->editingStudentId));

            if ($guardian) {
                $query->where(function($q) use ($guardian, $hash) {
                    $q->whereHas('guardians', fn($g) => $g->where('guardians.id', $guardian->id))
                      ->orWhere('father_cnic_hash', $hash)
                      ->orWhere('guardian_cnic_hash', $hash);
                });
            } else {
                $query->where(function($q) use ($hash) {
                    $q->where('father_cnic_hash', $hash)
                      ->orWhere('guardian_cnic_hash', $hash);
                });
            }

            $this->detectedSiblings = $query->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'roll_no' => $s->roll_no,
                    'class_name' => $s->class->name ?? 'N/A',
                    'admission_no' => $s->admission_no,
                    'admission_date' => $s->admission_date?->format('d M, Y') ?? 'N/A',
                ])
                ->toArray();
        } else {
            $this->detectedSiblings = [];
        }
    }

    public function updatedGuardianCnic($value)
    {
        $this->validateCnicSecurity();
        if (empty($this->father_cnic) && preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', trim($value))) {
            $this->updatedFatherCnic($value);
        }
    }

    public function updatedIsGuardianPrimary($value)
    {
        $this->validateCnicSecurity();
        if ($value && !empty($this->guardian_cnic)) {
            $this->updatedGuardianCnic($this->guardian_cnic);
        } elseif (!$value && !empty($this->father_cnic)) {
            $this->updatedFatherCnic($this->father_cnic);
        }
    }

    public function validateCnicSecurity(): bool
    {
        $this->resetErrorBag(['b_form_number', 'father_cnic', 'guardian_cnic']);

        $bDigits = preg_replace('/\D/', '', $this->b_form_number ?? '');
        $fDigits = preg_replace('/\D/', '', $this->father_cnic ?? '');
        $gDigits = preg_replace('/\D/', '', $this->guardian_cnic ?? '');

        $hasError = false;

        if (!empty($bDigits) && strlen($bDigits) >= 5) {
            if (!empty($fDigits) && $bDigits === $fDigits) {
                $this->addError('b_form_number', "Security Alert: Student B-Form / CNIC cannot be identical to Father's CNIC.");
                $this->addError('father_cnic', "Security Alert: Father's CNIC cannot be identical to Student B-Form / CNIC.");
                $hasError = true;
            }

            if (!empty($gDigits) && $bDigits === $gDigits) {
                $this->addError('b_form_number', "Security Alert: Student B-Form / CNIC cannot be identical to Guardian's CNIC.");
                $this->addError('guardian_cnic', "Security Alert: Guardian's CNIC cannot be identical to Student B-Form / CNIC.");
                $hasError = true;
            }
        }

        if ($this->isActiveStudentDuplicate && !empty($this->activeStudentDetails)) {
            $cls = $this->activeStudentDetails['class_name'] ?? 'Class';
            $roll = $this->activeStudentDetails['roll_no'] ?? '';
            $name = $this->activeStudentDetails['name'] ?? 'Student';
            $this->addError('b_form_number', "Student {$name} is already actively enrolled in {$cls} (Roll #{$roll}). Duplicate admission is disallowed.");
            $hasError = true;
        }

        return !$hasError;
    }

    public function applyExistingStudentData(Student $student)
    {
        $this->name = $student->name;
        $this->b_form_number = $student->b_form_number ?? $this->b_form_number;
        $this->admission_no = $student->admission_no;
        $this->father_name = $student->father_name ?? '';
        $this->father_cnic = $student->father_cnic ?? '';
        $this->guardian_cnic = $student->guardian_cnic ?? '';
        $this->guardian_relation = $student->guardian_relation ?? '';
        $this->is_guardian_primary = (bool) ($student->is_guardian_primary ?? false);
        $this->guardian_whatsapp_opt_in = (bool) ($student->primaryGuardian?->whatsapp_opt_in ?? true);
        $this->sibling_concession_status = $student->sibling_concession_status ?? 'none';
        $this->custom_concession_amount = $student->custom_concession_amount ?? '';
        $this->phone = $student->phone ?? '';
        $this->email = $student->email ?? '';
        $this->gender = $student->gender ? ucfirst($student->gender) : 'Male';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->address = $student->address ?? '';
        $this->sports = $student->sports ? (is_array($student->sports) ? $student->sports : explode(',', $student->sports)) : [];
        $this->extra_curriculars = $student->extra_curriculars ? (is_array($student->extra_curriculars) ? $student->extra_curriculars : explode(',', $student->extra_curriculars)) : [];
        $this->transport_mode = $student->transport_mode ?? 'none';
        $this->vehicle_number = $student->vehicle_number ?? '';
        $this->admission_date = $student->admission_date ? $student->admission_date->format('Y-m-d') : ($this->admission_date ?: now()->format('Y-m-d'));

        if ($this->father_cnic) {
            $this->updatedFatherCnic($this->father_cnic);
        }

        $this->isExistingStudentRecord = true;

        $isDeparted = in_array(strtolower($student->status ?? 'active'), ['withdrawn', 'slc_issued', 'graduated', 'struck_off', 'inactive']) || $student->trashed();

        if ($isDeparted) {
            // Case 1: Returning Alumni / Departed Student -> Enable Re-Admission
            $this->isActiveStudentDuplicate = false;
            $this->activeStudentDetails = [];
            $this->isReadmission = true;
            $this->readmitStudentId = $student->id;

            $service = app(\App\Services\StudentLifecycleService::class);
            $this->detectedAlumni = $service->lookupAlumni($student->b_form_number ?: ($student->father_cnic ?: ''));

            $statusName = $student->trashed() ? 'Archived' : ucfirst($student->status ?? 'Departed');
            $this->existingStudentMessage = "Institutional alumni record found for B-Form ({$student->b_form_number}). Student departed as {$statusName}. Historical profile data is locked for transcript integrity. Select class and roll number to re-admit.";
        } else {
            // Case 2: Currently Active Enrolled Student -> Block Duplicate Admission & Disallow Re-Admission
            $this->isActiveStudentDuplicate = true;
            $this->isReadmission = false;
            $this->readmitStudentId = null;
            $this->detectedAlumni = null;

            $activeEnrollment = $student->enrollments()
                ->where('academic_session_id', $this->selectedSessionId)
                ->where('status', 'active')
                ->first();

            if (!$activeEnrollment) {
                $activeEnrollment = $student->enrollments()->where('status', 'active')->latest('id')->first();
            }

            $cls = $activeEnrollment ? Classes::find($activeEnrollment->class_id) : Classes::find($student->class_id);
            $className = $cls ? $cls->name : 'Assigned Class';
            $rollNo = $activeEnrollment ? $activeEnrollment->roll_number : ($student->roll_no ?: 'N/A');
            $session = $activeEnrollment ? \App\Models\AcademicSession::find($activeEnrollment->academic_session_id) : \App\Models\AcademicSession::find($this->selectedSessionId);
            $sessionName = $session ? $session->name : 'Current Session';
            $shiftName = ucfirst($activeEnrollment?->shift_type ?? 'Regular');

            $this->activeStudentDetails = [
                'id' => $student->id,
                'name' => $student->name,
                'admission_no' => $student->admission_no,
                'class_name' => $className,
                'roll_no' => $rollNo,
                'session_name' => $sessionName,
                'shift_name' => $shiftName,
                'status' => 'active',
            ];

            $this->existingStudentMessage = "Student {$student->name} (Admission #{$student->admission_no}) is ALREADY an active student currently enrolled in {$className} (Roll #{$rollNo}, {$sessionName}). An active student cannot be added again or re-admitted.";

            $this->addError('b_form_number', "Student {$student->name} is already actively enrolled in {$className} (Roll #{$rollNo}). Duplicate admission is disallowed.");
        }

        $this->validateCnicSecurity();
    }

    public function clearExistingStudentRecordLock(bool $keepBForm = false)
    {
        $currentBform = $this->b_form_number;
        $this->isExistingStudentRecord = false;
        $this->isActiveStudentDuplicate = false;
        $this->activeStudentDetails = [];
        $this->isReadmission = false;
        $this->readmitStudentId = null;
        $this->existingStudentMessage = '';
        $this->detectedAlumni = null;
        $this->detectedSiblings = [];

        $this->reset([
            'name', 'admission_no', 'father_name', 'father_cnic', 'guardian_cnic', 'guardian_relation',
            'is_guardian_primary', 'sibling_concession_status', 'custom_concession_amount',
            'phone', 'email', 'gender', 'dob', 'address', 'photo', 'sports', 'extra_curriculars',
            'transport_mode', 'vehicle_number'
        ]);

        if ($keepBForm) {
            $this->b_form_number = $currentBform;
        } else {
            $this->b_form_number = '';
        }

        $this->resetErrorBag(['b_form_number', 'father_cnic', 'guardian_cnic']);
    }

    public function initiateReadmit($studentId)
    {
        $student = Student::withTrashed()->findOrFail($studentId);
        $this->isEditing = false;
        $this->applyExistingStudentData($student);
        $this->showModal = true;
    }

    public function cancelReadmission()
    {
        $this->clearExistingStudentRecordLock(false);
    }

    // Option Editing State
    public $newSportName = '';
    public $newActivityName = '';
    public $editingOptionId = null;
    public $editingOptionName = '';
    public const BUS_OPTIONS = ['135', '147']; // Strict options for School Bus
    public const TRANSPORT_OPTIONS = [
        'school_bus' => 'School Bus',
        'private_van' => 'Private Van',
        'car' => 'Personal Car',
        'bike' => 'Bike',
        'bicycle' => 'Bicycle',
        'walk' => 'Walk',
        'none' => 'None',
    ];

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_cnic' => [
                'nullable', 
                'string', 
                'max:15', 
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/',
                function ($attribute, $value, $fail) {
                    $bDigits = preg_replace('/\D/', '', $this->b_form_number ?? '');
                    $fDigits = preg_replace('/\D/', '', $value ?? '');

                    if (!empty($bDigits) && !empty($fDigits) && $bDigits === $fDigits) {
                        $fail("Security Alert: Father's CNIC cannot be identical to Student's B-Form / CNIC.");
                    }
                }
            ],
            'guardian_cnic' => [
                'nullable', 
                'string', 
                'max:15', 
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/',
                function ($attribute, $value, $fail) {
                    $bDigits = preg_replace('/\D/', '', $this->b_form_number ?? '');
                    $gDigits = preg_replace('/\D/', '', $value ?? '');

                    if (!empty($bDigits) && !empty($gDigits) && $bDigits === $gDigits) {
                        $fail("Security Alert: Guardian's CNIC cannot be identical to Student's B-Form / CNIC.");
                    }
                }
            ],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            'is_guardian_primary' => ['boolean'],
            'sibling_concession_status' => ['required', 'string', 'in:none,policy_eligible,custom_waiver,disallowed'],
            'custom_concession_amount' => ['nullable', 'numeric', 'min:0'],
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'admission_no' => [
                'required', 
                'string', 
                Rule::unique('students', 'admission_no')->ignore($this->editingStudentId ?: $this->readmitStudentId)
            ],
            'b_form_number' => [
                'nullable', 
                'string', 
                'max:20',
                function ($attribute, $value, $fail) {
                    $bDigits = preg_replace('/\D/', '', $value ?? '');
                    $fDigits = preg_replace('/\D/', '', $this->father_cnic ?? '');
                    $gDigits = preg_replace('/\D/', '', $this->guardian_cnic ?? '');

                    if (!empty($bDigits) && strlen($bDigits) >= 5) {
                        if (!empty($fDigits) && $bDigits === $fDigits) {
                            $fail("Security Alert: Student B-Form / CNIC cannot be identical to Father's CNIC.");
                        }
                        if (!empty($gDigits) && $bDigits === $gDigits) {
                            $fail("Security Alert: Student B-Form / CNIC cannot be identical to Guardian's CNIC.");
                        }
                    }
                }
            ],
            'gender' => 'required|in:Male,Female,Other',
            'sports' => 'array',
            'extra_curriculars' => 'array',
            'transport_mode' => 'required|string',
            'vehicle_number' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'admission_date' => 'required|date',
            'photo' => 'nullable|image|max:1024',
            'address' => 'nullable|string|max:1000',
        ];

        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

        if ($isRegular) {
            $rules['class_id'] = 'required|exists:classes,id';
            $rules['roll_no'] = 'required|string';
            $rules['student_shift'] = 'required|string|in:regular';
        } else {
            $rules['student_shift_option'] = 'required|in:morning,evening,both';
            
            if ($this->student_shift_option === 'morning') {
                $rules['singleStudentClasses.morning'] = 'required|exists:classes,id';
                $rules['roll_no'] = 'required|string';
            } elseif ($this->student_shift_option === 'evening') {
                $rules['singleStudentClasses.evening'] = 'required|exists:classes,id';
                $rules['roll_no'] = 'required|string';
            } elseif ($this->student_shift_option === 'both') {
                $rules['singleStudentClasses.morning'] = 'required|exists:classes,id';
                $rules['singleStudentClasses.evening'] = 'required|exists:classes,id';
                $rules['roll_no'] = 'required|string';
            }
        }

        return $rules;
    }

    public function mount()
    {
        $this->authorize('students.manage');
        $this->academicSessions = \App\Models\AcademicSession::active()->orderBy('start_date', 'desc')->get();
        
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        // Enforce Data Scope
        if (!auth()->user()->can('students.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }
        
        $this->loadClasses();

        // Auto-open add modal when navigated from dashboard quick-action
        if (request()->boolean('open_add_modal')) {
            $this->openModal();
        }
    }

    public function loadClasses()
    {
        if ($this->selectedSessionId) {
            $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

            $this->classes = Classes::forSession($this->selectedSessionId, $shiftType)
                ->orderBy('numeric_value')
                ->get();
        } else {
            $this->classes = collect();
        }
        
        // Reset selected class if it doesn't belong to the new session
        if ($this->selectedClassId && !$this->classes->contains('id', $this->selectedClassId)) {
            $this->selectedClassId = null;
        }
    }

    public function updatedSelectedSessionId()
    {
        $this->loadClasses();
    }

    public function updatedClassId($value)
    {
        if ($this->auto_roll_no && !$this->isEditing) {
            $this->autoAssignRollNo($value);
        }
        $this->resolveProgramForClass($value);
    }

    public function updatedAutoRollNo($value)
    {
        if ($value) {
            $this->autoAssignRollNo($this->class_id);
        }
    }

    protected function autoAssignRollNo($classId)
    {
        if ($classId) {
            $maxRollNo = \App\Models\Enrollment::where('class_id', $classId)
                ->where('academic_session_id', $this->selectedSessionId)
                ->where('shift_type', $this->student_shift)
                ->get()
                ->map(fn($e) => (int)$e->roll_number)
                ->max();
            $this->roll_no = (string)($maxRollNo ? ($maxRollNo + 1) : 1);
        } else {
            $this->roll_no = '';
        }
    }

    protected function adjustRollNumbers($classId, $targetRollNo)
    {
        $target = (int)$targetRollNo;
        if ($target <= 0) return;

        $enrollments = \App\Models\Enrollment::where('class_id', $classId)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $this->student_shift)
            ->when($this->editingStudentId, function ($query) {
                $query->where('student_id', '!=', $this->editingStudentId);
            })
            ->get();

        $enrollmentsToShift = $enrollments->filter(function ($e) {
            return (int)$e->roll_number >= (int)$this->roll_no;
        })->sortBy(function ($e) {
            return (int)$e->roll_number;
        });

        $nextRollNo = $target + 1;
        foreach ($enrollmentsToShift as $enrollment) {
            $enrollment->update(['roll_number' => (string)$nextRollNo]);
            $nextRollNo++;
        }
    }

    // Reset pagination and selection when filtering
    public function updatedSearch() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedSelectedClassId() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedFilterSport() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedFilterActivity() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedFilterTransport() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedFilterBus() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedFilterStatus() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedSortBy() { $this->resetPage(); $this->selectedStudentIds = []; }
    public function updatedSortDir() { $this->resetPage(); $this->selectedStudentIds = []; }

    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    #[On('open-add-student-modal')]
    public function openModal()
    {
        $this->isExistingStudentRecord = false;
        $this->isActiveStudentDuplicate = false;
        $this->activeStudentDetails = [];
        $this->existingStudentMessage = '';
        $this->reset(['name', 'roll_no', 'auto_roll_no', 'admission_no', 'b_form_number', 'father_name', 'father_cnic', 'guardian_cnic', 'guardian_relation', 'is_guardian_primary', 'sibling_concession_status', 'custom_concession_amount', 'detectedSiblings', 'detectedAlumni', 'isReadmission', 'readmitStudentId', 'phone', 'email', 'isEditing', 'editingStudentId', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address', 'program_id', 'gender', 'newSportName', 'newActivityName', 'editingOptionId', 'editingOptionName', 'status', 'isExistingStudentRecord', 'isActiveStudentDuplicate', 'activeStudentDetails', 'existingStudentMessage']);
        $this->guardian_whatsapp_opt_in = true;
        $this->resetErrorBag(['b_form_number', 'father_cnic', 'guardian_cnic']);
        $this->admission_date = now()->format('Y-m-d');
        
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        
        $this->student_shift_option = $isRegular ? 'regular' : 'morning';
        $this->student_shift = $isRegular ? 'regular' : 'morning';
        $this->singleStudentClasses = [];
        $this->class_id = $this->selectedClassId; // Default to currently selected filter

        if ($this->class_id) {
            $cls = Classes::withoutGlobalScope('active_session')->find($this->class_id);
            if ($cls && $cls->shift_type) {
                if ($cls->shift_type === 'morning') {
                    $this->student_shift_option = 'morning';
                    $this->singleStudentClasses['morning'] = $this->class_id;
                } elseif ($cls->shift_type === 'evening') {
                    $this->student_shift_option = 'evening';
                    $this->singleStudentClasses['evening'] = $this->class_id;
                }
            } else {
                $this->singleStudentClasses['regular'] = $this->class_id;
            }
            $this->autoAssignRollNo($this->class_id);
            $this->resolveProgramForClass($this->class_id);
        } else {
            $this->roll_no = '';
            $this->program_id = null;
        }
        $this->showModal = true;
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        
        $this->isEditing = true;
        $this->editingStudentId = $id;
        $this->isExistingStudentRecord = false;
        $this->existingStudentMessage = '';
        $this->resetErrorBag(['b_form_number', 'father_cnic', 'guardian_cnic']);
        $this->name = $student->name;
        
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

        $enrollments = \App\Models\Enrollment::where('student_id', $id)
            ->where('academic_session_id', $this->selectedSessionId)
            ->get();

        $hasMorning = $enrollments->contains('shift_type', 'morning');
        $hasEvening = $enrollments->contains('shift_type', 'evening');

        $this->singleStudentClasses = [];

        if ($isRegular) {
            $this->student_shift_option = 'regular';
            $this->student_shift = 'regular';
            $enrollment = $enrollments->first();
            $this->class_id = $enrollment ? $enrollment->class_id : $student->class_id;
            $this->roll_no = $enrollment ? $enrollment->roll_number : $student->roll_no;
            $this->status = $enrollment ? $enrollment->status : ($student->status ?? 'active');
        } else {
            if ($hasMorning && $hasEvening) {
                $this->student_shift_option = 'both';
                $morningEnrollment = $enrollments->firstWhere('shift_type', 'morning');
                $eveningEnrollment = $enrollments->firstWhere('shift_type', 'evening');
                
                $this->singleStudentClasses['morning'] = $morningEnrollment->class_id;
                $this->singleStudentClasses['evening'] = $eveningEnrollment->class_id;
                
                $this->class_id = $morningEnrollment->class_id;
                $this->roll_no = $morningEnrollment->roll_number;
                $this->status = $morningEnrollment->status;
            } elseif ($hasMorning) {
                $this->student_shift_option = 'morning';
                $morningEnrollment = $enrollments->firstWhere('shift_type', 'morning');
                $this->singleStudentClasses['morning'] = $morningEnrollment->class_id;
                
                $this->class_id = $morningEnrollment->class_id;
                $this->roll_no = $morningEnrollment->roll_number;
                $this->status = $morningEnrollment->status;
            } elseif ($hasEvening) {
                $this->student_shift_option = 'evening';
                $eveningEnrollment = $enrollments->firstWhere('shift_type', 'evening');
                $this->singleStudentClasses['evening'] = $eveningEnrollment->class_id;
                
                $this->class_id = $eveningEnrollment->class_id;
                $this->roll_no = $eveningEnrollment->roll_number;
                $this->status = $eveningEnrollment->status;
            } else {
                $this->student_shift_option = 'morning';
                $this->class_id = $student->class_id;
                $this->roll_no = $student->roll_no;
                $this->status = $student->status ?? 'active';
            }
        }

        $this->admission_no = $student->admission_no;
        $this->b_form_number = $student->b_form_number ?? '';
        $this->father_name = $student->father_name;
        $this->father_cnic = $student->father_cnic ?? '';
        $this->guardian_cnic = $student->guardian_cnic ?? '';
        $this->guardian_relation = $student->guardian_relation ?? '';
        $this->is_guardian_primary = (bool) ($student->is_guardian_primary ?? false);
        $this->guardian_whatsapp_opt_in = (bool) ($student->primaryGuardian?->whatsapp_opt_in ?? true);
        $this->sibling_concession_status = $student->sibling_concession_status ?? 'none';
        $this->custom_concession_amount = $student->custom_concession_amount ?? '';
        $this->updatedFatherCnic($this->father_cnic);
        $this->phone = $student->phone;
        $this->email = $student->email;
        $this->section_id = $student->section_id;
        $this->gender = $student->gender ? ucfirst($student->gender) : 'Male';
        
        $this->sports = $student->sports ? explode(',', $student->sports) : [];
        $this->extra_curriculars = $student->extra_curriculars ? explode(',', $student->extra_curriculars) : [];
        $this->transport_mode = $student->transport_mode ?? 'none';
        $this->vehicle_number = $student->vehicle_number ?? '';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->admission_date = $student->admission_date ? $student->admission_date->format('Y-m-d') : ($student->created_at ? $student->created_at->format('Y-m-d') : now()->format('Y-m-d'));
        $this->address = $student->address;

        // Load assigned curriculum program track
        $activeEnrollment = $enrollments->first();
        $this->program_id = $activeEnrollment ? $activeEnrollment->program_id : null;
        if (!$this->program_id && $this->class_id) {
            $this->resolveProgramForClass($this->class_id);
        }

        $this->auto_roll_no = false;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function view($id)
    {
        $this->viewingStudent = Student::with(['class', 'siblings'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function save()
    {
        // 1. Guard against new admissions in historical/concluded sessions
        if ($this->isHistoricalSession && !$this->isEditing) {
            session()->flash('error', 'New admissions cannot be added to a concluded academic session. Please switch to the active academic session.');
            return;
        }

        $this->roll_no = trim($this->roll_no);
        $this->admission_no = trim($this->admission_no);

        if (!$this->validateCnicSecurity()) {
            return;
        }

        if ($this->isActiveStudentDuplicate) {
            $this->addError('b_form_number', "Cannot proceed: Student is already actively enrolled in school. Duplicate admission is disallowed.");
            return;
        }

        $validated = $this->validate();

        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

        if ($isRegular) {
            $this->student_shift = 'regular';
        } else {
            if ($this->student_shift_option === 'both') {
                $this->class_id = $this->singleStudentClasses['morning'] ?? $this->class_id;
                $this->student_shift = 'morning';
            } elseif ($this->student_shift_option === 'morning') {
                $this->class_id = $this->singleStudentClasses['morning'] ?? $this->class_id;
                $this->student_shift = 'morning';
            } elseif ($this->student_shift_option === 'evening') {
                $this->class_id = $this->singleStudentClasses['evening'] ?? $this->class_id;
                $this->student_shift = 'evening';
            }
        }

        if (!$this->program_id && $this->class_id) {
            $this->resolveProgramForClass($this->class_id);
        }

        // 1-Click Re-Admission of returning student / Existing B-Form Student
        if ($this->isReadmission && $this->readmitStudentId) {
            $lifecycleService = app(\App\Services\StudentLifecycleService::class);
            $student = Student::withTrashed()->findOrFail($this->readmitStudentId);
            
            if ($student->trashed()) {
                $student->restore();
            }

            $student->update([
                'phone' => $this->phone ?: $student->phone,
                'email' => $this->email ?: $student->email,
                'address' => $this->address ?: $student->address,
                'transport_mode' => $this->transport_mode,
                'vehicle_number' => $this->vehicle_number,
                'status' => 'active',
                'status_reason' => 'Admitted/Re-admitted to Session ID ' . $this->selectedSessionId,
            ]);

            $lifecycleService->readmitStudent(
                $student,
                $this->selectedSessionId,
                $this->class_id,
                $this->student_shift,
                $this->roll_no,
                auth()->id() ?? 1,
                $this->program_id,
                $this->section_id ?: null
            );

            // If dual shift session and both shifts selected:
            if ($this->student_shift_option === 'both') {
                $morningClassId = $this->singleStudentClasses['morning'] ?? $this->class_id;
                $eveningClassId = $this->singleStudentClasses['evening'] ?? $this->class_id;

                \App\Models\Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => 'morning',
                    ],
                    [
                        'class_id' => $morningClassId,
                        'section_id' => $this->section_id ?: null,
                        'program_id' => $this->program_id,
                        'roll_number' => $this->roll_no,
                        'status' => 'active',
                    ]
                );

                \App\Models\Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => 'evening',
                    ],
                    [
                        'class_id' => $eveningClassId,
                        'section_id' => $this->section_id ?: null,
                        'program_id' => $this->program_id,
                        'roll_number' => $this->roll_no,
                        'status' => 'active',
                    ]
                );
            }

            $this->showModal = false;
            $this->isReadmission = false;
            $this->readmitStudentId = null;
            $this->isExistingStudentRecord = false;
            $this->isActiveStudentDuplicate = false;
            $this->activeStudentDetails = [];
            $this->existingStudentMessage = '';
            session()->flash('message', "Student {$student->name} admitted successfully with existing institutional record!");
            return;
        }

        // Safety catch: If B-form already exists in DB for a new admission, protect integrity and avoid duplicate
        if (!$this->isEditing) {
            $digits = preg_replace('/\D/', '', $this->b_form_number ?? '');
            if (!empty($digits) && strlen($digits) === 13) {
                $matchedStudent = Student::withTrashed()
                    ->where(function ($q) use ($digits) {
                        $q->where('b_form_number', $this->b_form_number)
                          ->orWhere('b_form_number', $digits)
                          ->orWhereRaw("REPLACE(REPLACE(b_form_number, '-', ''), ' ', '') = ?", [$digits]);
                    })
                    ->first();

                if ($matchedStudent) {
                    $this->applyExistingStudentData($matchedStudent);

                    if ($this->isActiveStudentDuplicate) {
                        $cls = $this->activeStudentDetails['class_name'] ?? 'Class';
                        $roll = $this->activeStudentDetails['roll_no'] ?? '';
                        $this->addError('b_form_number', "Student {$matchedStudent->name} is already actively enrolled in {$cls} (Roll #{$roll}). Duplicate admission is disallowed.");
                        return;
                    }

                    $lifecycleService = app(\App\Services\StudentLifecycleService::class);
                    if ($matchedStudent->trashed()) {
                        $matchedStudent->restore();
                    }
                    $matchedStudent->update([
                        'phone' => $this->phone ?: $matchedStudent->phone,
                        'email' => $this->email ?: $matchedStudent->email,
                        'address' => $this->address ?: $matchedStudent->address,
                        'transport_mode' => $this->transport_mode,
                        'vehicle_number' => $this->vehicle_number,
                        'status' => 'active',
                        'status_reason' => 'Admitted/Re-admitted to Session ID ' . $this->selectedSessionId,
                    ]);
                    $lifecycleService->readmitStudent(
                        $matchedStudent,
                        $this->selectedSessionId,
                        $this->class_id,
                        $this->student_shift,
                        $this->roll_no,
                        auth()->id() ?? 1,
                        $this->program_id
                    );
                    $this->showModal = false;
                    $this->isReadmission = false;
                    $this->readmitStudentId = null;
                    $this->isExistingStudentRecord = false;
                    $this->isActiveStudentDuplicate = false;
                    $this->activeStudentDetails = [];
                    $this->existingStudentMessage = '';
                    session()->flash('message', "Student {$matchedStudent->name} admitted successfully with existing institutional record!");
                    return;
                }
            }
        }

        // Serialize arrays
        $data = [
            'name' => $this->name,
            'admission_no' => $this->admission_no,
            'b_form_number' => $this->b_form_number ?: null,
            'father_name' => $this->father_name,
            'father_cnic' => $this->father_cnic ?: null,
            'guardian_cnic' => $this->guardian_cnic ?: null,
            'guardian_relation' => $this->guardian_relation ?: null,
            'is_guardian_primary' => (bool) $this->is_guardian_primary,
            'sibling_concession_status' => $this->sibling_concession_status ?: 'none',
            'custom_concession_amount' => $this->sibling_concession_status === 'custom_waiver' && is_numeric($this->custom_concession_amount) ? $this->custom_concession_amount : null,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => strtolower($this->gender),
            'sports' => !empty($this->sports) ? implode(',', $this->sports) : null,
            'extra_curriculars' => !empty($this->extra_curriculars) ? implode(',', $this->extra_curriculars) : null,
            'transport_mode' => $this->transport_mode,
            'vehicle_number' => $this->vehicle_number,
            'dob' => $this->dob ?: null,
            'admission_date' => $this->admission_date ?: null,
            'original_admission_date' => $this->admission_date ?: null,
            'address' => $this->address,
        ];

        // New admissions are always active. Editing never overwrites audited lifecycle status.
        if (!$this->isEditing) {
            $data['status'] = 'active';
        }

        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $data['profile_photo_path'] = $path;
        }

        // Concluded Session Check: Protect historical session records while allowing governed biographical fixes
        if ($this->isHistoricalSession) {
            if (!$this->isEditing) {
                session()->flash('error', 'New admissions cannot be added to a concluded academic session. Please switch to the active academic session.');
                return;
            }

            // Governed Clerical Correction to student master biographical info
            $student = Student::findOrFail($this->editingStudentId);
            $student->update($data);
            $this->showModal = false;
            $this->reset(['name', 'roll_no', 'admission_no', 'b_form_number', 'father_name', 'father_cnic', 'guardian_cnic', 'guardian_relation', 'is_guardian_primary', 'phone', 'email', 'isEditing', 'dob', 'address', 'photo']);
            session()->flash('message', 'Student biographical information updated successfully (historical session records preserved).');
            return;
        }

        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

        if ($isRegular) {
            $this->student_shift = 'regular';
        } else {
            if ($this->student_shift_option === 'both') {
                $this->class_id = $this->singleStudentClasses['morning'];
                $this->student_shift = 'morning';
            } elseif ($this->student_shift_option === 'morning') {
                $this->class_id = $this->singleStudentClasses['morning'];
                $this->student_shift = 'morning';
            } elseif ($this->student_shift_option === 'evening') {
                $this->class_id = $this->singleStudentClasses['evening'];
                $this->student_shift = 'evening';
            }
        }

        DB::transaction(function() use ($data, $isRegular) {
            $this->adjustRollNumbers($this->class_id, $this->roll_no);

            if ($this->isEditing) {
                Student::where('id', $this->editingStudentId)->update($data);
                $student = Student::findOrFail($this->editingStudentId);
                session()->flash('message', 'Student updated successfully.');
            } else {
                $student = Student::create($data);
                session()->flash('message', 'Student added successfully.');
            }

            if ($student->primaryGuardian) {
                $student->primaryGuardian->update([
                    'whatsapp_opt_in' => (bool) $this->guardian_whatsapp_opt_in,
                ]);
            }

            // Determine status for enrollment records
            $defaultStatus = $this->isEditing ? ($student->status ?? 'active') : 'active';

            if ($isRegular) {
                $existingReg = \App\Models\Enrollment::where('student_id', $student->id)
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->where('shift_type', 'regular')
                    ->first();

                \App\Models\Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => 'regular',
                    ],
                    [
                        'class_id' => $this->class_id,
                        'section_id' => $this->section_id ?: null,
                        'program_id' => $this->program_id,
                        'roll_number' => $this->roll_no,
                        'status' => $existingReg ? $existingReg->status : $defaultStatus,
                    ]
                );
            } else {
                if ($this->student_shift_option === 'morning') {
                    // Delete evening enrollment if any exists
                    DB::table('enrollments')
                        ->where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'evening')
                        ->delete();

                    $existingMorn = \App\Models\Enrollment::where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'morning')
                        ->first();

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'morning',
                        ],
                        [
                            'class_id' => $this->singleStudentClasses['morning'],
                            'section_id' => $this->section_id ?: null,
                            'program_id' => $this->program_id,
                            'roll_number' => $this->roll_no,
                            'status' => $existingMorn ? $existingMorn->status : $defaultStatus,
                        ]
                    );
                } elseif ($this->student_shift_option === 'evening') {
                    // Delete morning enrollment if any exists
                    DB::table('enrollments')
                        ->where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'morning')
                        ->delete();

                    $existingEve = \App\Models\Enrollment::where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'evening')
                        ->first();

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'evening',
                        ],
                        [
                            'class_id' => $this->singleStudentClasses['evening'],
                            'section_id' => $this->section_id ?: null,
                            'program_id' => $this->program_id,
                            'roll_number' => $this->roll_no,
                            'status' => $existingEve ? $existingEve->status : $defaultStatus,
                        ]
                    );
                } elseif ($this->student_shift_option === 'both') {
                    $morningClassId = $this->singleStudentClasses['morning'];
                    $eveningClassId = $this->singleStudentClasses['evening'];

                    // Check existing enrollments
                    $oldMorning = Enrollment::where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'morning')
                        ->first();

                    $oldEvening = Enrollment::where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'evening')
                        ->first();

                    $morningRoll = $oldMorning ? $oldMorning->roll_number : $this->roll_no;

                    if ($oldEvening) {
                        $eveningRoll = $oldEvening->roll_number;
                    } else {
                        $maxRollNo = DB::table('enrollments')
                            ->where('class_id', $eveningClassId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'evening')
                            ->max(DB::raw('CAST(roll_number AS INTEGER)'));
                        $eveningRoll = (string)($maxRollNo ? ($maxRollNo + 1) : 1);
                    }

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'morning',
                        ],
                        [
                            'class_id' => $morningClassId,
                            'section_id' => $this->section_id ?: null,
                            'program_id' => $this->program_id,
                            'roll_number' => $morningRoll,
                            'status' => $oldMorning ? $oldMorning->status : $defaultStatus,
                        ]
                    );

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'evening',
                        ],
                        [
                            'class_id' => $eveningClassId,
                            'section_id' => $this->section_id ?: null,
                            'program_id' => $this->program_id,
                            'roll_number' => $eveningRoll,
                            'status' => $oldEvening ? $oldEvening->status : $defaultStatus,
                        ]
                    );
                }
            }
        });

        $this->showModal = false;
        $this->isExistingStudentRecord = false;
        $this->isActiveStudentDuplicate = false;
        $this->activeStudentDetails = [];
        $this->existingStudentMessage = '';
        $this->reset(['name', 'roll_no', 'auto_roll_no', 'admission_no', 'b_form_number', 'father_name', 'father_cnic', 'guardian_cnic', 'guardian_relation', 'is_guardian_primary', 'sibling_concession_status', 'custom_concession_amount', 'detectedSiblings', 'detectedAlumni', 'isReadmission', 'readmitStudentId', 'phone', 'email', 'isEditing', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address', 'program_id', 'gender', 'newSportName', 'newActivityName', 'editingOptionId', 'editingOptionName', 'status', 'singleStudentClasses', 'isExistingStudentRecord', 'isActiveStudentDuplicate', 'activeStudentDetails', 'existingStudentMessage']);
        $this->guardian_whatsapp_opt_in = true;

        if ($this->onlyModal) {
            return redirect(request()->header('Referer'));
        }
    }

    public function addOption($type)
    {
        $name = $type === 'sport' ? $this->newSportName : $this->newActivityName;
        if (trim($name) === '') return;
        
        \App\Models\DefinedOption::firstOrCreate(['type' => $type, 'name' => trim($name)]);
        
        if ($type === 'sport') $this->newSportName = '';
        if ($type === 'activity') $this->newActivityName = '';
    }

    public function startEditOption($id, $name)
    {
        $this->editingOptionId = $id;
        $this->editingOptionName = $name;
    }

    public function renameOption()
    {
        if (!$this->editingOptionId || trim($this->editingOptionName) === '') return;
        
        $option = \App\Models\DefinedOption::find($this->editingOptionId);
        if ($option) {
            $oldName = $option->name;
            $newName = trim($this->editingOptionName);
            $option->update(['name' => $newName]);
            
            // Sync old names in existing students
            $field = $option->type === 'sport' ? 'sports' : 'extra_curriculars';
            $students = Student::where($field, 'LIKE', "%{$oldName}%")->get();
            foreach ($students as $s) {
                // simple replace logic, works best if options are unique
                $updatedStr = implode(',', array_map(function($val) use ($oldName, $newName) {
                    return trim($val) === $oldName ? $newName : trim($val);
                }, explode(',', $s->$field)));
                $s->update([$field => $updatedStr]);
            }

            // Also update current form state if it was checked
            if ($option->type === 'sport') {
                $idx = array_search($oldName, $this->sports);
                if ($idx !== false) {
                    $this->sports[$idx] = $newName;
                }
            } else {
                $idx = array_search($oldName, $this->extra_curriculars);
                if ($idx !== false) {
                    $this->extra_curriculars[$idx] = $newName;
                }
            }
        }
        $this->editingOptionId = null;
        $this->editingOptionName = '';
    }

    public function deleteOption($id)
    {
        \App\Models\DefinedOption::where('id', $id)->delete();
    }

    public function delete($id)
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Student enrollments cannot be deleted or archived from a concluded session.');
            return;
        }

        $student = Student::withTrashed()->findOrFail($id);

        // Check if student has ANY financial, academic, or attendance records
        $hasFinancial = $student->feeRecords()->exists() 
                     || $student->feePayments()->exists()
                     || \Illuminate\Support\Facades\DB::table('fee_invoices')->where('student_id', $id)->exists();

        $hasAcademic = \Illuminate\Support\Facades\DB::table('exam_marks')->where('student_id', $id)->exists()
                    || \Illuminate\Support\Facades\DB::table('attendances')->where('student_id', $id)->exists()
                    || \App\Models\SchoolLeavingCertificate::where('student_id', $id)->exists();

        // Also check if they have historical enrollments in OTHER academic sessions
        $hasHistoricalEnrollments = \App\Models\Enrollment::withTrashed()
            ->where('student_id', $id)
            ->where('academic_session_id', '!=', $this->selectedSessionId)
            ->exists();

        DB::transaction(function() use ($student, $id, $hasFinancial, $hasAcademic, $hasHistoricalEnrollments) {
            if (!$hasFinancial && !$hasAcademic && !$hasHistoricalEnrollments) {
                // FRESH / EMPTY STUDENT: Permanently remove enrollments and the student record completely
                \App\Models\Enrollment::where('student_id', $id)->forceDelete();
                $student->forceDelete();
                session()->flash('message', "Student '{$student->name}' had no academic or financial history and was permanently deleted.");
            } else {
                // HAS HISTORICAL / AUDIT DATA: Archive safely via soft-delete
                \App\Models\Enrollment::where('student_id', $id)
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->delete();

                $hasOtherActive = \App\Models\Enrollment::where('student_id', $id)
                    ->where('status', '!=', 'withdrawn')
                    ->exists();

                if (!$hasOtherActive) {
                    $student->delete();
                }

                session()->flash('message', "Student '{$student->name}' has existing audit records and was safely archived. You can view or restore it anytime from the 'Archived' status filter.");
            }
        });
    }

    public function forceDeleteStudent($id)
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Deleting records from a concluded session is prohibited.');
            return;
        }

        $student = Student::withTrashed()->findOrFail($id);

        $hasFinancial = $student->feeRecords()->exists() 
                     || $student->feePayments()->exists()
                     || \Illuminate\Support\Facades\DB::table('fee_invoices')->where('student_id', $id)->exists();

        $hasAcademic = \Illuminate\Support\Facades\DB::table('exam_marks')->where('student_id', $id)->exists()
                    || \Illuminate\Support\Facades\DB::table('attendances')->where('student_id', $id)->exists()
                    || \App\Models\SchoolLeavingCertificate::where('student_id', $id)->exists();

        if ($hasFinancial || $hasAcademic) {
            session()->flash('error', "Cannot permanently erase '{$student->name}': Institutional integrity violation. Student has financial, exam, or attendance audit history.");
            return;
        }

        DB::transaction(function() use ($student, $id) {
            \App\Models\Enrollment::where('student_id', $id)->forceDelete();
            $student->forceDelete();
        });

        session()->flash('message', "Student '{$student->name}' was permanently deleted from the database.");
    }

    public function restoreStudent($id)
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Student enrollments cannot be restored in a concluded session.');
            return;
        }

        $student = Student::withTrashed()->findOrFail($id);

        DB::transaction(function() use ($student, $id) {
            // 1. Restore the student profile if trashed
            if ($student->trashed()) {
                $student->restore();
            }
            $student->status = 'active';
            $student->save();

            // 2. Restore or recreate session enrollment
            $enrollment = \App\Models\Enrollment::withTrashed()
                ->where('student_id', $id)
                ->where('academic_session_id', $this->selectedSessionId)
                ->first();

            if ($enrollment) {
                if ($enrollment->trashed()) {
                    $enrollment->restore();
                }
                $enrollment->status = 'active';
                $enrollment->save();
            } else {
                // Determine a valid class ID for restoration
                $classId = $this->selectedClassId;
                if (!$classId) {
                    $classId = \App\Models\FeeRecord::where('student_id', $id)->latest()->value('class_id')
                        ?? \App\Models\Classes::withoutGlobalScope('active_session')->where('academic_session_id', $this->selectedSessionId)->value('id');
                }

                if ($classId) {
                    $shiftType = session('selected_shift_type', 'morning');
                    if ($shiftType === 'both') {
                        $shiftType = 'morning';
                    }
                    \App\Models\Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_session_id' => $this->selectedSessionId,
                        'roll_number' => $student->roll_no ?? '1',
                        'shift_type' => $shiftType,
                        'status' => 'active',
                    ]);
                }
            }

            // 3. Log audit trail
            \App\Services\ActivityLogger::log(
                'restored',
                "Restored student {$student->name} (Admn: {$student->admission_no}) to active class status",
                $student,
                null,
                ['status' => 'active'],
                'Students'
            );
        });

        session()->flash('message', "Student {$student->name} (Admn: {$student->admission_no}) has been restored successfully to active class.");
    }

    // ==========================================
    // STUDENT LIFECYCLE: 3-STEP SLC WIZARD
    // ==========================================

    public function startIssueSlc($studentId)
    {
        $this->slcStudentId = $studentId;
        $this->slcStudent = Student::with(['class'])->findOrFail($studentId);
        $this->slcEnrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->where('academic_session_id', $this->selectedSessionId)
            ->with(['currentClass', 'academicSession'])
            ->first();

        if (!$this->slcEnrollment) {
            $this->slcEnrollment = \App\Models\Enrollment::where('student_id', $studentId)
                ->with(['currentClass', 'academicSession'])
                ->latest('academic_session_id')
                ->first();
        }

        $service = app(\App\Services\StudentLifecycleService::class);
        $this->slcClearance = $service->checkFinancialClearance($this->slcStudent, $this->selectedSessionId);
        $this->slcLeavingDate = now()->format('Y-m-d');
        $this->slcReason = 'Parent Relocation';
        $this->slcConduct = 'Good';
        $this->slcRemarks = '';
        $this->issuedSlc = null;
        $this->slcStep = 1;
        $this->showSlcModal = true;
    }

    public function openWaiverModal($feeRecordId)
    {
        $this->waiverFeeRecordId = $feeRecordId;
        $this->waiverFeeRecord = \App\Models\FeeRecord::find($feeRecordId);
        $this->waiverAmount = $this->waiverFeeRecord ? (string)$this->waiverFeeRecord->balance : '';
        $this->waiverReference = 'APP-' . now()->format('Ymd') . '-' . rand(100, 999);
        $this->waiverApprovedBy = 'Principal';
        $this->waiverReason = 'Institutional Departure Clearance / Hardship Waiver';
        $this->waiverConcessionType = 'hardship';
        $this->showWaiverModal = true;
    }

    public function closeWaiverModal()
    {
        $this->showWaiverModal = false;
        $this->waiverFeeRecordId = null;
        $this->waiverFeeRecord = null;
    }

    public function submitFeeWaiver()
    {
        $this->validate([
            'waiverAmount' => 'required|numeric|min:0.01',
            'waiverReference' => 'required|string|max:100',
            'waiverApprovedBy' => 'required|string|max:100',
            'waiverReason' => 'required|string|max:500',
        ]);

        $service = app(\App\Services\StudentLifecycleService::class);
        $service->applyFeeWaiver(
            $this->slcStudent,
            $this->waiverFeeRecordId,
            (float) $this->waiverAmount,
            $this->waiverReference,
            $this->waiverApprovedBy,
            $this->waiverReason,
            null,
            auth()->id() ?? 1,
            $this->waiverConcessionType
        );

        $this->closeWaiverModal();

        // Refresh clearance dynamically
        $this->slcClearance = $service->checkFinancialClearance($this->slcStudent, $this->selectedSessionId);
        session()->flash('slc_message', 'Fee waiver approved and posted to ledger. Clearance status updated.');
    }

    public function advanceSlcStep()
    {
        if ($this->slcStep === 1) {
            if (!$this->slcClearance['cleared']) {
                $this->addError('slc', 'Clearance blocked: outstanding dues must be paid or officially waived.');
                return;
            }
            $this->slcStep = 2;
        } elseif ($this->slcStep === 2) {
            $this->validate([
                'slcLeavingDate' => 'required|date',
                'slcReason' => 'required|string|max:100',
                'slcConduct' => 'required|string|max:50',
                'slcRemarks' => 'nullable|string|max:500',
            ]);
            $this->slcStep = 3;
        }
    }

    public function finalizeSlc()
    {
        if (!$this->slcEnrollment) {
            $this->addError('slc', 'No active enrollment found to issue certificate.');
            return;
        }

        $service = app(\App\Services\StudentLifecycleService::class);
        $this->issuedSlc = $service->issueSchoolLeavingCertificate(
            $this->slcStudent,
            $this->slcEnrollment,
            [
                'leaving_date' => $this->slcLeavingDate,
                'class_at_leaving' => $this->slcEnrollment->currentClass?->name ?? 'N/A',
                'reason_for_leaving' => $this->slcReason,
                'conduct' => $this->slcConduct,
                'remarks' => $this->slcRemarks,
                'concession_reference' => $this->waiverReference ?: null,
            ],
            auth()->id() ?? 1
        );

        session()->flash('message', "Official School Leaving Certificate #{$this->issuedSlc->certificate_number} issued.");
    }

    public function closeSlcModal()
    {
        $this->showSlcModal = false;
        $this->slcStudent = null;
        $this->slcEnrollment = null;
        $this->slcClearance = [];
        $this->issuedSlc = null;
        $this->slcStep = 1;
    }

    // ==========================================
    // STUDENT LIFECYCLE: 360° LIFETIME TIMELINE
    // ==========================================

    public function openLifetimeHistory($studentId)
    {
        $this->historyStudentId = $studentId;
        $student = Student::with(['class'])->findOrFail($studentId);
        $service = app(\App\Services\StudentLifecycleService::class);
        $this->historyPortfolio = $service->getLifetimePortfolio($student);
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyStudentId = null;
        $this->historyPortfolio = null;
    }

    public function resolveProgramForClass($classId)
    {
        if (!$classId) {
            $this->program_id = null;
            return;
        }

        $class = Classes::withoutGlobalScopes()->with('programs')->find($classId);
        if (!$class) {
            $this->program_id = null;
            return;
        }

        $programs = $class->programs;

        if ($programs->count() === 1) {
            $this->program_id = $programs->first()->id;
        } elseif ($programs->isNotEmpty() && (empty($this->program_id) || !$programs->contains('id', $this->program_id))) {
            $defaultProg = $programs->firstWhere('pivot.is_default', true) ?? $programs->first();
            $this->program_id = $defaultProg->id;
        } elseif ($programs->isEmpty()) {
            $midGen = \App\Models\Program::where('code', 'MID-GEN')->first();
            if ($midGen) {
                $this->program_id = $midGen->id;
            }
        }
    }

    public function getSelectedClassProgramsProperty()
    {
        if (!$this->class_id) {
            return collect();
        }

        $class = Classes::withoutGlobalScopes()->with('programs.subjects')->find($this->class_id);
        if (!$class || $class->programs->isEmpty()) {
            return \App\Models\Program::active()->with('subjects')->get();
        }

        return $class->programs;
    }

    public function getCurrentSelectedProgramProperty()
    {
        if (!$this->program_id) {
            return null;
        }

        return \App\Models\Program::with('subjects')->find($this->program_id);
    }

    public function getAvailableProgramsProperty()
    {
        if ($this->selectedClassId) {
            $class = Classes::withoutGlobalScopes()->with('programs')->find($this->selectedClassId);
            if ($class && $class->programs->isNotEmpty()) {
                return $class->programs;
            }
        }

        return \App\Models\Program::active()->get();
    }

    public function assignStudentProgram(int $studentId, ?int $programId)
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Academic programs cannot be modified in a concluded session.');
            return;
        }

        if (!$programId) {
            return;
        }

        DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('academic_session_id', $this->selectedSessionId)
            ->update([
                'program_id' => $programId,
                'updated_at' => now(),
            ]);

        session()->flash('message', 'Academic program updated.');
    }

    public function bulkAssignProgram()
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Academic programs cannot be modified in a concluded session.');
            return;
        }

        $this->validate([
            'bulkProgramId' => 'required|exists:programs,id',
        ]);

        if (empty($this->selectedStudentIds)) {
            return;
        }

        DB::transaction(function() {
            DB::table('enrollments')
                ->whereIn('student_id', $this->selectedStudentIds)
                ->where('academic_session_id', $this->selectedSessionId)
                ->update([
                    'program_id' => $this->bulkProgramId,
                    'updated_at' => now(),
                ]);
        });

        $this->selectedStudentIds = [];
        $this->selectAll = false;
        $this->bulkProgramId = '';
        session()->flash('message', 'Academic program assigned to selected students.');
    }

    public function bulkUpdateStatus()
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Status cannot be modified in a concluded session.');
            return;
        }

        $this->validate([
            'bulkStatus' => 'required|in:active,inactive',
        ]);

        if (empty($this->selectedStudentIds)) {
            return;
        }

        DB::transaction(function() {
            Student::whereIn('id', $this->selectedStudentIds)->update([
                'status' => $this->bulkStatus,
                'updated_at' => now(),
            ]);

            DB::table('enrollments')
                ->whereIn('student_id', $this->selectedStudentIds)
                ->where('academic_session_id', $this->selectedSessionId)
                ->update([
                    'status' => $this->bulkStatus,
                    'updated_at' => now(),
                ]);
        });

        $this->selectedStudentIds = [];
        $this->selectAll = false;
        $this->bulkStatus = '';

        session()->flash('message', 'Selected students status updated successfully.');
    }

    public function bulkUpdateShift()
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Shift assignments cannot be modified in a concluded session.');
            return;
        }

        $this->validate([
            'bulkShift' => 'required|in:morning,evening,regular',
        ]);

        if (empty($this->selectedStudentIds)) {
            return;
        }

        DB::transaction(function() {
            foreach ($this->selectedStudentIds as $studentId) {
                $enrollment = DB::table('enrollments')
                    ->where('student_id', $studentId)
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->first();

                if ($enrollment) {
                    $targetClassId = $enrollment->class_id;

                    $currentClass = Classes::withoutGlobalScope('active_session')->find($enrollment->class_id);
                    if ($currentClass) {
                        $matchingClass = Classes::withoutGlobalScope('active_session')
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('name', $currentClass->name)
                            ->where('shift_type', $this->bulkShift)
                            ->first();

                        if ($matchingClass) {
                            $targetClassId = $matchingClass->id;
                        }
                    }

                    DB::table('enrollments')
                        ->where('id', $enrollment->id)
                        ->update([
                            'shift_type' => $this->bulkShift,
                            'class_id' => $targetClassId,
                            'updated_at' => now(),
                        ]);
                }
            }
        });

        $this->selectedStudentIds = [];
        $this->selectAll = false;
        $this->bulkShift = '';

        session()->flash('message', 'Selected students shift updated successfully.');
    }

    public function getIsHistoricalSessionProperty(): bool
    {
        return \App\Models\AcademicSession::isSessionLocked($this->selectedSessionId);
    }

    public function getMorningClassesProperty()
    {
        return Classes::forSession($this->selectedSessionId, 'morning')
            ->orderBy('numeric_value')
            ->get();
    }

    public function getEveningClassesProperty()
    {
        return Classes::forSession($this->selectedSessionId, 'evening')
            ->orderBy('numeric_value')
            ->get();
    }

    public function openBulkEditModal()
    {
        $this->bulkActionType = 'status';
        $this->bulkStatus = '';
        $this->bulkShiftOption = 'morning';
        $this->bulkStudentClasses = [];
        $this->bulkClassMorning = '';
        $this->bulkClassEvening = '';

        $this->resetErrorBag();
        $this->showBulkEditModal = true;
    }

    public function saveBulkEdit()
    {
        if ($this->bulkActionType === 'status') {
            $this->validate([
                'bulkStatus' => 'required|in:active,inactive',
            ]);
        } else {
            $rules = [
                'bulkShiftOption' => 'required|in:morning,evening,both',
            ];

            if ($this->bulkShiftOption === 'morning' || $this->bulkShiftOption === 'both') {
                $rules['bulkClassMorning'] = 'required|exists:classes,id';
            }
            if ($this->bulkShiftOption === 'evening' || $this->bulkShiftOption === 'both') {
                $rules['bulkClassEvening'] = 'required|exists:classes,id';
            }

            $this->validate($rules);
        }

        DB::transaction(function() {
            foreach ($this->selectedStudentIds as $studentId) {
                $student = Student::findOrFail($studentId);

                if ($this->bulkActionType === 'status') {
                    if (!in_array($student->status, ['withdrawn', 'slc_issued', 'graduated'])) {
                        $student->update(['status' => $this->bulkStatus]);
                        DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->whereNotIn('status', ['slc_issued', 'completed'])
                            ->update(['status' => $this->bulkStatus, 'updated_at' => now()]);
                    }
                } else {
                    if ($this->bulkShiftOption === 'morning') {
                        // Delete evening enrollment if any exists
                        DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'evening')
                            ->delete();

                        $existing = DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'morning')
                            ->first();

                        if ($existing) {
                            DB::table('enrollments')
                                ->where('id', $existing->id)
                                ->update([
                                    'class_id' => $this->bulkClassMorning,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $maxRollNo = DB::table('enrollments')
                                ->where('class_id', $this->bulkClassMorning)
                                ->where('academic_session_id', $this->selectedSessionId)
                                ->where('shift_type', 'morning')
                                ->max(DB::raw('CAST(roll_number AS INTEGER)'));
                            $rollNo = (string)($maxRollNo ? ($maxRollNo + 1) : 1);

                            DB::table('enrollments')->insert([
                                'student_id' => $studentId,
                                'class_id' => $this->bulkClassMorning,
                                'academic_session_id' => $this->selectedSessionId,
                                'shift_type' => 'morning',
                                'roll_number' => $rollNo,
                                'status' => $student->status ?: 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } elseif ($this->bulkShiftOption === 'evening') {
                        // Delete morning enrollment if any exists
                        DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'morning')
                            ->delete();

                        $existing = DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'evening')
                            ->first();

                        if ($existing) {
                            DB::table('enrollments')
                                ->where('id', $existing->id)
                                ->update([
                                    'class_id' => $this->bulkClassEvening,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $maxRollNo = DB::table('enrollments')
                                ->where('class_id', $this->bulkClassEvening)
                                ->where('academic_session_id', $this->selectedSessionId)
                                ->where('shift_type', 'evening')
                                ->max(DB::raw('CAST(roll_number AS INTEGER)'));
                            $rollNo = (string)($maxRollNo ? ($maxRollNo + 1) : 1);

                            DB::table('enrollments')->insert([
                                'student_id' => $studentId,
                                'class_id' => $this->bulkClassEvening,
                                'academic_session_id' => $this->selectedSessionId,
                                'shift_type' => 'evening',
                                'roll_number' => $rollNo,
                                'status' => $student->status ?: 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } elseif ($this->bulkShiftOption === 'both') {
                        // For morning
                        $existingMorning = DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'morning')
                            ->first();

                        if ($existingMorning) {
                            DB::table('enrollments')
                                ->where('id', $existingMorning->id)
                                ->update([
                                    'class_id' => $this->bulkClassMorning,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $maxRollNo = DB::table('enrollments')
                                ->where('class_id', $this->bulkClassMorning)
                                ->where('academic_session_id', $this->selectedSessionId)
                                ->where('shift_type', 'morning')
                                ->max(DB::raw('CAST(roll_number AS INTEGER)'));
                            $rollNo = (string)($maxRollNo ? ($maxRollNo + 1) : 1);

                            DB::table('enrollments')->insert([
                                'student_id' => $studentId,
                                'class_id' => $this->bulkClassMorning,
                                'academic_session_id' => $this->selectedSessionId,
                                'shift_type' => 'morning',
                                'roll_number' => $rollNo,
                                'status' => $student->status ?: 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // For evening
                        $existingEvening = DB::table('enrollments')
                            ->where('student_id', $studentId)
                            ->where('academic_session_id', $this->selectedSessionId)
                            ->where('shift_type', 'evening')
                            ->first();

                        if ($existingEvening) {
                            DB::table('enrollments')
                                ->where('id', $existingEvening->id)
                                ->update([
                                    'class_id' => $this->bulkClassEvening,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $maxRollNo = DB::table('enrollments')
                                ->where('class_id', $this->bulkClassEvening)
                                ->where('academic_session_id', $this->selectedSessionId)
                                ->where('shift_type', 'evening')
                                ->max(DB::raw('CAST(roll_number AS INTEGER)'));
                            $rollNo = (string)($maxRollNo ? ($maxRollNo + 1) : 1);

                            DB::table('enrollments')->insert([
                                'student_id' => $studentId,
                                'class_id' => $this->bulkClassEvening,
                                'academic_session_id' => $this->selectedSessionId,
                                'shift_type' => 'evening',
                                'roll_number' => $rollNo,
                                'status' => $student->status ?: 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });

        $this->showBulkEditModal = false;
        $this->selectedStudentIds = [];
        $this->selectAll = false;
        session()->flash('message', 'Bulk updates completed successfully.');
    }

    public function render()
    {
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $this->currentSessionIsRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

        $viewClasses = $this->classes;

        if ($this->onlyModal) {
            $sportsOptions = \App\Models\DefinedOption::sports()->get();
            $activityOptions = \App\Models\DefinedOption::activities()->get();

            return view('livewire.admin.student-manager', [
                'students' => collect(),
                'classes' => $viewClasses,
                'sportsOptions' => $sportsOptions,
                'activityOptions' => $activityOptions
            ]);
        }

        $allowedClassIds = [];
        $isTeacher = auth()->user()->hasRole('Teacher');

        if ($isTeacher) {
            $allowedClassIds = DB::table('user_class_access')
                ->where('user_id', auth()->id())
                ->pluck('class_id')
                ->toArray();
            
            $viewClasses = $viewClasses->whereIn('id', $allowedClassIds);
        }

        // Security: Restrict "All Classes" view logic
        if (empty($this->selectedClassId) && $isTeacher && !auth()->user()->can('students.view-all-classes')) {
             $first = $viewClasses->first();
             $this->selectedClassId = $first ? $first->id : null;
        }

        // Security: Ensure selected class is actually allowed
        if ($this->selectedClassId && $isTeacher && !in_array($this->selectedClassId, $allowedClassIds)) {
             $this->selectedClassId = !empty($allowedClassIds) ? $allowedClassIds[0] : null;
        }

        $studentsQuery = $this->buildStudentsQuery($allowedClassIds, $isTeacher);
        $students = $studentsQuery->paginate(10);

        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        $sportsOptions = \App\Models\DefinedOption::sports()->get();
        $activityOptions = \App\Models\DefinedOption::activities()->get();

        return view('livewire.admin.student-manager', [
            'students' => $students,
            'classes' => $viewClasses,
            'sportsOptions' => $sportsOptions,
            'activityOptions' => $activityOptions,
            'availablePrograms' => $this->availablePrograms,
        ])->layout($layout, ['title' => 'Student Management']);
    }

    public function buildStudentsQuery(array $allowedClassIds = [], bool $isTeacher = false)
    {
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        if ($this->filterStatus === 'archived') {
            $studentsQuery = Student::onlyTrashed()
                ->leftJoin('enrollments', function($join) {
                    $join->on('students.id', '=', 'enrollments.student_id')
                         ->where('enrollments.academic_session_id', '=', $this->selectedSessionId);
                })
                ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
                ->select(
                    'students.*',
                    DB::raw('COALESCE(classes.name, (SELECT c.name FROM fee_records fr JOIN classes c ON fr.class_id = c.id WHERE fr.student_id = students.id ORDER BY fr.id DESC LIMIT 1), "Archived Profile") as class_name'),
                    DB::raw('COALESCE(enrollments.roll_number, "—") as roll_no'),
                    DB::raw('COALESCE(enrollments.shift_type, "morning") as shift_type')
                )
                ->when($this->search, function ($q) {
                    $searchHash = \App\Models\Student::hashBlindIndex($this->search);
                    $q->where(function ($subQuery) use ($searchHash) {
                        $subQuery->where('students.name', 'like', '%' . $this->search . '%')
                          ->orWhere('students.admission_no', 'like', '%' . $this->search . '%');
                        if ($searchHash) {
                            $subQuery->orWhere('students.father_cnic_hash', $searchHash)
                              ->orWhere('students.guardian_cnic_hash', $searchHash)
                              ->orWhere('students.b_form_hash', $searchHash)
                              ->orWhere('students.phone_hash', $searchHash);
                        }
                    });
                })
                ->when($this->selectedClassId, function($q) {
                    $q->where('enrollments.class_id', $this->selectedClassId);
                });

            if ($isTeacher) {
                $studentsQuery->whereIn('enrollments.class_id', $allowedClassIds);
            }

            if ($this->sortBy === 'name') {
                $studentsQuery->orderBy('students.name', $this->sortDir === 'desc' ? 'desc' : 'asc');
            } elseif ($this->sortBy === 'admission_no') {
                $studentsQuery->orderBy('students.admission_no', $this->sortDir === 'desc' ? 'desc' : 'asc');
            } else {
                $studentsQuery->orderBy('students.id', $this->sortDir === 'desc' ? 'desc' : 'asc');
            }

            return $studentsQuery;
        }

        $studentsQuery = Student::query()
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('programs', 'enrollments.program_id', '=', 'programs.id')
            ->select(
                'students.*',
                'classes.name as class_name',
                'enrollments.roll_number as roll_no',
                'enrollments.shift_type',
                'enrollments.program_id',
                'programs.name as program_name',
                'programs.code as program_code'
            )
            ->where('enrollments.academic_session_id', $this->selectedSessionId)
            ->when($shiftType !== 'both', function($q) use ($shiftType) {
                $q->where('enrollments.shift_type', $shiftType);
            })
            ->when($this->selectedClassId, function($q) {
                $q->where('enrollments.class_id', $this->selectedClassId);
            })
            ->when($this->search, function ($q) {
                $searchHash = \App\Models\Student::hashBlindIndex($this->search);
                $q->where(function ($subQuery) use ($searchHash) {
                    $subQuery->where('students.name', 'like', '%' . $this->search . '%')
                      ->orWhere('enrollments.roll_number', 'like', '%' . $this->search . '%')
                      ->orWhere('students.admission_no', 'like', '%' . $this->search . '%');
                    if ($searchHash) {
                        $subQuery->orWhere('students.father_cnic_hash', $searchHash)
                          ->orWhere('students.guardian_cnic_hash', $searchHash)
                          ->orWhere('students.b_form_hash', $searchHash)
                          ->orWhere('students.phone_hash', $searchHash);
                    }
                });
            })
            ->when($this->filterSport, function($q) {
                $q->where('students.sports', 'like', '%' . $this->filterSport . '%');
            })
            ->when($this->filterActivity, function($q) {
                $q->where('students.extra_curriculars', 'like', '%' . $this->filterActivity . '%');
            })
            ->when($this->filterTransport, function($q) {
                $q->where('students.transport_mode', $this->filterTransport);
            })
            ->when($this->filterTransport === 'school_bus' && $this->filterBus, function($q) {
                $q->where('students.vehicle_number', $this->filterBus);
            })
            ->when($this->filterStatus, function($q) {
                if ($this->filterStatus === 'withdrawn') {
                    $q->whereIn('enrollments.status', ['withdrawn', 'slc_issued']);
                } else {
                    $q->where('enrollments.status', $this->filterStatus);
                }
            });

        // 1. Mandatory Teacher Restriction (Always apply)
        if ($isTeacher) {
            $studentsQuery->whereIn('enrollments.class_id', $allowedClassIds);
        }

        // 2. Selection Filter
        if ($this->selectedClassId) {
            $studentsQuery->where('enrollments.class_id', $this->selectedClassId);
        }

        // Always sort class-wise first to avoid mixing students from different classes
        $studentsQuery->orderBy('classes.name', 'asc');

        if ($this->sortBy === 'roll_no') {
            $studentsQuery->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ' . ($this->sortDir === 'desc' ? 'DESC' : 'ASC'));
        } elseif ($this->sortBy === 'name') {
            $studentsQuery->orderBy('students.name', $this->sortDir === 'desc' ? 'desc' : 'asc');
        } elseif ($this->sortBy === 'admission_no') {
            $studentsQuery->orderBy('students.admission_no', $this->sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $studentsQuery->orderBy('students.id', $this->sortDir === 'desc' ? 'desc' : 'asc');
        }

        return $studentsQuery;
    }

    public function exportCsv()
    {
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $sessionName = $sessionObj ? str_replace([' ', '/', '\\'], '_', $sessionObj->name) : 'session';
        $filename = 'students_export_' . $sessionName . '_' . now()->format('Y-m-d_His') . '.csv';

        $isTeacher = auth()->check() && auth()->user()->hasRole('Teacher');
        $allowedClassIds = [];
        if ($isTeacher) {
            $allowedClassIds = DB::table('user_class_access')
                ->where('user_id', auth()->id())
                ->pluck('class_id')
                ->toArray();
        }

        $students = $this->buildStudentsQuery($allowedClassIds, $isTeacher)->get();

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Admission No',
                'Roll No',
                'Student Name',
                'Father Name',
                'Class',
                'Shift',
                'Status',
                'Gender',
                'B-Form / CNIC',
                'Father CNIC',
                'Phone',
                'DOB',
                'Admission Date',
                'Address',
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->admission_no,
                    $student->roll_no ?? '—',
                    $student->name,
                    $student->father_name ?? '—',
                    $student->class_name ?? '—',
                    ucfirst($student->shift_type ?? 'regular'),
                    ucfirst($student->status ?? 'active'),
                    ucfirst($student->gender ?? '—'),
                    $student->b_form_number ?? '—',
                    $student->father_cnic ?? '—',
                    $student->phone ?? '—',
                    $student->dob ?? '—',
                    $student->admission_date ?? '—',
                    $student->address ?? '—',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
