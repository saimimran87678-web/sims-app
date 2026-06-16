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
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('academic_sessions')->onDelete('cascade');
            $table->string('shift_type')->default('Regular'); // Morning, Evening, Regular
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropColumn('shift_type');
        });
    }
};
