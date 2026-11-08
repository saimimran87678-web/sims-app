<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Standardize legacy 'passed_out' enrollment status to 'graduated'
        DB::table('enrollments')
            ->where('status', 'passed_out')
            ->update([
                'status' => 'graduated',
                'updated_at' => now(),
            ]);

        // 2. Standardize students who graduated (previously set to 'inactive') to 'graduated'
        $graduatedStudentIds = DB::table('enrollments')
            ->whereIn('status', ['graduated', 'passed_out'])
            ->pluck('student_id')
            ->unique();

        if ($graduatedStudentIds->isNotEmpty()) {
            DB::table('students')
                ->whereIn('id', $graduatedStudentIds)
                ->where('status', 'inactive')
                ->update([
                    'status' => 'graduated',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};
