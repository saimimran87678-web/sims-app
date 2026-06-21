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
        Schema::table('fee_record_items', function (Blueprint $table) {
            $table->string('description')->nullable()->after('fee_head_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_record_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
