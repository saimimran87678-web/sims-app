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
        // Skip for SQLite as we handle this in a later rebuild migration
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Add 'short_leave' to ENUM
        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'leave', 'official_duty', 'short_leave') DEFAULT 'present'");
        
        // Migrate data 'late' -> 'short_leave'
        DB::table('teacher_attendances')->where('status', 'late')->update(['status' => 'short_leave']);

        // Remove 'late' from ENUM (Optional, but cleaner)
        // Note: Removing an enum value that is not in use is safe in MySQL
        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'short_leave', 'leave', 'official_duty') DEFAULT 'present'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert ENUM to include 'late'
        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'leave', 'official_duty', 'short_leave') DEFAULT 'present'");

        // Revert data 'short_leave' -> 'late'
        DB::table('teacher_attendances')->where('status', 'short_leave')->update(['status' => 'late']);

        // Remove 'short_leave' from ENUM
        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'leave', 'official_duty') DEFAULT 'present'");
    }
};
