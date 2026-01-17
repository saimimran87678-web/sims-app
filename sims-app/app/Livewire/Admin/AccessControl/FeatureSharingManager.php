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
        $query = User::orderBy('name');

        // Security: Hide Super Admins and regular Admins from selection list
        // Only allow sharing with relevant staff (Teachers, etc.) if delegate
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'admin']);
            })->where('role', '!=', 'admin');
        }

        $this->users = $query->get();
        $this->allClasses = DB::table('classes')->orderBy('numeric_value')->get();
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

        $user = User::find($this->selectedUserId);
        if (!$user) return;

        $permissions = $this->permissionsGrouped[$groupName]['permissions'];
        
        if ($enable) {
            $user->givePermissionTo($permissions);
        } else {
            $user->revokePermissionTo($permissions);
        }
        
        $this->loadUserPermissions();
    }

    public function loadClassAccess()
    {
        if ($this->selectedUserId) {
            $this->userClassAccess = DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
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
            // All are selected, so deselect all
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->delete();
        } else {
            // Select all
            DB::table('user_class_access')
                ->where('user_id', $this->selectedUserId)
                ->delete(); // Clear first to avoid duplicates/conflicts logic
            
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
            $user = User::find($this->selectedUserId);
            $this->userPermissions = $user ? $user->getDirectPermissions()->pluck('name')->toArray() : [];
            
            // Also optionally show roles, but we focus on direct permissions as per "Sharing" concept
        } else {
            $this->userPermissions = [];
        }
    }

    public function togglePermission($permissionName)
    {
        if (!$this->selectedUserId) return;

        $user = User::find($this->selectedUserId);
        if (!$user) return;

        if ($user->hasDirectPermission($permissionName)) {
            $user->revokePermissionTo($permissionName);
        } else {
            $user->givePermissionTo($permissionName);
        }

        $this->loadUserPermissions();
        session()->flash('message', 'Permission updated.');
    }

    public function render()
    {
        // Simple search filter for users
        $query = User::query();

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
