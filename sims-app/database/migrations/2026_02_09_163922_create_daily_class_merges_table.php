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
            $table->date('date');
            $table->foreignId('source_timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->foreignId('target_timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['date', 'source_timetable_id'], 'unique_daily_merge_source');
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
