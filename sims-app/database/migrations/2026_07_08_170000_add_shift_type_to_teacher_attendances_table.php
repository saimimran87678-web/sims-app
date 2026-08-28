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
            $table->string('shift_type')->default('morning');
            
            // Drop old unique constraint
            $table->dropUnique('teacher_date_session_unique');
            
            // Add new unique constraint
            $table->unique(['teacher_id', 'date', 'academic_session_id', 'shift_type'], 'teacher_date_session_shift_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropUnique('teacher_date_session_shift_unique');
            $table->dropColumn('shift_type');
            $table->unique(['teacher_id', 'date', 'academic_session_id'], 'teacher_date_session_unique');
        });
    }
};
