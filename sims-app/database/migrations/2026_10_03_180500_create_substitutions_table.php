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
        if (!Schema::hasTable('substitutions')) {
            Schema::create('substitutions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->enum('shift_type', ['regular', 'morning', 'evening'])->default('regular');
                $table->date('date');
                $table->unsignedTinyInteger('period_no');

                // Academic Context
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('timetable_id')->nullable()->constrained('timetables')->nullOnDelete();

                // Staff Context
                $table->foreignId('absent_teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('substitute_teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('teacher_attendance_id')->nullable()->constrained('teacher_attendances')->nullOnDelete();

                $table->enum('status', ['assigned', 'completed', 'cancelled'])->default('assigned');
                $table->string('remarks', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Prevent double substitution on the same class and period on the same date
                $table->unique(['date', 'class_id', 'period_no'], 'uk_substitution_date_class_period');
                $table->index(['academic_session_id', 'shift_type', 'date'], 'idx_sub_session_shift_date');
                $table->index(['substitute_teacher_id', 'date'], 'idx_sub_teacher_date');
                $table->index(['absent_teacher_id', 'date'], 'idx_sub_absent_teacher_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substitutions');
    }
};
