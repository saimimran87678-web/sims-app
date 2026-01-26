<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\SchoolClass; // Using SchoolClass to avoid conflict if aliased, but App\Models\ClassModel doesn't exist. 
// Wait, I created 'classes' table but did I create a specific model for it?
// I used DB::table('classes') in other places. 
// Let's check if I have a Class model. I likely need to create it or use DB facade.
// In GradeManager I used DB::table('classes').
// I should probably use DB facade to be consistent and avoid model issues if 'Class' is reserved.
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isEditMode = false;

    // Form Fields
    public $userId;
    public $name;
    public $email;
    public $password;
    public $role = 'teacher';
    public $class_id = null;
    public $class_subject = ''; // Subject for Class Teacher
    
    // New Fields for React Parity
    public $teachingAssignments = []; // Array of ['class_id' => '', 'subject_id' => '']
    public $availableSubjects = []; // For dynamic dropdowns in assignments
    
    // Helper for loading subjects based on class selection in assignment row
    public function loadSubjectsForClass($index, $classId)
    {
        if(!$classId) return;
        $this->teachingAssignments[$index]['subjects'] = DB::table('subjects')->where('class_id', $classId)->get()->toArray();
    }

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'role' => 'required|in:admin,teacher',
        'class_id' => 'nullable|exists:classes,id',
        'class_subject' => 'nullable|string',
        'teachingAssignments.*.class_id' => 'required|exists:classes,id',
        'teachingAssignments.*.subject_id' => 'required|exists:subjects,id',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::query()
            ->leftJoin('classes', 'users.class_id', '=', 'classes.id')
            ->select('users.*', 'classes.name as class_name')
            ->where(function($q) {
                $q->where('users.name', 'like', '%' . $this->search . '%')
                  ->orWhere('users.email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('users.created_at', 'desc')
            ->paginate(10);

        // Load subject allocations for each user
        $userIds = $users->pluck('id')->toArray();
        $allocations = DB::table('subject_allocations')
            ->join('subjects', 'subject_allocations.subject_id', '=', 'subjects.id')
            ->join('classes', 'subject_allocations.class_id', '=', 'classes.id')
            ->whereIn('subject_allocations.user_id', $userIds)
            ->select('subject_allocations.user_id', 'subjects.name as subject', 'classes.name as class')
            ->get()
            ->groupBy('user_id');

        $classes = DB::table('classes')->orderBy('numeric_value')->get();
        
        $classTeacherSubjects = [];
        if($this->class_id) {
            $classTeacherSubjects = DB::table('subjects')->where('class_id', $this->class_id)->get();
        }

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'classes' => $classes,
            'classTeacherSubjects' => $classTeacherSubjects,
            'userAllocations' => $allocations
        ])->layout('components.layouts.admin', ['title' => 'User Management']);
    }

    public function create()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'class_id', 'class_subject', 'teachingAssignments']);
        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->class_id = $user->class_id;
        $this->class_subject = $user->class_subject;
        $this->password = ''; 

        // Load Allocations
        $allocations = DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->get();
        
        $this->teachingAssignments = [];
        foreach($allocations as $alloc) {
            $this->teachingAssignments[] = [
                'class_id' => $alloc->class_id,
                'subject_id' => $alloc->subject_id,
                'subjects' => DB::table('subjects')->where('class_id', $alloc->class_id)->get()->toArray()
            ];
        }

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function addAssignment()
    {
        $this->teachingAssignments[] = ['class_id' => '', 'subject_id' => '', 'subjects' => []];
    }

    public function removeAssignment($index)
    {
        unset($this->teachingAssignments[$index]);
        $this->teachingAssignments = array_values($this->teachingAssignments);
    }
    
    // Livewire hook to update subjects when class changes in Repeater
    public function updatedTeachingAssignments($value, $key)
    {
        // Key formats: "0.class_id" or "teachingAssignments.0.class_id"
        $parts = explode('.', $key);
        $count = count($parts);
        
        if ($count >= 2 && end($parts) === 'class_id') {
            // Index is the second to last element
            $index = $parts[$count - 2];
            $this->loadSubjectsForClass($index, $value);
        }
    }

    public function store()
    {
        $rules = $this->rules;
        if ($this->isEditMode) {
            $rules['email'] = 'required|email|unique:users,email,' . $this->userId;
            $rules['password'] = 'nullable|min:6'; 
        }

        $this->validate($rules);

        DB::beginTransaction();
        try {
            if ($this->isEditMode) {
                $user = User::findOrFail($this->userId);
                $data = [
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                    'class_id' => ($this->role === 'teacher' && !empty($this->class_id)) ? $this->class_id : null,
                    'class_subject' => ($this->role === 'teacher' && $this->class_id) ? $this->class_subject : null,
                ];
                if (!empty($this->password)) {
                    $data['password'] = Hash::make($this->password);
                }
                $user->update($data);
                
                // Sync Allocations
                DB::table('subject_allocations')->where('user_id', $user->id)->delete();
                if($this->role === 'teacher') {
                     foreach($this->teachingAssignments as $assignment) {
                        DB::table('subject_allocations')->insert([
                            'user_id' => $user->id,
                            'class_id' => $assignment['class_id'],
                            'subject_id' => $assignment['subject_id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                session()->flash('message', 'User updated successfully.');
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'role' => $this->role,
                    'class_id' => ($this->role === 'teacher' && !empty($this->class_id)) ? $this->class_id : null,
                    'class_subject' => ($this->role === 'teacher' && $this->class_id) ? $this->class_subject : null,
                ]);
                
                 if($this->role === 'teacher') {
                     foreach($this->teachingAssignments as $assignment) {
                        DB::table('subject_allocations')->insert([
                            'user_id' => $user->id,
                            'class_id' => $assignment['class_id'],
                            'subject_id' => $assignment['subject_id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                session()->flash('message', 'User created successfully.');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving user: ' . $e->getMessage());
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        User::findOrFail($id)->delete();
        session()->flash('message', 'User deleted successfully.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'class_id', 'class_subject', 'teachingAssignments']);
    }
}
