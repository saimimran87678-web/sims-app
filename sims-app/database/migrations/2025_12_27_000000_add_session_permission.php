<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Permission
        Permission::firstOrCreate(['name' => 'sessions.view-all', 'guard_name' => 'web']);

        // Assign to Super Admin (Role ID 1 usually, or by name)
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo('sessions.view-all');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'sessions.view-all')->delete();
    }
};
