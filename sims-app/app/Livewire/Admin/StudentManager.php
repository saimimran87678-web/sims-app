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

    // Bulk Actions & Electives
    public $selectedStudentIds = [];
    public $bulkSubjectId = '';
    public $bulkStatus = '';
    public $bulkShift = '';
    public $studentSubjects = [];
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
                $query->where(function($q) {
                    $q->where('students.name', 'like', '%' . $this->search . '%')
                      ->orWhere('enrollments.roll_number', 'like', '%' . $this->search . '%')
                      ->orWhere('students.admission_no', 'like', '%' . $this->search . '%');
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
    public $father_name = '';
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
    public $photo;
    public $student_shift = 'morning';
    public $currentSessionIsRegular = false;

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
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'admission_no' => [
                'required', 
                'string', 
                Rule::unique('students', 'admission_no')->ignore($this->editingStudentId)
            ],
            'gender' => 'required|in:Male,Female,Other',
            'sports' => 'array',
            'extra_curriculars' => 'array',
            'transport_mode' => 'required|string',
            'vehicle_number' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'admission_date' => 'nullable|date',
            'photo' => 'nullable|image|max:1024',
            'address' => 'nullable|string|max:1000',
            'status' => 'required|string|in:active,inactive',
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

            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('shift_type', $shiftType);
                })
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
        $this->reset(['name', 'roll_no', 'auto_roll_no', 'admission_no', 'father_name', 'phone', 'email', 'isEditing', 'editingStudentId', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address', 'studentSubjects', 'gender', 'newSportName', 'newActivityName', 'editingOptionId', 'editingOptionName', 'status']);
        
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
        } else {
            $this->roll_no = '';
        }
        $this->showModal = true;
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        
        $this->editingStudentId = $id;
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
        $this->father_name = $student->father_name;
        $this->phone = $student->phone;
        $this->email = $student->email;
        $this->section_id = $student->section_id;
        $this->gender = $student->gender ? ucfirst($student->gender) : 'Male';
        
        $this->sports = $student->sports ? explode(',', $student->sports) : [];
        $this->extra_curriculars = $student->extra_curriculars ? explode(',', $student->extra_curriculars) : [];
        $this->transport_mode = $student->transport_mode ?? 'none';
        $this->vehicle_number = $student->vehicle_number ?? '';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->admission_date = $student->admission_date ? $student->admission_date->format('Y-m-d') : '';
        $this->address = $student->address;

        // Load assigned subjects
        $this->studentSubjects = $student->subjects()->pluck('subjects.id')->toArray();

        $this->auto_roll_no = false;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function view($id)
    {
        $this->viewingStudent = Student::with('class')->findOrFail($id);
        $this->showViewModal = true;
    }

    public function save()
    {
        $this->roll_no = trim($this->roll_no);
        $this->admission_no = trim($this->admission_no);
        $validated = $this->validate();

        // Serialize arrays
        $data = [
            'name' => $this->name,
            'admission_no' => $this->admission_no,
            'father_name' => $this->father_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'section_id' => $this->section_id ?: null,
            'gender' => strtolower($this->gender),
            'sports' => !empty($this->sports) ? implode(',', $this->sports) : null,
            'extra_curriculars' => !empty($this->extra_curriculars) ? implode(',', $this->extra_curriculars) : null,
            'transport_mode' => $this->transport_mode,
            'vehicle_number' => $this->vehicle_number,
            'dob' => $this->dob ?: null,
            'admission_date' => $this->admission_date ?: null,
            'address' => $this->address,
            'status' => $this->status,
        ];

        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $data['profile_photo_path'] = $path;
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

            if ($isRegular) {
                \App\Models\Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => 'regular',
                    ],
                    [
                        'class_id' => $this->class_id,
                        'roll_number' => $this->roll_no,
                        'status' => $this->status ?: 'active',
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

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'morning',
                        ],
                        [
                            'class_id' => $this->singleStudentClasses['morning'],
                            'roll_number' => $this->roll_no,
                            'status' => $this->status ?: 'active',
                        ]
                    );
                } elseif ($this->student_shift_option === 'evening') {
                    // Delete morning enrollment if any exists
                    DB::table('enrollments')
                        ->where('student_id', $student->id)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->where('shift_type', 'morning')
                        ->delete();

                    \App\Models\Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $this->selectedSessionId,
                            'shift_type' => 'evening',
                        ],
                        [
                            'class_id' => $this->singleStudentClasses['evening'],
                            'roll_number' => $this->roll_no,
                            'status' => $this->status ?: 'active',
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
                            'roll_number' => $morningRoll,
                            'status' => $this->status ?: 'active',
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
                            'roll_number' => $eveningRoll,
                            'status' => $this->status ?: 'active',
                        ]
                    );
                }
            }

            $student->subjects()->sync($this->studentSubjects);
        });

        $this->showModal = false;
        $this->reset(['name', 'roll_no', 'auto_roll_no', 'admission_no', 'father_name', 'phone', 'email', 'isEditing', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address', 'studentSubjects', 'gender', 'newSportName', 'newActivityName', 'editingOptionId', 'editingOptionName', 'status', 'singleStudentClasses']);

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
        DB::transaction(function() use ($id) {
            // 1. Delete all enrollments of this student in the selected session
            DB::table('enrollments')
                ->where('student_id', $id)
                ->where('academic_session_id', $this->selectedSessionId)
                ->delete();

            // 2. Check if the student has any enrollments left in any session
            $hasOtherEnrollments = DB::table('enrollments')
                ->where('student_id', $id)
                ->exists();

            if (!$hasOtherEnrollments) {
                // If no enrollments left in any session, we can delete the student completely.
                // This will trigger cascade deletes on all other tables.
                Student::where('id', $id)->delete();
            } else {
                // If there are other enrollments, keep the student but delete session-specific data:
                
                // Delete attendances for this session
                DB::table('attendances')
                    ->where('student_id', $id)
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->delete();

                // Delete exam marks for this session
                $examIds = DB::table('exams')
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->pluck('id');
                if ($examIds->isNotEmpty()) {
                    DB::table('exam_marks')
                        ->where('student_id', $id)
                        ->whereIn('exam_id', $examIds)
                        ->delete();
                }

                // Delete fee records for this session
                DB::table('fee_records')
                    ->where('student_id', $id)
                    ->where('academic_session_id', $this->selectedSessionId)
                    ->delete();
            }
        });

        session()->flash('message', 'Student deleted successfully.');
    }

    public function getBulkSubjectsProperty()
    {
        if (!$this->selectedClassId) {
            return collect();
        }

        $dividedSubjectIds = DB::table('timetables')
            ->where('class_id', $this->selectedClassId)
            ->where('is_divided', true)
            ->pluck('subject_id')
            ->unique()
            ->toArray();

        return \App\Models\Subject::whereIn('id', $dividedSubjectIds)->get();
    }

    public function bulkAssignSubject()
    {
        $this->validate([
            'bulkSubjectId' => 'required|exists:subjects,id',
        ]);

        if (empty($this->selectedStudentIds)) {
            return;
        }

        DB::transaction(function() {
            foreach ($this->selectedStudentIds as $studentId) {
                DB::table('student_subject')->updateOrInsert([
                    'student_id' => $studentId,
                    'subject_id' => $this->bulkSubjectId,
                ]);
            }
        });

        $this->selectedStudentIds = [];
        $this->bulkSubjectId = '';
        session()->flash('message', 'Elective subject assigned successfully.');
    }

    public function bulkUnassignSubject()
    {
        $this->validate([
            'bulkSubjectId' => 'required|exists:subjects,id',
        ]);

        if (empty($this->selectedStudentIds)) {
            return;
        }

        DB::table('student_subject')
            ->whereIn('student_id', $this->selectedStudentIds)
            ->where('subject_id', $this->bulkSubjectId)
            ->delete();

        $this->selectedStudentIds = [];
        $this->bulkSubjectId = '';
        session()->flash('message', 'Elective subject unassigned successfully.');
    }

    public function bulkUpdateStatus()
    {
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

    public function getMorningClassesProperty()
    {
        return Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', 'morning')
            ->orderBy('numeric_value')
            ->get();
    }

    public function getEveningClassesProperty()
    {
        return Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', 'evening')
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
                    $student->update(['status' => $this->bulkStatus]);
                    DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('academic_session_id', $this->selectedSessionId)
                        ->update(['status' => $this->bulkStatus, 'updated_at' => now()]);
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

        $shiftType = $this->currentSessionIsRegular ? 'regular' : session('selected_shift_type', 'morning');

        $studentsQuery = Student::query()
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.name as class_name', 'enrollments.roll_number as roll_no', 'enrollments.shift_type')
            ->where('enrollments.academic_session_id', $this->selectedSessionId)
            ->when($shiftType !== 'both', function($q) use ($shiftType) {
                $q->where('enrollments.shift_type', $shiftType);
            })
            ->when($this->selectedClassId, function($q) {
                $q->where('enrollments.class_id', $this->selectedClassId);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('students.name', 'like', '%' . $this->search . '%')
                      ->orWhere('enrollments.roll_number', 'like', '%' . $this->search . '%')
                      ->orWhere('students.admission_no', 'like', '%' . $this->search . '%');
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
                $q->where('enrollments.status', $this->filterStatus);
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
            'activityOptions' => $activityOptions
        ])->layout($layout, ['title' => 'Student Management']);
    }
}
