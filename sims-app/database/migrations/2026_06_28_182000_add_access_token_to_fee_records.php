<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_records', function (Blueprint $table) {
            $table->uuid('access_token')->nullable()->unique()->after('id');
        });

        // Backfill existing records with a unique UUID
        \Illuminate\Support\Facades\DB::table('fee_records')
            ->whereNull('access_token')
            ->orderBy('id')
            ->chunkById(100, function ($records) {
                foreach ($records as $record) {
                    \Illuminate\Support\Facades\DB::table('fee_records')
                        ->where('id', $record->id)
                        ->update(['access_token' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_records', function (Blueprint $table) {
            $table->dropColumn('access_token');
        });
    }
};
