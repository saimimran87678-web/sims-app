<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'exams.view-sessions',
            'students.view-sessions',
            'schedule.view-sessions',
            'reports.view-sessions',
            'classes.view-sessions',
            'grades.view-sessions'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign to Super Admin
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        // Permission::whereIn('name', [...])->delete();
    }
};
