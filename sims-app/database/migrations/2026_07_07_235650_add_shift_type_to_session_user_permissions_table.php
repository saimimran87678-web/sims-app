<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_user_permissions', function (Blueprint $table) {
            $table->string('shift_type')->default('regular');
            $table->dropUnique('session_user_perm_unique');
            $table->unique(['user_id', 'academic_session_id', 'permission_name', 'shift_type'], 'session_user_perm_shift_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_user_permissions', function (Blueprint $table) {
            $table->dropUnique('session_user_perm_shift_unique');
            $table->unique(['user_id', 'academic_session_id', 'permission_name'], 'session_user_perm_unique');
            $table->dropColumn('shift_type');
        });
    }
};
