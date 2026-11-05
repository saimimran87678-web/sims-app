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
        // 1. Delete any lingering temporary substitution rows in timetables
        if (Schema::hasColumn('timetables', 'is_substitute')) {
            DB::table('timetables')->where('is_substitute', true)->delete();
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
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
                    foreign key("teacher_id") references users("id") on delete set null on update no action,
                    foreign key("class_id") references classes("id") on delete cascade on update no action,
                    foreign key("subject_id") references subjects("id") on delete cascade on update no action
                );
            ');
            DB::statement('
                INSERT INTO "timetables_new" ("id", "class_id", "subject_id", "day", "start_time", "end_time", "created_at", "updated_at", "period_no", "teacher_id", "room", "is_divided", "is_merged", "merge_group_id")
                SELECT "id", "class_id", "subject_id", "day", "start_time", "end_time", "created_at", "updated_at", "period_no", "teacher_id", "room", "is_divided", "is_merged", "merge_group_id" FROM "timetables";
            ');
            DB::statement('DROP TABLE "timetables";');
            DB::statement('ALTER TABLE "timetables_new" RENAME TO "timetables";');

            DB::statement('CREATE INDEX "idx_timetables_class_day" ON "timetables" ("class_id", "day");');
            DB::statement('CREATE INDEX "idx_timetables_subject_id" ON "timetables" ("subject_id");');
            DB::statement('CREATE INDEX "idx_timetables_teacher_day_period" ON "timetables" ("teacher_id", "day", "period_no");');
            DB::statement('CREATE INDEX "idx_timetables_class_day_period" ON "timetables" ("class_id", "day", "period_no");');
            DB::statement('CREATE INDEX "timetables_merge_group_id_index" on "timetables" ("merge_group_id");');
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            Schema::table('timetables', function (Blueprint $table) {
                if (Schema::hasColumn('timetables', 'is_substitute')) {
                    $table->dropColumn('is_substitute');
                }
                if (Schema::hasColumn('timetables', 'substitute_date')) {
                    $table->dropColumn('substitute_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            if (!Schema::hasColumn('timetables', 'is_substitute')) {
                $table->boolean('is_substitute')->default(false);
            }
            if (!Schema::hasColumn('timetables', 'substitute_date')) {
                $table->date('substitute_date')->nullable();
            }
        });
    }
};
