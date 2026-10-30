<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_leaving_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->restrictOnDelete();
            $table->string('certificate_number', 50)->unique();
            $table->date('leaving_date');
            $table->string('class_at_leaving', 50);
            $table->string('reason_for_leaving', 100);
            $table->string('conduct', 50)->default('Good');
            $table->text('remarks')->nullable();
            $table->boolean('fee_dues_cleared')->default(true);
            $table->string('concession_reference', 100)->nullable();
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'academic_session_id'], 'idx_slc_student_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_leaving_certificates');
    }
};
