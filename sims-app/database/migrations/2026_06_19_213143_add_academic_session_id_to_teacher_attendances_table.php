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
        Schema::table('teacher_attendances', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique(['teacher_id', 'date']);
            
            // Add session ID
            $table->foreignId('academic_session_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Add new unique constraint
            $table->unique(['teacher_id', 'date', 'academic_session_id'], 'teacher_date_session_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropUnique('teacher_date_session_unique');
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
            $table->unique(['teacher_id', 'date']);
        });
    }
};
