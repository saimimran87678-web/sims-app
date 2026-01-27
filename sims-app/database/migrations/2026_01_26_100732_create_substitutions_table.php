<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_attendance_id')->constrained('teacher_attendances')->onDelete('cascade');
            $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date'); // Redundant but good for quick queries
            $table->enum('status', ['assigned', 'pending'])->default('assigned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutions');
    }
};
