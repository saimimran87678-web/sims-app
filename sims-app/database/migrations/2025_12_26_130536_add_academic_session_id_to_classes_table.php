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
            $table->unsignedBigInteger('academic_session_id')->nullable()->after('id');
        });

        // Assign existing classes to the active session
        $activeSessionId = DB::table('academic_sessions')->where('is_active', true)->value('id');
        
        // If no active session, try to find ANY session, or create one (though creation inside migration is risky, assumed existing)
        if ($activeSessionId) {
            DB::table('classes')->update(['academic_session_id' => $activeSessionId]);
        }

        // Now enforce constraints (after data population)
        Schema::table('classes', function (Blueprint $table) {
            // If data exists but no session, we might have issues. Usually safe to make nullable initially, then constrained.
            // But we want it to be a proper foreign key.
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
