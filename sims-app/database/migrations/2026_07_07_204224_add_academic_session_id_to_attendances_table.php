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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable()->after('student_id')->constrained('academic_sessions')->nullOnDelete();
        });

        // Populate existing records:
        // 1. Map to session whose date range fits and which was created before the attendance record
        $sessions = DB::table('academic_sessions')->orderBy('created_at', 'asc')->get();
        foreach ($sessions as $session) {
            DB::table('attendances')
                ->whereNull('academic_session_id')
                ->whereBetween('date', [$session->start_date, $session->end_date])
                ->where('created_at', '>=', $session->created_at)
                ->update(['academic_session_id' => $session->id]);
        }

        // 2. Fallback for any remaining unmapped records (e.g. out of range test records) to the oldest session (ID 1)
        DB::table('attendances')
            ->whereNull('academic_session_id')
            ->update(['academic_session_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
