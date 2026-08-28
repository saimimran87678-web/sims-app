<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('marks_configs', 'academic_session_id')) {
                $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            }
            if (!Schema::hasColumn('marks_configs', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('marks_configs', 'program_id')) {
                $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            }
            if (!Schema::hasColumn('marks_configs', 'theory_marks')) {
                $table->integer('theory_marks')->nullable();
            }
            if (!Schema::hasColumn('marks_configs', 'practical_marks')) {
                $table->integer('practical_marks')->nullable();
            }
            if (!Schema::hasColumn('marks_configs', 'is_board_exam')) {
                $table->boolean('is_board_exam')->default(false);
            }
            if (!Schema::hasColumn('marks_configs', 'effective_from')) {
                $table->date('effective_from')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marks_configs', function (Blueprint $table) {
            $table->dropColumn([
                'academic_session_id',
                'subject_id',
                'program_id',
                'theory_marks',
                'practical_marks',
                'is_board_exam',
                'effective_from'
            ]);
        });
    }
};
