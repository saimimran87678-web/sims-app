<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('roll_number')->nullable();
            $table->enum('shift_type', ['morning', 'evening'])->default('morning');
            $table->enum('status', ['active', 'promoted', 'held_back', 'passed_out', 'transferred'])->default('active');
            $table->foreignId('promoted_to_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->timestamp('promoted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'academic_session_id', 'shift_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
