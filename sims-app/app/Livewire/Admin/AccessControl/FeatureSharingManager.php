<?php

namespace App\Livewire\Admin\AccessControl;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class FeatureSharingManager extends Component
{
    public $selectedUserId;
    public $users = [];
    public $permissionsGrouped = [];
    public $userPermissions = [];
    public $search = '';
    
    // Class Access Control
    public $allClasses = [];
    public $userClassAccess = [];

    public function mount()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        
        $query = User::orderBy('name')
            ->join('session_user', 'users.id', '=', 'session_user.user_id')
            ->where('session_user.academic_session_id', $activeSessionId)
            ->where('session_user.is_active', true)
            ->select('users.*');

        if ($shiftType !== 'both' && $shiftType !== 'regular') {
            $query->where(function($q) use ($shiftType) {
                $q->where('session_user.allowed_shifts', 'both')
                  ->orWhere('session_user.allowed_shifts', $shiftType);
            });
        }

        // Security: Hide Super Admins and regular Admins from selection list
        // Only allow sharing with relevant staff (Teachers, etc.) if delegate
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'admin']);
            })->where('role', '!=', 'admin');
        }

        $this->users = $query->get();

        $classQuery = DB::table('classes')
            ->where('academic_session_id', $activeSessionId);

        if ($shiftType !== 'both' && $shiftType !== 'regular') {
            $classQuery->where('shift_type', $shiftType);
        }

        $this->allClasses = $classQuery->orderBy('numeric_value')->get();
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        $allPermissions = Permission::all();
        
        // Define Groups with Icons and Descriptions
        $groupsConfig = [
            'Exams' => [
                'icon' => 'clipboard-document-list', // Heroicon name
                'desc' => 'Manage exams, datesheets, and marks.',
                'perms' => ['exams.manage', 'exam.create', 'exam.edit', 'exam.delete', 'exam.datesheet', 'exams.view-sessions']
            ],
            'Students' => [
                'icon' => 'users',
                'desc' => 'Admissions and student records.',
                'perms' => ['students.manage', 'student.create', 'student.edit', 'student.delete', 'students.view-all-classes', 'students.view-sessions']
            ],
            'Classes & Sessions' => [
                'icon' => 'academic-cap',
                'desc' => 'Manage classes, subjects and academic sessions.',
                'perms' => ['classes.manage', 'class.create', 'class.edit', 'class.delete', 'subjects.manage', 'subject.create', 'subject.delete', 'sessions.manage', 'classes.view-sessions']
            ],
            'Schedule' => [
                'icon' => 'calendar',
                'desc' => 'Timetable management and configuration.',
                'perms' => ['schedule.manage', 'schedule.view', 'schedule.config', 'schedule.view-sessions']
            ],
            'Fee Management' => [
                'icon' => 'credit-card',
                'desc' => 'Manage invoices, fees collection, and defaulters.',
                'perms' => ['fees.manage', 'fees.view-sessions']
            ],
            'Substitutions & Attendance' => [
                'icon' => 'arrow-path-rounded-square',
                'desc' => 'Daily substitutions and teacher attendance.',
                'perms' => ['substitutions.manage', 'substitutions.view-sessions']
            ],
            'Reports' => [
                'icon' => 'chart-bar',
                'desc' => 'View and print academic reports.',
                'perms' => ['reports.view', 'reports.view-all-classes']
            ],
            'Access Control' => [
                'icon' => 'key',
                'desc' => 'Manage feature sharing and user roles.',
                'perms' => ['access-control.manage', 'permissions.assign', 'users.manage']
            ],
            'Data Scope' => [
                'icon' => 'adjustments-horizontal', // Heroicon
                'desc' => 'Manage Subject Allocation & Gradebook Access.',
                'perms' => [
                    'allocations.view',
                    'allocations.manage',
                    'allocations.lock'
                ]
            ],
        ];

        $this->permissionsGrouped = [];
        
        foreach ($groupsConfig as $groupName => $config) {
            $perms = $allPermissions->whereIn('name', $config['perms']);
            if ($perms->isNotEmpty()) {
                $this->permissionsGrouped[$groupName] = [
                    'icon' => $config['icon'],
                    'desc' => $config['desc'],
                    'permissions' => $perms
                ];
            }
        }
    }

    public function toggleGroup($groupName, $enable = true)
    {
        if (!$this->selectedUserId || !isset($this->permissionsGrouped[$groupName])) return;

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        if (!$activeSessionId) return;

        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $shiftsToToggle = $shiftType === 'both' ? ['morning', 'evening'] : [$shiftType];

        $permissions = $this->permissionsGrouped[$groupName]['permissions'];
        
        if ($enable) {
            foreach ($shiftsToToggle as $st) {
                foreach ($permissions as $permission) {
                    DB::table('session_user_permissions')->updateOrInsert([
                        'user_id' => $this->selectedUserId,
                        'academic_session_id' => $activeSessionId,
                        'permission_name' => $permission->name,
                        'shift_type' => $st,
                    ], [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } else {
            foreach ($shiftsToToggle as $st) {
                foreach ($permissions as $permission) {
                    DB::table('session_user_permissions')
                        ->where('user_id', $this->selectedUserId)
                        ->where('academic_session_id', $activeSessionId)
                        ->where('permission_name', $permission->name)
                        ->where('shift_type', $st)
                        ->delete();
                }
            }
        }
        
        $this->loadUserPermissions();
    }

    public function loadClassAccess()
    {
        if ($this->selectedUserId) {
            $allIds = $this->allClasses->pluck('id')->toArray();
            $this->userClassAccess = DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->whereIn('class_id', $allIds)
                ->pluck('class_id')
                ->toArray();
        } else {
            $this->userClassAccess = [];
        }
    }

    public function toggleClassAccess($classId)
    {
        if (!$this->selectedUserId) return;

        if (in_array($classId, $this->userClassAccess)) {
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->where('class_id', $classId)
                ->delete();
        } else {
            DB::table('user_class_access')->insert([
                'user_id' => $this->selectedUserId,
                'class_id' => $classId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->loadClassAccess();
    }

    public function toggleAllClasses()
    {
        if (!$this->selectedUserId) return;

        $allIds = $this->allClasses->pluck('id')->toArray();
        $diff = array_diff($allIds, $this->userClassAccess);

        if (empty($diff)) {
            // All are selected, so deselect all for active session classes
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->whereIn('class_id', $allIds)
                ->delete();
        } else {
            // Select all for active session classes
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->whereIn('class_id', $allIds)
                ->delete(); // Clear active session classes first to avoid duplicates/conflicts logic
            
            $data = [];
            $now = now();
            foreach($allIds as $id) {
                $data[] = [
                    'user_id' => $this->selectedUserId,
                    'class_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            DB::table('user_class_access')->insert($data);
        }
        $this->loadClassAccess();
    }

    public function updatedSelectedUserId()
    {
        $this->loadUserPermissions();
        $this->loadClassAccess();
    }

    public function getFilteredUsersProperty()
    {
        if (empty($this->search)) {
            return $this->users;
        }

        return $this->users->filter(function ($user) {
            return stripos($user->name, $this->search) !== false 
                || stripos($user->email, $this->search) !== false;
        });
    }

    public function loadUserPermissions()
    {
        if ($this->selectedUserId) {
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

            $query = DB::table('session_user_permissions')
                ->where('user_id', $this->selectedUserId)
                ->where('academic_session_id', $activeSessionId);

            if ($shiftType === 'both') {
                $this->userPermissions = $query->pluck('permission_name')->unique()->toArray();
            } else {
                $this->userPermissions = $query->where('shift_type', $shiftType)
                    ->pluck('permission_name')
                    ->toArray();
            }
        } else {
            $this->userPermissions = [];
        }
    }

    public function togglePermission($permissionName)
    {
        if (!$this->selectedUserId) return;

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        if (!$activeSessionId) return;

        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $shiftsToToggle = $shiftType === 'both' ? ['morning', 'evening'] : [$shiftType];

        if (in_array($permissionName, $this->userPermissions)) {
            DB::table('session_user_permissions')
                ->where('user_id', $this->selectedUserId)
                ->where('academic_session_id', $activeSessionId)
                ->where('permission_name', $permissionName)
                ->whereIn('shift_type', $shiftsToToggle)
                ->delete();
        } else {
            foreach ($shiftsToToggle as $st) {
                DB::table('session_user_permissions')->updateOrInsert([
                    'user_id' => $this->selectedUserId,
                    'academic_session_id' => $activeSessionId,
                    'permission_name' => $permissionName,
                    'shift_type' => $st,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->loadUserPermissions();
        session()->flash('message', 'Permission updated.');
    }

    public function getOppositeShiftType()
    {
        $sessionObj = \App\Models\AcademicSession::find(\App\Models\AcademicSession::getActiveSessionId());
        if ($sessionObj && $sessionObj->shift_type === 'Regular') {
            return null;
        }

        $currentShift = session('selected_shift_type', 'morning');
        if ($currentShift === 'both') {
            return null;
        }

        return $currentShift === 'morning' ? 'evening' : 'morning';
    }

    public function getIsActiveInOppositeShiftProperty()
    {
        if (!$this->selectedUserId) return false;
        
        $oppositeShift = $this->getOppositeShiftType();
        if (!$oppositeShift) return false;

        $currentSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionUser = DB::table('session_user')
            ->where('user_id', $this->selectedUserId)
            ->where('academic_session_id', $currentSessionId)
            ->where('is_active', true)
            ->first();

        if (!$sessionUser) return false;

        $allowed = $sessionUser->allowed_shifts ?? 'both';
        return $allowed === 'both' || $allowed === $oppositeShift;
    }

    public function syncToOppositeShift()
    {
        if (!$this->selectedUserId) return;
        
        $currentSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $oppositeShift = $this->getOppositeShiftType();
        if (!$oppositeShift || !$this->isActiveInOppositeShift) return;

        $currentShift = session('selected_shift_type', 'morning');
        
        // 1. Sync permissions from current shift to opposite shift
        $currentPermissions = DB::table('session_user_permissions')
            ->where('user_id', $this->selectedUserId)
            ->where('academic_session_id', $currentSessionId)
            ->where('shift_type', $currentShift)
            ->pluck('permission_name')
            ->toArray();
            
        DB::table('session_user_permissions')
            ->where('user_id', $this->selectedUserId)
            ->where('academic_session_id', $currentSessionId)
            ->where('shift_type', $oppositeShift)
            ->delete();
            
        $permissionData = [];
        $now = now();
        foreach ($currentPermissions as $permName) {
            $permissionData[] = [
                'user_id' => $this->selectedUserId,
                'academic_session_id' => $currentSessionId,
                'permission_name' => $permName,
                'shift_type' => $oppositeShift,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($permissionData)) {
            DB::table('session_user_permissions')->insert($permissionData);
        }
        
        // 2. Sync class access (match classes by name across shifts)
        $currentClassAccessIds = DB::table('user_class_access')
            ->where('user_id', $this->selectedUserId)
            ->whereIn('class_id', $this->allClasses->pluck('id')->toArray())
            ->pluck('class_id')
            ->toArray();
            
        if (!empty($currentClassAccessIds)) {
            $currentClassNames = DB::table('classes')
                ->whereIn('id', $currentClassAccessIds)
                ->pluck('name')
                ->toArray();
                
            $oppositeClassIds = DB::table('classes')
                ->where('academic_session_id', $currentSessionId)
                ->where('shift_type', $oppositeShift)
                ->whereIn('name', $currentClassNames)
                ->pluck('id')
                ->toArray();
                
            $allOppositeClassIds = DB::table('classes')
                ->where('academic_session_id', $currentSessionId)
                ->where('shift_type', $oppositeShift)
                ->pluck('id')
                ->toArray();
                
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->whereIn('class_id', $allOppositeClassIds)
                ->delete();
                
            $classAccessData = [];
            foreach ($oppositeClassIds as $classId) {
                $classAccessData[] = [
                    'user_id' => $this->selectedUserId,
                    'class_id' => $classId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($classAccessData)) {
                DB::table('user_class_access')->insert($classAccessData);
            }
        }
        
        session()->flash('message', 'Permissions and class access successfully synced to ' . ucfirst($oppositeShift) . ' shift.');
    }

    public function render()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        // Simple search filter for users - loading only active in active session
        $query = User::query()
            ->join('session_user', 'users.id', '=', 'session_user.user_id')
            ->where('session_user.academic_session_id', $activeSessionId)
            ->where('session_user.is_active', true)
            ->select('users.*');

        if ($shiftType !== 'both' && $shiftType !== 'regular') {
            $query->where(function($q) use ($shiftType) {
                $q->where('session_user.allowed_shifts', 'both')
                  ->orWhere('session_user.allowed_shifts', $shiftType);
            });
        }

        // Security: Hide Super Admins if current user is not Super Admin
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super Admin');
            });
        }

        $filteredUsers = $query->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();

        // Dynamic Layout based on Role
        $layout = auth()->user()->role === 'teacher' ? 'components.layouts.teacher' : 'components.layouts.admin';

        return view('livewire.admin.access-control.feature-sharing-manager', [
            'filteredUsers' => $filteredUsers
        ])->layout($layout, ['title' => 'Feature Sharing']);
    }
}
