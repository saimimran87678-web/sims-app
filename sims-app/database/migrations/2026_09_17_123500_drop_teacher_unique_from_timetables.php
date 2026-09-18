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
        Schema::table('timetables', function (Blueprint $table) {
            // Drop unique constraint on teacher to allow assigning a teacher
            // to multiple classes in the same period (e.g. combined/divided classes or multi-class periods).
            if (Schema::hasIndex('timetables', 'timetable_teacher_unique')) {
                $table->dropUnique('timetable_teacher_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            if (!Schema::hasIndex('timetables', 'timetable_teacher_unique')) {
                $table->unique([
                    'schedule_template_id',
                    'day',
                    'period_no',
                    'teacher_id',
                ], 'timetable_teacher_unique');
            }
        });
    }
};
