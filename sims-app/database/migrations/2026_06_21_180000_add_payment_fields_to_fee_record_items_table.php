<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_record_items', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('balance', 10, 2)->nullable()->after('paid_amount');
        });

        // Initialize paid_amount and balance for existing items
        DB::table('fee_record_items')->update([
            'paid_amount' => 0,
            'balance' => DB::raw('amount')
        ]);

        // Distribute existing payment amounts for partially or fully paid records
        $records = DB::table('fee_records')->where('paid_amount', '>', 0)->get();

        foreach ($records as $record) {
            $remainingPayment = $record->paid_amount;
            $items = DB::table('fee_record_items')->where('fee_record_id', $record->id)->orderBy('id', 'asc')->get();

            foreach ($items as $item) {
                if ($remainingPayment <= 0) {
                    break;
                }

                // Disregard negative items (discounts) from getting paid
                if ($item->amount <= 0) {
                    continue;
                }

                $toPay = min($remainingPayment, $item->amount);
                DB::table('fee_record_items')->where('id', $item->id)->update([
                    'paid_amount' => $toPay,
                    'balance' => $item->amount - $toPay
                ]);

                $remainingPayment -= $toPay;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_record_items', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'balance']);
        });
    }
};
