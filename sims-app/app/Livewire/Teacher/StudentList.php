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
        $this->classId = Auth::user()->getSessionClassId($activeSessionId);
        
        if ($this->classId) {
            $this->className = DB::table('classes')
                ->where('id', $this->classId)
                ->value('name') ?? 'Unknown Class';
        }
    }

    public function updatedFilterBus()
    {
        // For Livewire reactivity
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $student = \App\Models\Student::find($id);
        
        if (!$student || $student->class_id != $this->classId) {
            session()->flash('error', 'Student not found.');
            return;
        }
        
        $this->editStudentId = $student->id;
        $this->name = $student->name;
        $this->roll_no = $student->roll_no;
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
        $this->isModalOpen = true;
    }

    public function view($id)
    {
        $this->viewingStudent = \App\Models\Student::with('class')->find($id);
        if ($this->viewingStudent && $this->viewingStudent->class_id == $this->classId) {
            $this->showViewModal = true;
        }
    }

    public function store()
    {
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
            'gender' => $this->gender,
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

        if ($this->isEditMode) {
            $student = \App\Models\Student::find($this->editStudentId);
            $student->update($data);
            session()->flash('message', 'Student updated successfully.');
        } else {
            \App\Models\Student::create($data);
            session()->flash('message', 'Student added successfully.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $student = \App\Models\Student::find($id);
        
        if (!$student || $student->class_id != $this->classId) {
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

        $query = \App\Models\Student::where('class_id', $this->classId);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('roll_no', 'like', '%' . $this->search . '%')
                  ->orWhere('admission_no', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->filterSport) {
            $query->where('sports', 'like', '%' . $this->filterSport . '%');
        }
        
        if ($this->filterActivity) {
            $query->where('extra_curriculars', 'like', '%' . $this->filterActivity . '%');
        }
        
        if ($this->filterTransport) {
            $query->where('transport_mode', $this->filterTransport);
             if ($this->filterTransport === 'school_bus' && $this->filterBus) {
                $query->where('vehicle_number', $this->filterBus);
            }
        }

        $students = $query->orderByRaw('CAST(roll_no AS INTEGER) ' . $this->sortOrder)->get();

        return view('livewire.teacher.student-list', [
            'students' => $students,
            'className' => $this->className,
            'sportsOptions' => $sportsOptions,
            'activityOptions' => $activityOptions
        ])->layout('components.layouts.teacher', ['title' => 'My Students']);
    }
}
