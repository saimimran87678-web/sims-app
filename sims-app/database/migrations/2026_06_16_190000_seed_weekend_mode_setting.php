<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert default weekend_mode setting (sat_sun = both Saturday and Sunday are weekends)
        DB::table('settings')->insertOrIgnore([
            'key'        => 'weekend_mode',
            'value'      => 'sat_sun',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'weekend_mode')->delete();
    }
};
