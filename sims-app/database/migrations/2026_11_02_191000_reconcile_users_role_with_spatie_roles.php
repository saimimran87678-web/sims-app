<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure standard Spatie roles exist
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        $dataEntryRole = Role::firstOrCreate(['name' => 'Data Entry', 'guard_name' => 'web']);

        $firstAdminId = DB::table('users')->where('role', 'admin')->orderBy('id', 'asc')->value('id') ?? 1;

        $users = User::all();
        foreach ($users as $user) {
            $roleStr = strtolower($user->role ?? '');

            if ($roleStr === 'admin') {
                if ($user->id === $firstAdminId || $user->id === 1) {
                    if (!$user->hasRole('Super Admin')) {
                        $user->assignRole($superAdminRole);
                    }
                }
            } elseif ($roleStr === 'teacher') {
                if (!$user->hasRole('Teacher')) {
                    $user->assignRole($teacherRole);
                }
            } elseif ($roleStr === 'student') {
                if (!$user->hasRole('Student')) {
                    $user->assignRole($studentRole);
                }
            } elseif ($roleStr === 'data_entry') {
                if (!$user->hasRole('Data Entry')) {
                    $user->assignRole($dataEntryRole);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as roles are idempotent
    }
};
