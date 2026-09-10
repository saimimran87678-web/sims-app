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
        Schema::table('timetables', function (Blueprint $table) {
            $table->unsignedBigInteger('merged_class_id')->nullable()->after('room');
            $table->foreign('merged_class_id')->references('id')->on('classes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['merged_class_id']);
            $table->dropColumn('merged_class_id');
        });
    }
};
