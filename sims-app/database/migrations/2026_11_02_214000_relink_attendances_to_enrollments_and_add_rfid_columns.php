<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            // 1. Create temporary new table
            DB::statement('
                CREATE TABLE "attendances_new" (
                    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "enrollment_id" INTEGER NOT NULL,
                    "student_id" INTEGER NOT NULL,
                    "academic_session_id" INTEGER NOT NULL,
                    "date" DATE NOT NULL,
                    "status" VARCHAR NOT NULL DEFAULT "P",
                    "source" VARCHAR(20) NOT NULL DEFAULT "manual",
                    "check_in_at" DATETIME NULL,
                    "check_out_at" DATETIME NULL,
                    "device_id" VARCHAR(50) NULL,
                    "marked_by_user_id" INTEGER NULL,
                    "marked_at" DATETIME NULL,
                    "created_at" DATETIME NULL,
                    "updated_at" DATETIME NULL,
                    FOREIGN KEY ("enrollment_id") REFERENCES "enrollments" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("student_id") REFERENCES "students" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("academic_session_id") REFERENCES "academic_sessions" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("marked_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL
                );
            ');

            // 2. Backfill existing attendance records
            $existing = DB::table('attendances')->get();
            foreach ($existing as $att) {
                // Find matching enrollment
                $enrollment = DB::table('enrollments')
                    ->where('student_id', $att->student_id)
                    ->where('academic_session_id', $att->academic_session_id)
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderBy('id', 'desc')
                    ->first();

                $enrollmentId = $enrollment ? $enrollment->id : null;

                if (!$enrollmentId) {
                    // Fallback: any enrollment for this student
                    $enrollment = DB::table('enrollments')
                        ->where('student_id', $att->student_id)
                        ->orderBy('id', 'desc')
                        ->first();
                    $enrollmentId = $enrollment ? $enrollment->id : null;
                }

                if ($enrollmentId) {
                    DB::table('attendances_new')->insert([
                        'id' => $att->id,
                        'enrollment_id' => $enrollmentId,
                        'student_id' => $att->student_id,
                        'academic_session_id' => $att->academic_session_id ?? ($enrollment->academic_session_id ?? 1),
                        'date' => $att->date,
                        'status' => $att->status ?? 'P',
                        'source' => 'manual',
                        'check_in_at' => null,
                        'check_out_at' => null,
                        'device_id' => null,
                        'marked_by_user_id' => null,
                        'marked_at' => $att->created_at,
                        'created_at' => $att->created_at,
                        'updated_at' => $att->updated_at,
                    ]);
                }
            }

            // 3. Drop old table and rename
            DB::statement('DROP TABLE "attendances";');
            DB::statement('ALTER TABLE "attendances_new" RENAME TO "attendances";');

            // 4. Create indexes
            DB::statement('CREATE UNIQUE INDEX "attendances_enrollment_id_date_unique" ON "attendances" ("enrollment_id", "date");');
            DB::statement('CREATE INDEX "idx_att_session_student_date" ON "attendances" ("academic_session_id", "student_id", "date");');
            DB::statement('CREATE INDEX "idx_attendances_date" ON "attendances" ("date");');
            DB::statement('CREATE INDEX "idx_attendances_session_date_status" ON "attendances" ("academic_session_id", "date", "status");');
            DB::statement('CREATE INDEX "idx_attendances_source" ON "attendances" ("source");');
            DB::statement('CREATE INDEX "idx_attendances_check_in_at" ON "attendances" ("check_in_at");');

            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('enrollment_id')->after('id')->constrained('enrollments')->cascadeOnDelete();
                $table->string('source', 20)->default('manual')->after('status');
                $table->timestamp('check_in_at')->nullable()->after('source');
                $table->timestamp('check_out_at')->nullable()->after('check_in_at');
                $table->string('device_id', 50)->nullable()->after('check_out_at');
                $table->foreignId('marked_by_user_id')->nullable()->after('device_id')->constrained('users')->nullOnDelete();
                $table->timestamp('marked_at')->nullable()->after('marked_by_user_id');

                $table->dropUnique(['student_id', 'date']);
                $table->unique(['enrollment_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');
            DB::statement('
                CREATE TABLE "attendances_old" (
                    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "student_id" INTEGER NOT NULL,
                    "academic_session_id" INTEGER NULL,
                    "date" DATE NOT NULL,
                    "status" VARCHAR NOT NULL DEFAULT "P",
                    "created_at" DATETIME NULL,
                    "updated_at" DATETIME NULL,
                    FOREIGN KEY ("academic_session_id") REFERENCES "academic_sessions" ("id") ON DELETE SET NULL,
                    FOREIGN KEY ("student_id") REFERENCES "students" ("id") ON DELETE RESTRICT
                );
            ');

            DB::statement('
                INSERT INTO "attendances_old" ("id", "student_id", "academic_session_id", "date", "status", "created_at", "updated_at")
                SELECT "id", "student_id", "academic_session_id", "date", "status", "created_at", "updated_at" FROM "attendances";
            ');

            DB::statement('DROP TABLE "attendances";');
            DB::statement('ALTER TABLE "attendances_old" RENAME TO "attendances";');
            DB::statement('CREATE UNIQUE INDEX "attendances_student_id_date_unique" ON "attendances" ("student_id", "date");');
            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique(['enrollment_id', 'date']);
                $table->unique(['student_id', 'date']);
                $table->dropForeign(['enrollment_id']);
                $table->dropForeign(['marked_by_user_id']);
                $table->dropColumn(['enrollment_id', 'source', 'check_in_at', 'check_out_at', 'device_id', 'marked_by_user_id', 'marked_at']);
            });
        }
    }
};
