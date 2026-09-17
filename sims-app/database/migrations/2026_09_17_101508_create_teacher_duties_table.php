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
        Schema::create('teacher_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_template_id')->constrained('schedule_templates')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('day');
            $table->integer('period_no');
            $table->string('duty_name')->default('Management');
            $table->timestamps();

            $table->unique(['schedule_template_id', 'teacher_id', 'day', 'period_no'], 'teacher_duty_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_duties');
    }
};
