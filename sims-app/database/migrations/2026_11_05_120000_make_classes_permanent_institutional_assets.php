<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            // 1. Recreate classes table: permanent institutional classrooms
            DB::statement('DROP TABLE IF EXISTS "classes_new";');
            DB::statement('
                CREATE TABLE "classes_new" (
                    "id" integer primary key autoincrement not null,
                    "name" varchar not null,
                    "numeric_value" integer not null default 0,
                    "shift_type" varchar not null default \'morning\',
                    "is_active" tinyint(1) not null default 1,
                    "academic_session_id" integer null,
                    "next_class_id" integer null,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "deleted_at" datetime,
                    foreign key("next_class_id") references "classes"("id") on delete set null,
                    foreign key("academic_session_id") references "academic_sessions"("id") on delete set null
                );
            ');

            // Copy existing classes into classes_new
            DB::statement('
                INSERT INTO "classes_new" (
                    "id", "name", "numeric_value", "shift_type", "is_active", 
                    "academic_session_id", "next_class_id", "created_at", "updated_at", "deleted_at"
                )
                SELECT 
                    "id", "name", "numeric_value", "shift_type", 1, 
                    "academic_session_id", "next_class_id", "created_at", "updated_at", "deleted_at"
                FROM "classes";
            ');

            DB::statement('DROP TABLE "classes";');
            DB::statement('ALTER TABLE "classes_new" RENAME TO "classes";');

            // Create indexes on classes
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "idx_classes_name_shift_session" ON "classes" ("name", "shift_type", "academic_session_id") WHERE "deleted_at" IS NULL;');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_classes_is_active" ON "classes" ("is_active");');
            DB::statement('CREATE INDEX IF NOT EXISTS "idx_classes_numeric_value" ON "classes" ("numeric_value");');

            // 2. Recreate fee_records table to enforce ON DELETE RESTRICT on class_id
            DB::statement('DROP TABLE IF EXISTS "fee_records_new";');
            DB::statement('
                CREATE TABLE "fee_records_new" (
                    "id" integer primary key autoincrement not null,
                    "student_id" integer not null,
                    "class_id" integer not null,
                    "academic_session_id" integer not null,
                    "period" varchar not null,
                    "cycle" varchar not null,
                    "total_amount" numeric not null,
                    "paid_amount" numeric not null default (\'0\'),
                    "balance" numeric not null,
                    "status" varchar not null default (\'unpaid\'),
                    "due_date" date not null,
                    "paid_date" date,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "is_custom" tinyint(1) not null default (\'0\'),
                    "access_token" varchar,
                    "access_token_expires_at" datetime,
                    foreign key("academic_session_id") references "academic_sessions"("id") on delete cascade on update no action,
                    foreign key("class_id") references "classes"("id") on delete restrict on update no action,
                    foreign key("student_id") references "students"("id") on delete restrict,
                    CHECK (total_amount >= 0),
                    CHECK (paid_amount >= 0),
                    CHECK (round(balance, 2) = round(total_amount - paid_amount, 2)),
                    CHECK (status IN (\'unpaid\', \'partial\', \'paid\', \'void\'))
                );
            ');
            DB::statement('INSERT INTO "fee_records_new" SELECT * FROM "fee_records";');
            DB::statement('DROP TABLE "fee_records";');
            DB::statement('ALTER TABLE "fee_records_new" RENAME TO "fee_records";');

            // Restore fee_records indexes
            DB::statement('CREATE UNIQUE INDEX "fee_record_unique" on "fee_records" ("student_id", "period", "cycle", "academic_session_id");');
            DB::statement('CREATE UNIQUE INDEX "fee_records_access_token_unique" on "fee_records" ("access_token");');
            DB::statement('CREATE INDEX "idx_fee_records_class_session_period" on "fee_records" ("class_id", "academic_session_id", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_class_status" on "fee_records" ("class_id", "status");');
            DB::statement('CREATE INDEX "idx_fee_records_due_date" on "fee_records" ("due_date");');
            DB::statement('CREATE INDEX "idx_fee_records_session_bal_period" on "fee_records" ("academic_session_id", "balance", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_session_status" on "fee_records" ("academic_session_id", "status");');
            DB::statement('CREATE INDEX "idx_fee_records_student_session_period" on "fee_records" ("student_id", "academic_session_id", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_student_status" on "fee_records" ("student_id", "status");');
            DB::statement('CREATE INDEX "idx_fr_session_student_period" on "fee_records" ("academic_session_id", "student_id", "period");');
            DB::statement('CREATE INDEX "fee_records_access_token_expires_at_index" on "fee_records" ("access_token_expires_at");');

            // 3. Recreate marks_configs to enforce ON DELETE RESTRICT on class_id
            DB::statement('DROP TABLE IF EXISTS "marks_configs_new";');
            DB::statement('
                CREATE TABLE "marks_configs_new" (
                    "id" integer primary key autoincrement not null,
                    "exam_id" integer not null,
                    "class_id" integer not null,
                    "subject" varchar not null,
                    "total_marks" integer not null default (\'100\'),
                    "created_at" datetime,
                    "updated_at" datetime,
                    "passing_marks" integer not null default (\'33\'),
                    "academic_session_id" integer,
                    "subject_id" integer,
                    "program_id" integer,
                    "theory_marks" integer,
                    "practical_marks" integer,
                    "is_board_exam" tinyint(1) not null default \'0\',
                    "effective_from" date,
                    foreign key("class_id") references "classes"("id") on delete restrict on update no action,
                    foreign key("exam_id") references "exams"("id") on delete cascade on update no action,
                    foreign key("academic_session_id") references "academic_sessions"("id") on delete set null,
                    foreign key("subject_id") references "subjects"("id") on delete set null,
                    foreign key("program_id") references "programs"("id") on delete set null
                );
            ');
            DB::statement('INSERT INTO "marks_configs_new" SELECT * FROM "marks_configs";');
            DB::statement('DROP TABLE "marks_configs";');
            DB::statement('ALTER TABLE "marks_configs_new" RENAME TO "marks_configs";');
            DB::statement('CREATE UNIQUE INDEX "marks_configs_exam_id_class_id_subject_unique" on "marks_configs" ("exam_id", "class_id", "subject");');
            DB::statement('CREATE INDEX "idx_marks_configs_exam_class" ON "marks_configs" ("exam_id", "class_id");');
            DB::statement('CREATE INDEX "idx_marks_configs_session_subject" on "marks_configs" ("academic_session_id", "subject_id");');

            // 4. Recreate timetables to enforce ON DELETE RESTRICT on class_id
            DB::statement('DROP TABLE IF EXISTS "timetables_new";');
            DB::statement('
                CREATE TABLE "timetables_new" (
                    "id" integer primary key autoincrement not null,
                    "class_id" integer not null,
                    "subject_id" integer not null,
                    "day" varchar not null,
                    "start_time" time,
                    "end_time" time,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "period_no" integer,
                    "teacher_id" integer,
                    "room" varchar,
                    "is_divided" tinyint(1) not null default (\'0\'),
                    "is_merged" tinyint(1) not null default \'0\',
                    "merge_group_id" varchar,
                    foreign key("teacher_id") references "users"("id") on delete set null on update no action,
                    foreign key("class_id") references "classes"("id") on delete restrict on update no action,
                    foreign key("subject_id") references "subjects"("id") on delete cascade on update no action
                );
            ');
            DB::statement('INSERT INTO "timetables_new" SELECT * FROM "timetables";');
            DB::statement('DROP TABLE "timetables";');
            DB::statement('ALTER TABLE "timetables_new" RENAME TO "timetables";');
            DB::statement('CREATE INDEX "idx_timetables_class_day" ON "timetables" ("class_id", "day");');
            DB::statement('CREATE INDEX "idx_timetables_subject_id" ON "timetables" ("subject_id");');
            DB::statement('CREATE INDEX "idx_timetables_teacher_day_period" ON "timetables" ("teacher_id", "day", "period_no");');
            DB::statement('CREATE INDEX "idx_timetables_class_day_period" ON "timetables" ("class_id", "day", "period_no");');
            DB::statement('CREATE INDEX "timetables_merge_group_id_index" on "timetables" ("merge_group_id");');
            DB::statement('CREATE UNIQUE INDEX "idx_timetables_slot_single" ON "timetables" ("class_id", "day", "period_no") WHERE is_divided = 0 OR is_divided IS NULL;');
            DB::statement('CREATE UNIQUE INDEX "idx_timetables_slot_divided" ON "timetables" ("class_id", "day", "period_no", "subject_id") WHERE is_divided = 1;');

            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};
