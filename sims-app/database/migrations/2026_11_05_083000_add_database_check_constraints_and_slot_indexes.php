<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

            // 1. REBUILD STUDENTS TABLE WITH ENGINE CHECK CONSTRAINTS
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
                    "deleted_at" datetime,
                    "rfid_tag" varchar,
                    "card_issued_at" datetime,
                    "card_status" varchar not null default \'unassigned\',
                    "father_cnic_hash" varchar,
                    "guardian_cnic_hash" varchar,
                    "b_form_hash" varchar,
                    "phone_hash" varchar,
                    CHECK (dob IS NULL OR admission_date IS NULL OR dob < admission_date),
                    CHECK (status IN (\'active\', \'inactive\', \'withdrawn\', \'transferred\', \'graduated\', \'struck_off\', \'slc_issued\')),
                    CHECK (gender IS NULL OR lower(gender) IN (\'male\', \'female\', \'other\')),
                    CHECK (transport_mode IN (\'none\', \'school_bus\', \'private_van\', \'car\', \'bike\')),
                    CHECK (card_status IN (\'unassigned\', \'active\', \'blocked\', \'lost\'))
                );
            ');

            DB::statement('
                INSERT INTO "students_new" (
                    "id", "name", "admission_no", "father_name", "phone", "gender", "dob",
                    "created_at", "updated_at", "sports", "extra_curriculars", "transport_mode",
                    "vehicle_number", "profile_photo_path", "admission_date", "address", "email",
                    "status", "father_cnic", "guardian_cnic", "guardian_relation", "is_guardian_primary",
                    "sibling_concession_status", "custom_concession_amount", "b_form_number",
                    "original_admission_date", "status_reason", "deleted_at", "rfid_tag",
                    "card_issued_at", "card_status", "father_cnic_hash", "guardian_cnic_hash",
                    "b_form_hash", "phone_hash"
                )
                SELECT
                    "id", "name", "admission_no", "father_name", "phone", "gender", "dob",
                    "created_at", "updated_at", "sports", "extra_curriculars", "transport_mode",
                    "vehicle_number", "profile_photo_path", "admission_date", "address", "email",
                    "status", "father_cnic", "guardian_cnic", "guardian_relation", "is_guardian_primary",
                    "sibling_concession_status", "custom_concession_amount", "b_form_number",
                    "original_admission_date", "status_reason", "deleted_at", "rfid_tag",
                    "card_issued_at", "card_status", "father_cnic_hash", "guardian_cnic_hash",
                    "b_form_hash", "phone_hash"
                FROM "students";
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
            DB::statement('CREATE UNIQUE INDEX "students_rfid_tag_unique" on "students" ("rfid_tag");');
            DB::statement('CREATE INDEX "students_father_cnic_hash_index" on "students" ("father_cnic_hash");');
            DB::statement('CREATE INDEX "students_guardian_cnic_hash_index" on "students" ("guardian_cnic_hash");');
            DB::statement('CREATE INDEX "students_b_form_hash_index" on "students" ("b_form_hash");');
            DB::statement('CREATE INDEX "students_phone_hash_index" on "students" ("phone_hash");');

            // 2. REBUILD ENROLLMENTS TABLE WITH ENGINE CHECK CONSTRAINTS
            DB::statement('
                CREATE TABLE "enrollments_new" (
                    "id" integer primary key autoincrement not null,
                    "student_id" integer not null,
                    "class_id" integer not null,
                    "academic_session_id" integer not null,
                    "program_id" integer,
                    "roll_number" varchar,
                    "shift_type" varchar not null default (\'morning\'),
                    "status" varchar not null default (\'active\'),
                    "promoted_to_class_id" integer,
                    "promoted_at" datetime,
                    "remarks" text,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "deleted_at" datetime,
                    "section_id" integer,
                    foreign key("student_id") references students("id") on delete restrict on update no action,
                    foreign key("class_id") references classes("id") on delete restrict on update no action,
                    foreign key("academic_session_id") references academic_sessions("id") on delete restrict on update no action,
                    foreign key("program_id") references programs("id") on delete set null on update no action,
                    foreign key("promoted_to_class_id") references classes("id") on delete set null on update no action,
                    foreign key("section_id") references sections("id") on delete set null,
                    CHECK (shift_type IN (\'morning\', \'evening\', \'regular\')),
                    CHECK (status IN (\'active\', \'inactive\', \'withdrawn\', \'transferred\', \'graduated\', \'repeated\', \'promoted\', \'passed_out\', \'held_back\', \'completed\', \'slc_issued\'))
                );
            ');

            DB::statement('
                INSERT INTO "enrollments_new" (
                    "id", "student_id", "class_id", "academic_session_id", "program_id",
                    "roll_number", "shift_type", "status", "promoted_to_class_id",
                    "promoted_at", "remarks", "created_at", "updated_at", "deleted_at", "section_id"
                )
                SELECT
                    "id", "student_id", "class_id", "academic_session_id", "program_id",
                    "roll_number", "shift_type", "status", "promoted_to_class_id",
                    "promoted_at", "remarks", "created_at", "updated_at", "deleted_at", "section_id"
                FROM "enrollments";
            ');

            DB::statement('DROP TABLE "enrollments";');
            DB::statement('ALTER TABLE "enrollments_new" RENAME TO "enrollments";');

            DB::statement('CREATE UNIQUE INDEX "enrollments_student_id_academic_session_id_shift_type_unique" on "enrollments" ("student_id", "academic_session_id", "shift_type");');
            DB::statement('CREATE INDEX "idx_enr_session_class_shift" on "enrollments" ("academic_session_id", "class_id", "shift_type");');
            DB::statement('CREATE INDEX "idx_enr_session_student_status" on "enrollments" ("academic_session_id", "student_id", "status");');
            DB::statement('CREATE INDEX "idx_enrollments_session_class_shift" on "enrollments" ("academic_session_id", "class_id", "shift_type", "status");');
            DB::statement('CREATE INDEX "idx_enrollments_session_status" on "enrollments" ("academic_session_id", "status");');
            DB::statement('CREATE INDEX "idx_enrollments_student_id" on "enrollments" ("student_id");');
            DB::statement('CREATE INDEX "idx_enrollments_section_id" on "enrollments" ("section_id");');

            // 3. REBUILD FEE_RECORDS TABLE WITH FINANCIAL INTEGRITY CHECK CONSTRAINTS
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
                    foreign key("academic_session_id") references academic_sessions("id") on delete cascade on update no action,
                    foreign key("class_id") references classes("id") on delete cascade on update no action,
                    foreign key("student_id") references "students"("id") on delete restrict,
                    CHECK (total_amount >= 0),
                    CHECK (paid_amount >= 0),
                    CHECK (round(balance, 2) = round(total_amount - paid_amount, 2)),
                    CHECK (status IN (\'unpaid\', \'partial\', \'paid\', \'void\'))
                );
            ');

            DB::statement('
                INSERT INTO "fee_records_new" (
                    "id", "student_id", "class_id", "academic_session_id", "period", "cycle",
                    "total_amount", "paid_amount", "balance", "status", "due_date", "paid_date",
                    "created_at", "updated_at", "is_custom", "access_token", "access_token_expires_at"
                )
                SELECT
                    "id", "student_id", "class_id", "academic_session_id", "period", "cycle",
                    "total_amount", "paid_amount", "balance", "status", "due_date", "paid_date",
                    "created_at", "updated_at", "is_custom", "access_token", "access_token_expires_at"
                FROM "fee_records";
            ');

            DB::statement('DROP TABLE "fee_records";');
            DB::statement('ALTER TABLE "fee_records_new" RENAME TO "fee_records";');

            DB::statement('CREATE UNIQUE INDEX "fee_record_unique" on "fee_records" ("student_id", "period", "cycle", "academic_session_id");');
            DB::statement('CREATE UNIQUE INDEX "fee_records_access_token_unique" on "fee_records" ("access_token");');
            DB::statement('CREATE INDEX "idx_fee_records_class_session_period" on "fee_records" ("class_id", "academic_session_id", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_class_status" on "fee_records" ("class_id", "status");');
            DB::statement('CREATE INDEX "idx_fee_records_due_date" on "fee_records" ("due_date");');
            DB::statement('CREATE INDEX "idx_fee_records_session_bal_period" on "fee_records" ("academic_session_id", "balance", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_session_status" on "fee_records" ("academic_session_id", "status");');
            DB::statement('CREATE INDEX "idx_fee_records_student_session_period" on "fee_records" ("student_id", "academic_session_id", "period");');
            DB::statement('CREATE INDEX "idx_fee_records_student_status" on "fee_records" ("student_id", "status");');
            DB::statement('CREATE INDEX "idx_fr_session_status" on "fee_records" ("academic_session_id", "status");');
            DB::statement('CREATE INDEX "idx_fr_session_student_period" on "fee_records" ("academic_session_id", "student_id", "period");');
            DB::statement('CREATE INDEX "fee_records_access_token_expires_at_index" on "fee_records" ("access_token_expires_at");');

            // 4. TIMETABLE SLOT UNIQUENESS CONSTRAINTS
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_timetables_slot_single"
                ON "timetables" ("class_id", "day", "period_no")
                WHERE is_divided = 0 OR is_divided IS NULL;
            ');

            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS "idx_timetables_slot_divided"
                ON "timetables" ("class_id", "day", "period_no", "subject_id")
                WHERE is_divided = 1;
            ');

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
            DB::statement('DROP INDEX IF EXISTS "idx_timetables_slot_single";');
            DB::statement('DROP INDEX IF EXISTS "idx_timetables_slot_divided";');
        }
    }
};
