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
        Schema::table('marks_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('marks_configs', 'passing_marks')) {
                $table->integer('passing_marks')->default(33)->after('total_marks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marks_configs', function (Blueprint $table) {
            //
        });
    }
};
