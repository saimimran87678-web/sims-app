<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only run this for SQLite as MySQL was likely handled by the previous migration
        // However, to be safe and consistent, we can run this for all drivers if we really wanted to,
        // but 'enum' works natively in MySQL. 
        // We will target SQLite specifically OR use the Laravel schema builder to redefine the column which might handle it.
        
        // Strategy: Create new table, copy data, swap.
        
        $driver = DB::getDriverName();
        if ($driver !== 'sqlite') {
            return;
        }

        // 1. Create temp table with NEW schema
        Schema::create('teacher_attendances_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            // Redefine enum with new value
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'official_duty'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // 2. Copy data
        // We can use raw SQL to copy. 
        // Note: any existing 'status' values must match the new allowed values. 
        // Since we are adding a value, old values are fine.
        DB::statement('INSERT INTO teacher_attendances_temp (id, user_id, date, status, remarks, created_at, updated_at) SELECT id, user_id, date, status, remarks, created_at, updated_at FROM teacher_attendances');

        // 3. Drop old table
        Schema::drop('teacher_attendances');

        // 4. Rename temp to real
        Schema::rename('teacher_attendances_temp', 'teacher_attendances');
    }

    public function down(): void
    {
        // Revert is hard because 'official_duty' data might exist.
        // We will just skip revert logic for this fix-forward migration in dev environment.
    }
};
