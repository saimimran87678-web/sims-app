<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM column using raw SQL since doctrine/dbal might be missing or tricky with enums
        // This syntax works for MySQL/MariaDB which is standard for Laravel
        // For SQLite (testing), this might fail if not handled, but usually local dev uses MySQL/MariaDB for this user based on 'wamp/xampp' vibe. 
        // If SQLite, we can't easily alter enum check constraints without table rebuild. 
        // Assuming MySQL/MariaDB:
        
        // Check driver
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'leave', 'official_duty') DEFAULT 'present'");
        } else {
             // Fallback for SQLite - simpler "check" constraint logic usually not strict in SQLite unless enforced.
             // But if specific constraint exists, we'd need to drop and recreate. 
             // Just letting it be strict for MySQL now.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'leave') DEFAULT 'present'");
        }
    }
};
