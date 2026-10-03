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
        Schema::create('daily_class_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->string('shift_type')->default('regular'); // 'regular', 'morning', 'evening'
            $table->date('date');
            $table->foreignId('source_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('target_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('source_timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('target_timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['date', 'source_timetable_id'], 'unique_daily_merge_source');
            $table->index(['academic_session_id', 'shift_type', 'date'], 'idx_daily_merge_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_class_merges');
    }
};
