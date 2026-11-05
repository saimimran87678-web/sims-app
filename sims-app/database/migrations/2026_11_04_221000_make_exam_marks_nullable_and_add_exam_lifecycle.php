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
        // 1. Modify exam_marks: make marks_obtained nullable with default NULL
        Schema::table('exam_marks', function (Blueprint $table) {
            $table->decimal('marks_obtained', 5, 2)->nullable()->default(null)->change();
        });

        // Convert existing absent records to have marks_obtained = null
        DB::table('exam_marks')->where('is_absent', true)->update([
            'marks_obtained' => null,
        ]);

        // 2. Add lifecycle status and audit columns to exams table
        Schema::table('exams', function (Blueprint $table) {
            $table->string('lifecycle_status', 20)->default('draft')->after('is_active');
            $table->timestamp('published_at')->nullable()->after('lifecycle_status');
            $table->foreignId('published_by_user_id')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('published_by_user_id');
            $table->foreignId('locked_by_user_id')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
        });

        // Set existing active exams to 'entered'
        DB::table('exams')->where('is_active', true)->update([
            'lifecycle_status' => 'entered',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['published_by_user_id']);
            $table->dropForeign(['locked_by_user_id']);
            $table->dropColumn([
                'lifecycle_status',
                'published_at',
                'published_by_user_id',
                'locked_at',
                'locked_by_user_id',
            ]);
        });

        // Revert null marks to 0 before restoring not-null constraint
        DB::table('exam_marks')->whereNull('marks_obtained')->update([
            'marks_obtained' => 0,
        ]);

        Schema::table('exam_marks', function (Blueprint $table) {
            $table->decimal('marks_obtained', 5, 2)->default(0)->nullable(false)->change();
        });
    }
};
