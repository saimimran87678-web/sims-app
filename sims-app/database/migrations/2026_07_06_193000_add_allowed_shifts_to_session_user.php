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
        Schema::table('session_user', function (Blueprint $table) {
            if (!Schema::hasColumn('session_user', 'allowed_shifts')) {
                $table->string('allowed_shifts')->default('both'); // morning, evening, both
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_user', function (Blueprint $table) {
            if (Schema::hasColumn('session_user', 'allowed_shifts')) {
                $table->dropColumn('allowed_shifts');
            }
        });
    }
};
