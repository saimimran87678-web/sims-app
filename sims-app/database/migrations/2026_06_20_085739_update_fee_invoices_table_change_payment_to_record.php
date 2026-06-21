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
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropForeign(['fee_payment_id']);
            $table->dropColumn('fee_payment_id');
            $table->foreignId('fee_record_id')->after('id')->constrained('fee_records')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropForeign(['fee_record_id']);
            $table->dropColumn('fee_record_id');
            $table->foreignId('fee_payment_id')->after('id')->constrained('fee_payments')->cascadeOnDelete();
        });
    }
};
