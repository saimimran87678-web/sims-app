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
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('void_status', 20)->default('none')->after('is_void');
            $table->timestamp('void_requested_at')->nullable()->after('void_status');
            $table->foreignId('void_requested_by_user_id')
                ->nullable()
                ->after('void_requested_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('void_rejection_reason')->nullable()->after('void_reason');
            
            $table->index('void_status');
        });

        // Sync existing voided payments to have void_status = 'approved'
        \Illuminate\Support\Facades\DB::table('fee_payments')
            ->where('is_void', true)
            ->update(['void_status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropForeign(['void_requested_by_user_id']);
            $table->dropColumn([
                'void_status',
                'void_requested_at',
                'void_requested_by_user_id',
                'void_rejection_reason',
            ]);
        });
    }
};
