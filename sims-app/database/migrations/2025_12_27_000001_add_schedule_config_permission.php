<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure schedule.config exists
        Permission::firstOrCreate(['name' => 'schedule.config', 'guard_name' => 'web']);

        // Assign to Super Admin
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo('schedule.config');
        }
    }

    public function down(): void
    {
        // Optional: Permission::where('name', 'schedule.config')->delete();
    }
};
