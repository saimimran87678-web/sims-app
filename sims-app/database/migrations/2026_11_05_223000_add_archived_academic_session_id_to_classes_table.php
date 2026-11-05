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
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('archived_academic_session_id')
                ->nullable()
                ->after('academic_session_id')
                ->constrained('academic_sessions')
                ->nullOnDelete();
        });

        \Illuminate\Support\Facades\DB::statement("UPDATE classes SET archived_academic_session_id = COALESCE(academic_session_id, (SELECT id FROM academic_sessions WHERE is_active = 1 LIMIT 1)) WHERE is_active = 0 AND archived_academic_session_id IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['archived_academic_session_id']);
            $table->dropColumn('archived_academic_session_id');
        });
    }
};
