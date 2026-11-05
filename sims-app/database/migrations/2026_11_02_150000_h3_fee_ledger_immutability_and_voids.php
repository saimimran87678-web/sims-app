<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('receipt_number', 32)->nullable()->unique()->after('id');
            $table->foreignId('received_by_user_id')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            $table->boolean('is_void')->default(false)->after('notes');
            $table->timestamp('voided_at')->nullable()->after('is_void');
            $table->foreignId('voided_by_user_id')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by_user_id');

            $table->index(['is_void', 'payment_date'], 'idx_fee_payments_void_date');
        });

        // Seed permission 'fees.void'
        try {
            $permission = Permission::firstOrCreate(['name' => 'fees.void', 'guard_name' => 'web']);
            $superAdmin = Role::where('name', 'Super Admin')->first();
            if ($superAdmin && !$superAdmin->hasPermissionTo('fees.void')) {
                $superAdmin->givePermissionTo($permission);
            }
        } catch (\Throwable $e) {
            // Safe fallback if roles table not yet present in testing isolated migrations
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropIndex('idx_fee_payments_void_date');
            $table->dropForeign(['received_by_user_id']);
            $table->dropForeign(['voided_by_user_id']);
            $table->dropColumn([
                'receipt_number',
                'received_by_user_id',
                'is_void',
                'voided_at',
                'voided_by_user_id',
                'void_reason',
            ]);
        });

        try {
            Permission::where('name', 'fees.void')->delete();
        } catch (\Throwable $e) {
        }
    }
};
