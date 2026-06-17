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

    // PIN Confirmation Fields
    public $isPinModalOpen = false;
    public $pin = '';
    public $usePasswordForPin = false;
    public $pendingAction = ''; // 'store', 'delete', 'toggleAccountStatus'
    public $pendingUserId = null;

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

    public function updatedClassId()
    {
        $this->class_subject = '';
    }

    public function render()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        $users = User::query()
            ->leftJoin('session_user', function($join) use ($activeSessionId) {
                $join->on('users.id', '=', 'session_user.user_id')
                     ->where('session_user.academic_session_id', '=', $activeSessionId);
            })
            ->leftJoin('classes', function($join) use ($activeSessionId) {
                $join->on('session_user.class_id', '=', 'classes.id')
                     ->where('classes.academic_session_id', '=', $activeSessionId);
            })
            ->select('users.*', 'classes.name as class_name', 'session_user.is_active as session_is_active', 'session_user.class_id as session_class_id', 'session_user.class_subject as session_class_subject')
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
            ->where('classes.academic_session_id', $activeSessionId)
            ->select('subject_allocations.user_id', 'subjects.name as subject', 'classes.name as class')
            ->get()
            ->groupBy('user_id');

        $classes = DB::table('classes')
            ->where('academic_session_id', $activeSessionId)
            ->orderBy('numeric_value')
            ->get();
        
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
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionUser = DB::table('session_user')
            ->where('user_id', $user->id)
            ->where('academic_session_id', $activeSessionId)
            ->first();

        $this->class_id = $sessionUser ? $sessionUser->class_id : null;
        $this->class_subject = $sessionUser ? $sessionUser->class_subject : null;
        $this->password = ''; 

        // Load Allocations
        $allocations = DB::table('subject_allocations')
            ->join('classes', 'subject_allocations.class_id', '=', 'classes.id')
            ->where('subject_allocations.user_id', $user->id)
            ->where('classes.academic_session_id', $activeSessionId)
            ->select('subject_allocations.*')
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

        // Check if Admin Action PIN is required
        $targetRole = $this->isEditMode ? User::findOrFail($this->userId)->role : $this->role;
        // If creating a new admin, or modifying an existing admin, or changing someone TO an admin
        if (\App\Models\Setting::get('admin_action_pin_enabled', false) && ($targetRole === 'admin' || $this->role === 'admin')) {
            $this->confirmPinAction('store');
            return;
        }

        $this->executeStore();
    }

    private function executeStore()
    {
        DB::beginTransaction();
        try {
            if ($this->isEditMode) {
                $user = User::findOrFail($this->userId);
                $data = [
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                ];
                if (!empty($this->password)) {
                    $data['password'] = Hash::make($this->password);
                }
                $user->update($data);
                
                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

                // Update or Insert Session User class assignment
                $finalClassId = ($this->role === 'teacher' && !empty($this->class_id)) ? $this->class_id : null;
                $finalClassSubject = ($this->role === 'teacher' && !empty($this->class_id) && !empty($this->class_subject)) ? $this->class_subject : null;

                DB::table('session_user')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'academic_session_id' => $activeSessionId,
                    ],
                    [
                        'class_id' => $finalClassId,
                        'class_subject' => $finalClassSubject,
                        'updated_at' => now(),
                    ]
                );
                
                // Sync Allocations for Current Shift
                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                $currentShiftClassIds = DB::table('classes')
                    ->where('academic_session_id', $activeSessionId)
                    ->pluck('id');
                
                DB::table('subject_allocations')
                    ->where('user_id', $user->id)
                    ->whereIn('class_id', $currentShiftClassIds)
                    ->delete();
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
                ]);

                // Automatically attach newly created users to the active session
                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                if ($activeSessionId) {
                    \Illuminate\Support\Facades\DB::table('session_user')->insert([
                        'user_id' => $user->id,
                        'academic_session_id' => $activeSessionId,
                        'is_active' => true,
                        'is_primary' => true,
                        'class_id' => $finalClassId,
                        'class_subject' => $finalClassSubject,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
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

        $user = User::findOrFail($id);
        if (\App\Models\Setting::get('admin_action_pin_enabled', false) && $user->role === 'admin') {
            $this->confirmPinAction('delete', $id);
            return;
        }

        $this->executeDelete($id);
    }

    private function executeDelete($id)
    {
        User::findOrFail($id)->delete();
        session()->flash('message', 'User deleted successfully.');
    }

    public function toggleAccountStatus($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');
            return;
        }

        $user = User::findOrFail($id);
        if (\App\Models\Setting::get('admin_action_pin_enabled', false) && $user->role === 'admin') {
            $this->confirmPinAction('toggleAccountStatus', $id);
            return;
        }

        $this->executeToggleAccountStatus($id);
    }

    private function executeToggleAccountStatus($id)
    {
        $user = User::findOrFail($id);
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        
        $sessionUser = \Illuminate\Support\Facades\DB::table('session_user')
            ->where('user_id', $user->id)
            ->where('academic_session_id', $activeSessionId)
            ->first();

        if ($sessionUser) {
            $newStatus = !$sessionUser->is_active;
            \Illuminate\Support\Facades\DB::table('session_user')
                ->where('user_id', $user->id)
                ->where('academic_session_id', $activeSessionId)
                ->update(['is_active' => $newStatus]);
                
            session()->flash('message', 'User account ' . ($newStatus ? 'enabled' : 'disabled') . ' successfully for the active session.');
        } else {
            // User isn't explicitly part of this session yet; attach them as enabled.
            \Illuminate\Support\Facades\DB::table('session_user')->insert([
                'user_id' => $user->id,
                'academic_session_id' => $activeSessionId,
                'is_active' => true,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            session()->flash('message', 'User account enabled and attached to the active session.');
        }
    }

    public function confirmPinAction($action, $userId = null)
    {
        $this->pendingAction = $action;
        $this->pendingUserId = $userId;
        $this->isPinModalOpen = true;
        $this->pin = '';
        $this->usePasswordForPin = false;
        
        // Hide edit modal if it's open, but remember state
        if ($action === 'store') {
            $this->isModalOpen = false;
        }
    }

    public function verifyPin()
    {
        if ($this->usePasswordForPin) {
            $this->validate(['pin' => 'required']);
            if (!Hash::check($this->pin, auth()->user()->password)) {
                $this->addError('pin', 'Incorrect password.');
                return;
            }
        } else {
            $this->validate(['pin' => 'required']);
            $correctPin = \App\Models\Setting::get('admin_action_pin');
            if ($this->pin !== $correctPin) {
                $this->addError('pin', 'Incorrect PIN.');
                return;
            }
        }

        // Success
        $this->isPinModalOpen = false;
        $this->pin = '';

        if ($this->pendingAction === 'store') {
            $this->executeStore();
        } elseif ($this->pendingAction === 'delete') {
            $this->executeDelete($this->pendingUserId);
        } elseif ($this->pendingAction === 'toggleAccountStatus') {
            $this->executeToggleAccountStatus($this->pendingUserId);
        }

        $this->pendingAction = '';
        $this->pendingUserId = null;
    }

    public function closePinModal()
    {
        $this->isPinModalOpen = false;
        $this->pin = '';
        
        // If we were trying to store, bring back the edit modal
        if ($this->pendingAction === 'store') {
            $this->isModalOpen = true;
        }
        $this->pendingAction = '';
        $this->pendingUserId = null;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'class_id', 'class_subject', 'teachingAssignments']);
    }
}
