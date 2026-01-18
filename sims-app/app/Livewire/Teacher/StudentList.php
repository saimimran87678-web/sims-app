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
    public $gender = 'male';
    public $dob = '';
    public $admission_date = '';
    public $sports = [];
    public $extra_curriculars = [];
    public $transport_mode = 'none';
    public $vehicle_number = '';
    public $address = '';
    public $photo;

    protected $rules = [
        'name' => 'required|min:2',
        'roll_no' => 'required',
        'admission_no' => 'required',
        'father_name' => 'nullable',
        'phone' => 'nullable',
        'gender' => 'required|in:male,female,other',
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
        $this->classId = Auth::user()->class_id;
        
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

    // ... (rest of methods)

    public function render()
    {
        if (!$this->classId) {
            return view('livewire.teacher.student-list', [
                'students' => collect([]),
                'className' => 'No Class Assigned'
            ])->layout('components.layouts.teacher', ['title' => 'My Students']);
        }

        $query = \App\Models\Student::where('class_id', $this->classId);

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
            'className' => $this->className
        ])->layout('components.layouts.teacher', ['title' => 'My Students']);
    }
}
