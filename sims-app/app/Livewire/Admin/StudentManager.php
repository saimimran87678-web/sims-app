<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\Student;
use App\Models\Classes;

class StudentManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Search & Filter
    public $search = '';
    public $selectedClassId = '';
    public $filterSport = '';
    public $filterActivity = '';
    public $filterTransport = '';
    public $filterBus = '';
    public $viewMode = 'grid'; // 'grid' or 'list'
    
    // Modal State
    public $showModal = false;
    public $showViewModal = false; // For Profile View
    public $isEditing = false;
    public $editingStudentId = null;
    public $viewingStudent = null; // Holds the student model for viewing

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
        'filterBus'
    ];

    // Form Data
    public $name = '';
    public $roll_no = '';
    public $admission_no = '';
    public $father_name = '';
    public $phone = '';
    public $class_id = '';
    public $section_id = '';
    public $sports = [];
    public $extra_curriculars = [];
    public $transport_mode = 'none';
    public $vehicle_number = '';
    public $dob = '';
    public $admission_date = '';
    public $address = '';
    public $photo;

    // Constants for Options
    public const SPORTS_OPTIONS = ['Cricket', 'Football', 'Hockey', 'Badminton', 'Table Tennis', 'Volleyball', 'Basketball', 'Athletics'];
    public const ACTIVITY_OPTIONS = ['Naat', 'Tilawat', 'Speech (Urdu)', 'Speech (English)', 'Debate', 'Quiz', 'Drama'];
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
        return [
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'class_id' => 'required|exists:classes,id',
            'admission_no' => [
                'required', 
                'string', 
                Rule::unique('students', 'admission_no')->ignore($this->editingStudentId)
            ],
            'roll_no' => [
                'required',
                'string',
                Rule::unique('students', 'roll_no')
                    ->where(function ($query) {
                        return $query->where('class_id', $this->class_id);
                    })
                    ->ignore($this->editingStudentId)
            ],
            'sports' => 'array',
            'extra_curriculars' => 'array',
            'extra_curriculars' => 'array',
            'transport_mode' => 'required|string',
            'vehicle_number' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'admission_date' => 'nullable|date',
            'photo' => 'nullable|image|max:1024',
            'address' => 'nullable|string|max:1000',
        ];
    }

    public function mount()
    {
        $this->authorize('students.manage');
        $this->academicSessions = DB::table('academic_sessions')->orderBy('start_date', 'desc')->get();
        
        $activeSessionId = $this->academicSessions->where('is_active', true)->first()->id ?? $this->academicSessions->first()->id ?? null;

        // Enforce Data Scope
        if (!auth()->user()->can('students.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }
        
        $this->loadClasses();
    }

    public function loadClasses()
    {
        if ($this->selectedSessionId) {
            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
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

    // Reset pagination when filtering
    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedClassId() { $this->resetPage(); }
    public function updatedFilterSport() { $this->resetPage(); }
    public function updatedFilterActivity() { $this->resetPage(); }
    public function updatedFilterTransport() { $this->resetPage(); }
    public function updatedFilterBus() { $this->resetPage(); }

    public function openModal()
    {
        $this->reset(['name', 'roll_no', 'admission_no', 'father_name', 'phone', 'isEditing', 'editingStudentId', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address']);
        $this->class_id = $this->selectedClassId; // Default to currently selected filter
        $this->showModal = true;
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        
        $this->editingStudentId = $id;
        $this->name = $student->name;
        $this->roll_no = $student->roll_no;
        $this->admission_no = $student->admission_no;
        $this->father_name = $student->father_name;
        $this->phone = $student->phone;
        $this->class_id = $student->class_id;
        $this->section_id = $student->section_id;
        
        // Deserialize comma-separated strings
        $this->sports = $student->sports ? explode(',', $student->sports) : [];
        $this->extra_curriculars = $student->extra_curriculars ? explode(',', $student->extra_curriculars) : [];
        $this->transport_mode = $student->transport_mode ?? 'none';
        $this->vehicle_number = $student->vehicle_number ?? '';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->dob = $student->dob ? $student->dob->format('Y-m-d') : '';
        $this->admission_date = $student->admission_date ? $student->admission_date->format('Y-m-d') : '';
        $this->address = $student->address;

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
        $validated = $this->validate();

        // Serialize arrays
        $data = [
            'name' => $this->name,
            'roll_no' => $this->roll_no,
            'admission_no' => $this->admission_no,
            'father_name' => $this->father_name,
            'phone' => $this->phone,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'sports' => !empty($this->sports) ? implode(',', $this->sports) : null,
            'extra_curriculars' => !empty($this->extra_curriculars) ? implode(',', $this->extra_curriculars) : null,
            'transport_mode' => $this->transport_mode,
            'vehicle_number' => $this->vehicle_number,
            'dob' => $this->dob ?: null,
            'admission_date' => $this->admission_date ?: null,
            'address' => $this->address,
        ];

        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $data['profile_photo_path'] = $path;
        }

        if ($this->isEditing) {
            Student::where('id', $this->editingStudentId)->update($data);
            session()->flash('message', 'Student updated successfully.');
        } else {
            Student::create($data);
            session()->flash('message', 'Student added successfully.');
        }

        $this->showModal = false;
        $this->showModal = false;
        $this->reset(['name', 'roll_no', 'admission_no', 'father_name', 'phone', 'isEditing', 'sports', 'extra_curriculars', 'transport_mode', 'vehicle_number', 'dob', 'admission_date', 'photo', 'address']);
    }

    public function delete($id)
    {
        Student::where('id', $id)->delete();
        session()->flash('message', 'Student deleted successfully.');
    }

    public function render()
    {
        $viewClasses = $this->classes;

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

        $studentsQuery = Student::query()
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.name as class_name')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->when($this->selectedClassId, function($q) {
                $q->where('students.class_id', $this->selectedClassId);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->where('students.name', 'like', '%' . $this->search . '%')
                      ->orWhere('students.roll_no', 'like', '%' . $this->search . '%')
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
            });

        // 1. Mandatory Teacher Restriction (Always apply)
        if ($isTeacher) {
            $studentsQuery->whereIn('students.class_id', $allowedClassIds);
        }

        // 2. Selection Filter
        if ($this->selectedClassId) {
            $studentsQuery->where('students.class_id', $this->selectedClassId);
        }

        $students = $studentsQuery->orderBy('id', 'desc')->paginate(10);

        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.student-manager', [
            'students' => $students,
            'classes' => $viewClasses
        ])->layout($layout, ['title' => 'Student Management']);
    }
}
