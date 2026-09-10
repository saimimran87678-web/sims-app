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
            $table->index(['schedule_template_id', 'day', 'period_no'], 'timetables_template_day_period_idx');
            $table->index('teacher_id', 'timetables_teacher_id_idx');
            $table->index('class_id', 'timetables_class_id_idx');
            $table->index('merged_class_id', 'timetables_merged_class_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropIndex('timetables_template_day_period_idx');
            $table->dropIndex('timetables_teacher_id_idx');
            $table->dropIndex('timetables_class_id_idx');
            $table->dropIndex('timetables_merged_class_id_idx');
        });
    }
};
