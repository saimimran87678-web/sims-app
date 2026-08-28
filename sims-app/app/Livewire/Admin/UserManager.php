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
    public $allowed_shifts = 'both'; // morning, evening, both
    public $currentSessionIsRegular = false;
    
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
        'allowed_shifts' => 'required|in:morning,evening,both,regular',
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
        $activeSession = \App\Models\AcademicSession::find($activeSessionId);
        $this->currentSessionIsRegular = ($activeSession && $activeSession->shift_type === 'Regular');

        $shiftType = $this->currentSessionIsRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $users = User::query()
            ->leftJoin('session_user', function($join) use ($activeSessionId) {
                $join->on('users.id', '=', 'session_user.user_id')
                     ->where('session_user.academic_session_id', '=', $activeSessionId);
            })
            ->leftJoin('classes', function($join) use ($shiftType) {
                $join->on('session_user.class_id', '=', 'classes.id')
                     ->where('classes.shift_type', '=', $shiftType)
                     ->whereNull('classes.deleted_at');
            })
            ->select('users.*', 'classes.name as class_name', 'classes.shift_type as class_shift_type', 'session_user.is_active as session_is_active', 'session_user.class_id as session_class_id', 'session_user.class_subject as session_class_subject', 'session_user.allowed_shifts as session_allowed_shifts')
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
            ->join('classes', function($join) use ($activeSessionId, $shiftType) {
                $join->on('subject_allocations.class_id', '=', 'classes.id')
                     ->where('classes.academic_session_id', $activeSessionId)
                     ->where('classes.shift_type', $shiftType)
                     ->whereNull('classes.deleted_at');
            })
            ->whereIn('subject_allocations.user_id', $userIds)
            ->select('subject_allocations.user_id', 'subjects.name as subject', 'classes.name as class', 'classes.shift_type as class_shift_type')
            ->get()
            ->groupBy('user_id');

        $classes = DB::table('classes')
            ->where('classes.academic_session_id', $activeSessionId)
            ->where('classes.shift_type', $shiftType)
            ->whereNull('classes.deleted_at')
            ->orderBy('classes.numeric_value')
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
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'class_id', 'class_subject', 'teachingAssignments', 'allowed_shifts']);
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $activeSession = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $this->allowed_shifts = $isRegular ? 'regular' : 'both';
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

        $activeSession = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $this->class_id = $sessionUser ? $sessionUser->class_id : null;
        $this->class_subject = $sessionUser ? $sessionUser->class_subject : null;
        if ($this->class_id) {
            $classObj = DB::table('classes')->where('id', $this->class_id)->first();
            if ($classObj && $classObj->shift_type !== $shiftType) {
                $this->class_id = null;
                $this->class_subject = null;
            }
        }
        
        if ($isRegular) {
            $this->allowed_shifts = 'regular';
        } else {
            $this->allowed_shifts = $sessionUser ? ($sessionUser->allowed_shifts ?? 'both') : 'both';
            if ($this->allowed_shifts === 'regular') {
                $this->allowed_shifts = 'both';
            }
        }
        $this->password = ''; 

        // Load Allocations
        $allocations = DB::table('subject_allocations')
            ->join('classes', function($join) use ($activeSessionId, $shiftType) {
                $join->on('subject_allocations.class_id', '=', 'classes.id')
                     ->where('classes.academic_session_id', $activeSessionId)
                     ->where('classes.shift_type', $shiftType)
                     ->whereNull('classes.deleted_at');
            })
            ->where('subject_allocations.user_id', $user->id)
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
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $activeSession = \App\Models\AcademicSession::find($activeSessionId);
            $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
            if ($shiftType === 'both') {
                $shiftType = 'morning';
            }

            $finalClassId = ($this->role === 'teacher' && !empty($this->class_id)) ? $this->class_id : null;
            $finalClassSubject = ($this->role === 'teacher' && !empty($this->class_id) && !empty($this->class_subject)) ? $this->class_subject : null;
            $finalAllowedShifts = $this->role === 'teacher' ? ($this->currentSessionIsRegular ? 'regular' : $this->allowed_shifts) : 'both';

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
                
                // Get existing class teacher assignment
                $existingSessionUser = DB::table('session_user')
                    ->where('user_id', $user->id)
                    ->where('academic_session_id', $activeSessionId)
                    ->first();

                $existingClassId = $existingSessionUser ? $existingSessionUser->class_id : null;
                $existingClass = $existingClassId ? DB::table('classes')->where('id', $existingClassId)->first() : null;

                $finalClassIdToSave = $existingClassId;
                $finalClassSubjectToSave = $existingSessionUser ? $existingSessionUser->class_subject : null;

                if ($this->role === 'teacher') {
                    if (!empty($this->class_id)) {
                        $finalClassIdToSave = $this->class_id;
                        $finalClassSubjectToSave = !empty($this->class_subject) ? $this->class_subject : null;
                    } else {
                        // Admin selected "No Class Assigned" or cleared it in current shift.
                        // We should only clear the assignment if the existing assignment belongs to the active shift!
                        if ($existingClass && $existingClass->shift_type === $shiftType) {
                            $finalClassIdToSave = null;
                            $finalClassSubjectToSave = null;
                        }
                    }
                } else {
                    // Not a teacher - clear any class teacher assignment
                    $finalClassIdToSave = null;
                    $finalClassSubjectToSave = null;
                }

                // Update or Insert Session User class assignment
                DB::table('session_user')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'academic_session_id' => $activeSessionId,
                    ],
                    [
                        'class_id' => $finalClassIdToSave,
                        'class_subject' => $finalClassSubjectToSave,
                        'allowed_shifts' => $finalAllowedShifts,
                        'updated_at' => now(),
                    ]
                );
                
                // Sync Allocations for Current Shift
                $currentShiftClassIds = DB::table('classes')
                    ->where('classes.academic_session_id', $activeSessionId)
                    ->where('classes.shift_type', $shiftType)
                    ->whereNull('classes.deleted_at')
                    ->pluck('classes.id');
                
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
                        'allowed_shifts' => $finalAllowedShifts,
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
            }

            // Sync Roles & Permissions since user was created/updated from Admin panel
            if ($user->role === 'admin') {
                // If this is the Super Admin user (e.g. the first user registered), keep their Super Admin role.
                // Otherwise, they are a staff admin: remove any Spatie roles, and assign all permissions in the
                // active session except for the Feature Sharing component permissions ('access-control.manage', 'permissions.assign').
                $isFirstUser = (\App\Models\User::orderBy('id', 'asc')->first()?->id === $user->id);
                if ($isFirstUser) {
                    $user->syncRoles(['Super Admin']);
                    DB::table('session_user_permissions')
                        ->where('user_id', $user->id)
                        ->delete();
                } else {
                    $user->syncRoles([]);
                    $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                    if ($activeSessionId) {
                        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
                        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
                        $shiftsToInsert = $isRegular ? ['regular'] : ['morning', 'evening'];

                        $allPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
                        $insertData = [];
                        foreach ($shiftsToInsert as $st) {
                            foreach ($allPermissions as $perm) {
                                if (in_array($perm, ['access-control.manage', 'permissions.assign'])) {
                                    continue;
                                }
                                $insertData[] = [
                                    'user_id'             => $user->id,
                                    'academic_session_id' => $activeSessionId,
                                    'permission_name'     => $perm,
                                    'shift_type'          => $st,
                                    'created_at'          => now(),
                                    'updated_at'          => now(),
                                ];
                            }
                        }
                        DB::table('session_user_permissions')
                            ->where('user_id', $user->id)
                            ->where('academic_session_id', $activeSessionId)
                            ->delete();
                        DB::table('session_user_permissions')->insert($insertData);
                    }
                }
            } elseif ($user->role === 'teacher') {
                $user->syncRoles(['Teacher']);
                
                // If created or updated from admin panel, enable all features (Spatie permissions)
                // for the active session, except for access control permissions.
                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                if ($activeSessionId) {
                    $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
                    $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
                    $shiftsToInsert = $isRegular ? ['regular'] : ['morning', 'evening'];

                    $allPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
                    $insertData = [];
                    foreach ($shiftsToInsert as $st) {
                        foreach ($allPermissions as $perm) {
                            if (in_array($perm, ['access-control.manage', 'permissions.assign'])) {
                                continue;
                            }
                            $insertData[] = [
                                'user_id'             => $user->id,
                                'academic_session_id' => $activeSessionId,
                                'permission_name'     => $perm,
                                'shift_type'          => $st,
                                'created_at'          => now(),
                                'updated_at'          => now(),
                            ];
                        }
                    }
                    DB::table('session_user_permissions')
                        ->where('user_id', $user->id)
                        ->where('academic_session_id', $activeSessionId)
                        ->delete();
                    DB::table('session_user_permissions')->insert($insertData);
                }
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

        if ($id == 1) {
            session()->flash('error', 'The owner account cannot be deleted.');
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

        if ($id == 1) {
            session()->flash('error', 'The owner account cannot be disabled.');
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

        // Guard: ensure the active session actually exists in the database
        if (!$activeSessionId || !\App\Models\AcademicSession::find($activeSessionId)) {
            session()->flash('error', 'No valid active academic session found. Please set an active session first.');
            return;
        }
        
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
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'class_id', 'class_subject', 'teachingAssignments', 'allowed_shifts']);
    }
}
