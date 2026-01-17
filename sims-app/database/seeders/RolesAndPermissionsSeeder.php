<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Granular Permissions
        $permissions = [
            // Exams
            'exams.manage', // View the module
            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam.datesheet', // Manage datesheets
            
            // Users
            'users.manage',
            
            // Students
            'students.manage',
            'student.create',
            'student.edit',
            'student.delete',
            
            // Academic Sessions
            'sessions.manage',
            
            // Classes & Subjects
            'classes.manage',
            'subjects.manage',
            
            // Schedule
            'schedule.manage',
            'schedule.view',
            'schedule.config',
            
            // Reports
            'reports.view',
            'report.print-result',
            'report.print-gazette',
            
            
            // Access Control (The Sharing Mechanism)
            'access-control.manage', // Can access the sharing UI
            'permissions.assign',    // Can share features
            
            // Allocation Manager (Gradebook Access)
            'allocations.view',
            'allocations.manage',
            'allocations.lock',

            // Session History Access (Granular)
            'exams.view-sessions',
            'students.view-sessions',
            'classes.view-sessions',
            'schedule.view-sessions',
            'reports.view-sessions',
            'grades.view-sessions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles
        // Super Admin (Has everything)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Teacher (Standard Access)
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        // Assign standard teacher permissions (mostly they use their own portals, 
        // but if they access Admin panel features, they might need 'view' perms)
        // Assign standard teacher permissions (mostly they use their own portals, 
        // but if they access Admin panel features, they might need 'view' perms)
        // $teacherRole->givePermissionTo([]); // Now relying on Feature Sharing for these

        // Student
        $studentRole = Role::firstOrCreate(['name' => 'Student']);

        // Data Entry Operator (Example Custom Role)
        $dataEntryRole = Role::firstOrCreate(['name' => 'Data Entry']);
        $dataEntryRole->givePermissionTo([
            'students.manage',
            'student.create',
            'student.edit',
            // No delete
        ]);

        // 3. Migrate Existing Users
        // Admins -> Super Admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $user) {
            $user->assignRole($superAdminRole);
        }

        // Teachers -> Teacher Role
        $teachers = User::where('role', 'teacher')->get();
        foreach ($teachers as $user) {
            $user->assignRole($teacherRole);
        }
    }
}
