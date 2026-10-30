<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('b_form_number', 20)->nullable()->after('admission_no');
            $table->date('original_admission_date')->nullable()->after('admission_date');
            $table->string('status_reason', 255)->nullable()->after('status');
            $table->softDeletes()->after('updated_at');

            $table->index('b_form_number', 'idx_students_b_form');
        });

        // Backfill original_admission_date from admission_date if available
        \Illuminate\Support\Facades\DB::table('students')
            ->whereNotNull('admission_date')
            ->whereNull('original_admission_date')
            ->update([
                'original_admission_date' => \Illuminate\Support\Facades\DB::raw('admission_date'),
            ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_b_form');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'b_form_number',
                'original_admission_date',
                'status_reason',
            ]);
        });
    }
};
