<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('rfid_tag', 32)->nullable()->unique()->after('admission_no');
            $table->timestamp('card_issued_at')->nullable()->after('rfid_tag');
            $table->string('card_status', 20)->default('unassigned')->after('card_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['rfid_tag', 'card_issued_at', 'card_status']);
        });
    }
};
