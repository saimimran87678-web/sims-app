<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_user', function (Blueprint $table) {
            if (!Schema::hasColumn('session_user', 'class_id')) {
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            }
            if (!Schema::hasColumn('session_user', 'class_subject')) {
                $table->string('class_subject')->nullable();
            }
        });

        // Migrate existing data for active sessions
        $activeSessionId = \App\Models\AcademicSession::where('is_active', true)->value('id');
        if ($activeSessionId && Schema::hasColumn('users', 'class_id')) {
            $users = \Illuminate\Support\Facades\DB::table('users')->whereNotNull('class_id')->get();
            foreach ($users as $user) {
                \Illuminate\Support\Facades\DB::table('session_user')
                    ->where('user_id', $user->id)
                    ->where('academic_session_id', $activeSessionId)
                    ->update([
                        'class_id' => $user->class_id,
                        'class_subject' => property_exists($user, 'class_subject') ? $user->class_subject : null,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('session_user', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                try {
                    $table->dropForeign(['class_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist
                }
            }
            
            $dropColumns = [];
            if (Schema::hasColumn('session_user', 'class_id')) {
                $dropColumns[] = 'class_id';
            }
            if (Schema::hasColumn('session_user', 'class_subject')) {
                $dropColumns[] = 'class_subject';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
