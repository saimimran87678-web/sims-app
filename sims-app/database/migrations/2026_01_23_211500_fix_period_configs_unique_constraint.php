<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old global unique index on period_no
        Schema::table('period_configs', function (Blueprint $table) {
            $table->dropUnique(['period_no']); // Dropping standard unique index
        });

        // Add new composite unique index
        Schema::table('period_configs', function (Blueprint $table) {
             // We can enforce uniqueness per template.
             // Using start_time as secondary key? Or just allow multiple period_no?
             // Actually, period_no usage is just for sorting primarily. 
             // But let's assume one period #1 per template per day-set?
             // Since 'days' is JSON and nullable, uniqueness is hard to enforce at DB level.
             // Just dropping the global unique constraint is enough to fix the crash.
             // We can optionally add a standard index for performance.
             $table->index('period_no');
        });
    }

    public function down(): void
    {
        // Re-adding unique might fail if duplicates exist, but that IS the point.
        // We are moving away from global uniqueness.
    }
};
