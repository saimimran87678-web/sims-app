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
        // 1. Add section_id to enrollments table if it does not already exist
        if (!Schema::hasColumn('enrollments', 'section_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreignId('section_id')->nullable()->after('class_id')->constrained('sections')->nullOnDelete();
                $table->index('section_id', 'idx_enrollments_section_id');
            });
        }

        // 2. Migrate any populated section_id from students to active enrollments
        if (Schema::hasColumn('students', 'section_id')) {
            $studentsWithSection = DB::table('students')->whereNotNull('section_id')->get();
            foreach ($studentsWithSection as $student) {
                DB::table('enrollments')
                    ->where('student_id', $student->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['section_id' => $student->section_id]);
            }
        }

        // 3. Drop section_id from students
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::statement('DROP INDEX IF EXISTS idx_students_section_id;');
            DB::statement('
                CREATE TABLE "students_new" (
                    "id" integer primary key autoincrement not null,
                    "name" varchar not null,
                    "admission_no" varchar not null,
                    "father_name" varchar,
                    "phone" varchar,
                    "gender" varchar,
                    "dob" date,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "sports" varchar,
                    "extra_curriculars" varchar,
                    "transport_mode" varchar not null default (\'none\'),
                    "vehicle_number" varchar,
                    "profile_photo_path" varchar,
                    "admission_date" date,
                    "address" text,
                    "email" varchar,
                    "status" varchar not null default (\'active\'),
                    "father_cnic" varchar,
                    "guardian_cnic" varchar,
                    "guardian_relation" varchar,
                    "is_guardian_primary" tinyint(1) not null default \'0\',
                    "sibling_concession_status" varchar not null default \'none\',
                    "custom_concession_amount" numeric,
                    "b_form_number" varchar,
                    "original_admission_date" date,
                    "status_reason" varchar,
                    "deleted_at" datetime
                );
            ');
            DB::statement('
                INSERT INTO "students_new" ("id", "name", "admission_no", "father_name", "phone", "gender", "dob", "created_at", "updated_at", "sports", "extra_curriculars", "transport_mode", "vehicle_number", "profile_photo_path", "admission_date", "address", "email", "status", "father_cnic", "guardian_cnic", "guardian_relation", "is_guardian_primary", "sibling_concession_status", "custom_concession_amount", "b_form_number", "original_admission_date", "status_reason", "deleted_at")
                SELECT "id", "name", "admission_no", "father_name", "phone", "gender", "dob", "created_at", "updated_at", "sports", "extra_curriculars", "transport_mode", "vehicle_number", "profile_photo_path", "admission_date", "address", "email", "status", "father_cnic", "guardian_cnic", "guardian_relation", "is_guardian_primary", "sibling_concession_status", "custom_concession_amount", "b_form_number", "original_admission_date", "status_reason", "deleted_at" FROM "students";
            ');
            DB::statement('DROP TABLE "students";');
            DB::statement('ALTER TABLE "students_new" RENAME TO "students";');

            DB::statement('CREATE UNIQUE INDEX "students_admission_no_unique" on "students" ("admission_no");');
            DB::statement('CREATE INDEX "idx_students_name" ON "students" ("name");');
            DB::statement('CREATE INDEX "idx_students_status" ON "students" ("status");');
            DB::statement('CREATE INDEX "idx_students_father_cnic_status" on "students" ("father_cnic", "status");');
            DB::statement('CREATE INDEX "students_father_cnic_index" on "students" ("father_cnic");');
            DB::statement('CREATE INDEX "students_guardian_cnic_index" on "students" ("guardian_cnic");');
            DB::statement('CREATE INDEX "idx_students_b_form" on "students" ("b_form_number");');
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'section_id')) {
                    $table->dropForeign(['section_id']);
                    $table->dropColumn('section_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('students', 'section_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('enrollments', 'section_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }
    }
};
