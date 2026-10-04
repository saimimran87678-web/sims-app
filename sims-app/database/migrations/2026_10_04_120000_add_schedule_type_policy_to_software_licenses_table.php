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
        Schema::table('software_licenses', function (Blueprint $table) {
            if (!Schema::hasColumn('software_licenses', 'schedule_type_policy')) {
                $table->string('schedule_type_policy')->default('configurable')->after('config_version');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('software_licenses', 'schedule_type_policy')) {
                $table->dropColumn('schedule_type_policy');
            }
        });
    }
};
