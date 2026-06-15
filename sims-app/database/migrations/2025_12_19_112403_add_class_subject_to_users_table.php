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
        if (!Schema::hasColumn('users', 'class_subject')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('class_subject')->nullable()->after('class_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'class_subject')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('class_subject');
            });
        }
    }
};
