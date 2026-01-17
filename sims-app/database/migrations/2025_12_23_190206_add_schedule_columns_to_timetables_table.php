<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            // Add period reference instead of time-based
            $table->integer('period_no')->after('day')->nullable();
            
            // Teacher assignment
            $table->foreignId('teacher_id')->nullable()->after('subject_id')->constrained('users')->nullOnDelete();
            
            // Room (defaults to class name in app logic)
            $table->string('room')->nullable()->after('teacher_id');
            
            // Divided class support (2 teachers same period)
            $table->boolean('is_divided')->default(false)->after('room');
            
            // Substitute support
            $table->boolean('is_substitute')->default(false)->after('is_divided');
            $table->date('substitute_date')->nullable()->after('is_substitute');
        });
    }

    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['period_no', 'teacher_id', 'room', 'is_divided', 'is_substitute', 'substitute_date']);
        });
    }
};
