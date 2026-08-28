<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_sessions', 'is_archived')) {
                $table->boolean('is_archived')->default(false);
            }
            if (!Schema::hasColumn('academic_sessions', 'archived_reason')) {
                $table->string('archived_reason')->nullable();
            }
        });

        // Archive all child academic sessions (Evening shifts previously modeled as separate sessions)
        DB::table('academic_sessions')
            ->whereNotNull('parent_id')
            ->update([
                'is_archived' => true,
                'archived_reason' => 'Shift migrated to enrollments',
            ]);
    }

    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'archived_reason']);
        });
    }
};
