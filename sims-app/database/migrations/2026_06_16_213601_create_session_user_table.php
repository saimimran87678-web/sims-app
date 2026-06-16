<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'academic_session_id']);
        });

        // Migrate existing global is_active status to session_user for currently active sessions
        $activeSessionId = \App\Models\AcademicSession::where('is_active', true)->value('id');
        if ($activeSessionId) {
            $users = \Illuminate\Support\Facades\DB::table('users')->get();
            foreach ($users as $user) {
                // If they have the column, migrate it
                $isActive = property_exists($user, 'is_active') ? $user->is_active : true;
                
                \Illuminate\Support\Facades\DB::table('session_user')->insert([
                    'user_id' => $user->id,
                    'academic_session_id' => $activeSessionId,
                    'is_active' => $isActive,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Try to drop global is_active from users
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        Schema::dropIfExists('session_user');
    }
};
