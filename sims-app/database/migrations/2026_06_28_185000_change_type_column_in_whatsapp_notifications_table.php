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
        // Change the 'type' column to string to support more values like 'voucher', 'receipt', 'late'
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            $table->string('type')->default('absent')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            // SQLite does not support changing column back to ENUM easily, but we can set it back to string or enum if needed
            $table->string('type')->default('absent')->change();
        });
    }
};
