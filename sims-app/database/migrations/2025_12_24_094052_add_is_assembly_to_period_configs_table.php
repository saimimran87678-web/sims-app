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
        Schema::table('period_configs', function (Blueprint $table) {
            $table->boolean('is_assembly')->default(false)->after('is_break');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('period_configs', function (Blueprint $table) {
            $table->dropColumn('is_assembly');
        });
    }
};
