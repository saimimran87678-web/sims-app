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
            if (!Schema::hasColumn('software_licenses', 'modules_v3')) {
                $table->boolean('modules_v3')->default(false)->after('modules_v2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('software_licenses', 'modules_v3')) {
                $table->dropColumn('modules_v3');
            }
        });
    }
};
