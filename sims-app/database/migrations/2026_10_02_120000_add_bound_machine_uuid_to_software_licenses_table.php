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
            if (!Schema::hasColumn('software_licenses', 'bound_machine_uuid')) {
                $table->string('bound_machine_uuid')->nullable()->after('school_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('software_licenses', 'bound_machine_uuid')) {
                $table->dropColumn('bound_machine_uuid');
            }
        });
    }
};
