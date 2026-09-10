<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create new table with updated schema (including 'official_duty' and 'short_leave')
        Schema::create('teacher_attendances_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            // Full allowed list
            $table->enum('status', ['present', 'absent', 'leave', 'official_duty', 'short_leave'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // 2. Copy Data and Transform 'late' -> 'short_leave'
        // We use raw SQL to ensure it works across drivers, but specific logic for the enum conversion
        DB::statement("
            INSERT INTO teacher_attendances_new (id, user_id, date, status, remarks, created_at, updated_at)
            SELECT id, user_id, date, 
            CASE 
                WHEN status = 'late' THEN 'short_leave' 
                -- If status is somehow already 'official_duty' but failed constraint check (unlikely but possible if constraint was missing), keep it.
                ELSE status 
            END, 
            remarks, created_at, updated_at 
            FROM teacher_attendances
        ");

        // 3. Drop old table
        Schema::drop('teacher_attendances');

        // 4. Rename new table to original name
        Schema::rename('teacher_attendances_new', 'teacher_attendances');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old schema (approximated, since we can't easily undo the data transformation perfectly if 'short_leave' was legitimate)
        
        Schema::create('teacher_attendances_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        // Copy back, converting 'short_leave' -> 'late' (lossy logic but best effort)
        DB::statement("
            INSERT INTO teacher_attendances_old (id, user_id, date, status, remarks, created_at, updated_at)
            SELECT id, user_id, date, 
            CASE 
                WHEN status = 'short_leave' THEN 'late' 
                WHEN status = 'official_duty' THEN 'present' -- fallback as it didn't exist
                ELSE status 
            END, 
            remarks, created_at, updated_at 
            FROM teacher_attendances
        ");

        Schema::drop('teacher_attendances');
        Schema::rename('teacher_attendances_old', 'teacher_attendances');
    }
};
