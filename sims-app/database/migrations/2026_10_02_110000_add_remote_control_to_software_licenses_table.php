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
            if (!Schema::hasColumn('software_licenses', 'enabled_modules')) {
                $table->json('enabled_modules')->nullable()->after('status');
            }
            if (!Schema::hasColumn('software_licenses', 'broadcast_announcement')) {
                $table->text('broadcast_announcement')->nullable()->after('enabled_modules');
            }
            if (!Schema::hasColumn('software_licenses', 'config_version')) {
                $table->integer('config_version')->default(1)->after('broadcast_announcement');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_licenses', function (Blueprint $table) {
            if (Schema::hasColumn('software_licenses', 'config_version')) {
                $table->dropColumn('config_version');
            }
            if (Schema::hasColumn('software_licenses', 'broadcast_announcement')) {
                $table->dropColumn('broadcast_announcement');
            }
            if (Schema::hasColumn('software_licenses', 'enabled_modules')) {
                $table->dropColumn('enabled_modules');
            }
        });
    }
};
