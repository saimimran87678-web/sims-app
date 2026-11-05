<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            // 1. INDEX HYGIENE: Drop redundant indexes identified in audit
            DB::statement('DROP INDEX IF EXISTS "students_father_cnic_index";');
            DB::statement('DROP INDEX IF EXISTS "idx_fr_session_status";');

            // 2. NULL-SAFE UNIQUE INDEXES FOR SETTINGS
            DB::statement('DROP INDEX IF EXISTS "settings_new_key_academic_session_id_unique";');
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_settings_session_unique" 
                ON "settings" ("key", "academic_session_id") 
                WHERE "academic_session_id" IS NOT NULL;
            ');
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_settings_global_unique" 
                ON "settings" ("key") 
                WHERE "academic_session_id" IS NULL;
            ');

            // 3. NULL-SAFE UNIQUE INDEXES FOR TEACHER ATTENDANCES
            DB::statement('DROP INDEX IF EXISTS "teacher_date_session_shift_unique";');
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_teacher_att_session_unique" 
                ON "teacher_attendances" ("teacher_id", "date", "academic_session_id", "shift_type") 
                WHERE "academic_session_id" IS NOT NULL;
            ');
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_teacher_att_global_unique" 
                ON "teacher_attendances" ("teacher_id", "date", "shift_type") 
                WHERE "academic_session_id" IS NULL;
            ');

            // 4. CRYPTOGRAPHIC TAMPER-EVIDENT AUDIT CHAIN
            if (!Schema::hasColumn('activity_logs', 'previous_hash')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->string('previous_hash', 64)->nullable()->after('shift_type');
                    $table->string('record_hash', 64)->nullable()->after('previous_hash');
                    $table->index('record_hash', 'idx_activity_logs_record_hash');
                });
            }

            // Backfill existing activity log records with cryptographic chain hashes
            $logs = DB::table('activity_logs')->orderBy('id', 'asc')->get();
            $previousHash = str_repeat('0', 64);

            foreach ($logs as $log) {
                $recordHash = ActivityLog::computeRecordHash(
                    $log->user_id,
                    $log->action,
                    $log->module,
                    $log->model_type,
                    $log->model_id,
                    $log->description,
                    $log->created_at,
                    $previousHash
                );

                DB::table('activity_logs')->where('id', $log->id)->update([
                    'previous_hash' => $previousHash,
                    'record_hash' => $recordHash,
                ]);

                $previousHash = $recordHash;
            }

            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS "idx_settings_session_unique";');
            DB::statement('DROP INDEX IF EXISTS "idx_settings_global_unique";');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "settings_new_key_academic_session_id_unique" on "settings" ("key", "academic_session_id");');

            DB::statement('DROP INDEX IF EXISTS "idx_teacher_att_session_unique";');
            DB::statement('DROP INDEX IF EXISTS "idx_teacher_att_global_unique";');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "teacher_date_session_shift_unique" on "teacher_attendances" ("teacher_id", "date", "academic_session_id", "shift_type");');

            DB::statement('CREATE INDEX IF NOT EXISTS "students_father_cnic_index" on "students" ("father_cnic");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_fr_session_status" on "fee_records" ("academic_session_id", "status");');

            if (Schema::hasColumn('activity_logs', 'previous_hash')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_activity_logs_record_hash');
                    $table->dropColumn(['previous_hash', 'record_hash']);
                });
            }
        }
    }
};
