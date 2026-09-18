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
            // Unique index for class view (including merged_class_id)
            $table->unique([
                'schedule_template_id',
                'day',
                'period_no',
                'class_id',
                'merged_class_id',
            ], 'timetable_class_unique');

            // Unique index for teacher view
            $table->unique([
                'schedule_template_id',
                'day',
                'period_no',
                'teacher_id',
            ], 'timetable_teacher_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropUnique('timetable_class_unique');
            $table->dropUnique('timetable_teacher_unique');
        });
    }
};
