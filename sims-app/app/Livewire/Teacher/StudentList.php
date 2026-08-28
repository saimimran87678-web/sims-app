<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentList extends Component
{
    use \Livewire\WithFileUploads;

    public $classId;
    public $className = '';
    public $search = '';
    
    // Filters
    public $filterSport = '';
    public $filterActivity = '';
    public $filterTransport = '';
    public $filterBus = '';
    public $filterStatus = 'active'; // 'active' | 'inactive' | '' (all)
    public $viewMode = 'grid'; // 'grid' or 'list'
    
    // Modal State
    public $isModalOpen = false;
    public $isEditMode = false;
    public $editStudentId = null;
    public $auto_roll_no = true;
    
    // View Modal State
    public $viewingStudent = null;
    public $showViewModal = false;

    // Form Fields
    public $name = '';
    public $roll_no = '';
    public $admission_no = '';
    public $father_name = '';
    public $phone = '';
    public $email = '';
    public $gender = 'Male';
    public $dob = '';
    public $admission_date = '';
    public $sports = [];
    public $extra_curriculars = [];
    public $transport_mode = 'none';
    public $vehicle_number = '';
    public $address = '';
    public $photo;

    // Option Editing State
    public $newSportName = '';
    public $newActivityName = '';
    public $editingOptionId = null;
    public $editingOptionName = '';

    protected $rules = [
        'name' => 'required|min:2',
        'roll_no' => 'required',
        'admission_no' => 'required',
        'father_name' => 'nullable',
        'phone' => 'nullable',
        'email' => 'nullable|email|max:255',
        'gender' => 'required|in:Male,Female,Other',
        'dob' => 'nullable|date',
        'admission_date' => 'nullable|date',
        'sports' => 'nullable|array',
        'extra_curriculars' => 'nullable|array',
        'transport_mode' => 'nullable|string',
        'vehicle_number' => 'nullable|string',
        'address' => 'nullable|string',
        'photo' => 'nullable|image|max:1024',
    ];

    public $sortOrder = 'asc'; // 'asc' or 'desc'

    public function mount()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $classId = Auth::user()->getSessionClassId($activeSessionId);
        
        $shiftType = $this->getShiftType();
        if ($classId) {
            $class = \App\Models\Classes::withoutGlobalScope('active_session')->find($classId);
            if ($class && ($shiftType === 'both' || $class->shift_type === $shiftType)) {
                $this->classId = $classId;
                $this->className = $class->name;
            } else {
                $this->classId = null;
                $this->className = 'No Class Assigned';
            }
        } else {
            $this->classId = null;
            $this->className = 'No Class Assigned';
        }
    }

    protected function getShiftType()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }
        return $shiftType;
    }

    public function updatedFilterBus()
    {
        // For Livewire reactivity
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->auto_roll_no = true;
        $this->autoAssignRollNo($this->classId);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = $this->getShiftType();

        $student = \App\Models\Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
            $q->where('class_id', $this->classId)
              ->where('academic_session_id', $activeSessionId)
              ->where('shift_type', $shiftType);
        })->find($id);
        
        if (!$student) {
            session()->flash('error', 'Student not found.');
            return;
        }

        $enrollment = $student->enrollments()
            ->where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->first();
        
        $this->editStudentId = $student->id;
        $this->name = $student->name;
        $this->roll_no = $enrollment->roll_number;
        $this->admission_no = $student->admission_no;
        $this->father_name = $student->father_name;
        $this->phone = $student->phone;
        $this->email = $student->email;
        $this->gender = $student->gender ?? 'Male';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->admission_date = $student->admission_date ? $student->admission_date->format('Y-m-d') : '';
        $this->address = $student->address;
        $this->transport_mode = $student->transport_mode ?? 'none';
        $this->vehicle_number = $student->vehicle_number;
        
        // Handle array fields
        $this->sports = $student->sports ? explode(',', $student->sports) : [];
        $this->extra_curriculars = $student->extra_curriculars ? explode(',', $student->extra_curriculars) : [];
        
        $this->isEditMode = true;
        $this->auto_roll_no = false;
        $this->isModalOpen = true;
    }

    public function view($id)
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = $this->getShiftType();

        $this->viewingStudent = \App\Models\Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
            $q->where('class_id', $this->classId)
              ->where('academic_session_id', $activeSessionId)
              ->where('shift_type', $shiftType);
        })->find($id);

        if ($this->viewingStudent) {
            $enrollment = $this->viewingStudent->enrollments()
                ->where('academic_session_id', $activeSessionId)
                ->where('shift_type', $shiftType)
                ->first();
            $this->viewingStudent->class_name = $this->className;
            $this->viewingStudent->roll_no = $enrollment->roll_number;
            $this->showViewModal = true;
        }
    }

    public function store()
    {
        $this->roll_no = trim($this->roll_no);
        $this->admission_no = trim($this->admission_no);

        // Adjust validation for edit mode
        $rules = $this->rules;
        if ($this->isEditMode) {
            $rules['admission_no'] = 'required|unique:students,admission_no,' . $this->editStudentId;
        } else {
             $rules['admission_no'] = 'required|unique:students,admission_no';
        }
        
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'roll_no' => $this->roll_no,
            'admission_no' => $this->admission_no,
            'father_name' => $this->father_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => strtolower($this->gender),
            'dob' => $this->dob ?: null,
            'admission_date' => $this->admission_date ?: null,
            'address' => $this->address,
            'class_id' => $this->classId,
            'transport_mode' => $this->transport_mode,
            'vehicle_number' => $this->vehicle_number,
            'sports' => !empty($this->sports) ? implode(',', $this->sports) : null,
            'extra_curriculars' => !empty($this->extra_curriculars) ? implode(',', $this->extra_curriculars) : null,
        ];

        if ($this->photo) {
             $path = $this->photo->store('profile-photos', 'public');
             $data['profile_photo_path'] = $path;
        }

        DB::transaction(function() use ($data) {
            $this->adjustRollNumbers($this->classId, $this->roll_no);

            if ($this->isEditMode) {
                $student = \App\Models\Student::find($this->editStudentId);
                $student->update($data);
                session()->flash('message', 'Student updated successfully.');
            } else {
                $student = \App\Models\Student::create($data);
                session()->flash('message', 'Student added successfully.');
            }

            \App\Models\Enrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_session_id' => \App\Models\AcademicSession::getActiveSessionId(),
                    'shift_type' => $this->getShiftType(),
                ],
                [
                    'class_id' => $this->classId,
                    'roll_number' => $this->roll_no,
                    'status' => 'active',
                ]
            );
        });

        $this->closeModal();
    }

    public function delete($id)
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = $this->getShiftType();

        $student = \App\Models\Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
            $q->where('class_id', $this->classId)
              ->where('academic_session_id', $activeSessionId)
              ->where('shift_type', $shiftType);
        })->find($id);
        
        if (!$student) {
            session()->flash('error', 'Cannot delete this student.');
            return;
        }
        
        $student->delete();
        session()->flash('message', 'Student deleted successfully.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
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
            $students = \App\Models\Student::where($field, 'LIKE', "%{$oldName}%")->get();
            foreach ($students as $s) {
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

    private function resetForm()
    {
        $this->reset([
            'name', 'roll_no', 'admission_no', 'father_name', 'phone', 'email', 'gender', 'dob', 'admission_date',
            'editStudentId', 'photo', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'address', 'newSportName', 'newActivityName', 'editingOptionId', 'editingOptionName'
        ]);
        $this->sports = [];
        $this->extra_curriculars = [];
        $this->transport_mode = 'none';
        $this->gender = 'Male';
    }

    public function render()
    {
        $sportsOptions = \App\Models\DefinedOption::sports()->get();
        $activityOptions = \App\Models\DefinedOption::activities()->get();

        if (!$this->classId) {
            return view('livewire.teacher.student-list', [
                'students' => collect([]),
                'className' => 'No Class Assigned',
                'sportsOptions' => $sportsOptions,
                'activityOptions' => $activityOptions
            ])->layout('components.layouts.teacher', ['title' => 'My Students']);
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = $this->getShiftType();

        $query = \App\Models\Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
            $q->where('class_id', $this->classId)
              ->where('academic_session_id', $activeSessionId)
              ->where('shift_type', $shiftType);
        });

        if ($this->filterStatus) {
            $query->whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
                $q->where('academic_session_id', $activeSessionId)
                  ->where('shift_type', $shiftType)
                  ->where('status', $this->filterStatus);
            });
        }

        $students = $query->get()
            ->map(function($student) use ($activeSessionId, $shiftType) {
                $enrollment = $student->enrollments()
                    ->where('academic_session_id', $activeSessionId)
                    ->where('shift_type', $shiftType)
                    ->first();
                $student->roll_no = $enrollment ? $enrollment->roll_number : '';
                $student->status = $enrollment ? $enrollment->status : 'active';
                return $student;
            });

        if ($this->search) {
            $searchTerm = strtolower($this->search);
            $students = $students->filter(function($student) use ($searchTerm) {
                return str_contains(strtolower($student->name), $searchTerm)
                    || str_contains(strtolower($student->roll_no), $searchTerm)
                    || str_contains(strtolower($student->admission_no), $searchTerm);
            });
        }
        
        if ($this->filterSport) {
            $students = $students->filter(fn($student) => str_contains($student->sports, $this->filterSport));
        }
        
        if ($this->filterActivity) {
            $students = $students->filter(fn($student) => str_contains($student->extra_curriculars, $this->filterActivity));
        }
        
        if ($this->filterTransport) {
            $students = $students->filter(fn($student) => $student->transport_mode === $this->filterTransport);
            if ($this->filterTransport === 'school_bus' && $this->filterBus) {
                $students = $students->filter(fn($student) => $student->vehicle_number === $this->filterBus);
            }
        }

        $sortOrder = $this->sortOrder;
        $students = $students->sortBy(fn($student) => (int)$student->roll_no, SORT_REGULAR, $sortOrder === 'desc')->values();

        return view('livewire.teacher.student-list', [
            'students' => $students,
            'className' => $this->className,
            'sportsOptions' => $sportsOptions,
            'activityOptions' => $activityOptions
        ])->layout('components.layouts.teacher', ['title' => 'My Students']);
    }

    public function updatedAutoRollNo($value)
    {
        if ($value) {
            $this->autoAssignRollNo($this->classId);
        }
    }

    protected function autoAssignRollNo($classId)
    {
        if ($classId) {
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $shiftType = $this->getShiftType();

            $maxRollNo = \App\Models\Enrollment::where('class_id', $classId)
                ->where('academic_session_id', $activeSessionId)
                ->where('shift_type', $shiftType)
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

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = $this->getShiftType();

        $enrollments = \App\Models\Enrollment::where('class_id', $classId)
            ->where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->when($this->editStudentId, function ($query) {
                $query->where('student_id', '!=', $this->editStudentId);
            })
            ->get();

        $enrollmentsToShift = $enrollments->filter(function ($e) use ($target) {
            return (int)$e->roll_number >= $target;
        })->sortBy(function ($e) {
            return (int)$e->roll_number;
        });

        $nextRollNo = $target + 1;
        foreach ($enrollmentsToShift as $enrollment) {
            $enrollment->update(['roll_number' => (string)$nextRollNo]);
            $nextRollNo++;
        }
    }
}
