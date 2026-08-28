<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('period_configs', function (Blueprint $table) {
            $table->dropUnique(['period_no']);
        });

        Schema::table('period_configs', function (Blueprint $table) {
            $table->string('shift_type')->default('morning');
            $table->unique(['period_no', 'shift_type']);
        });

        // Seed default periods for evening and regular shifts, copying from existing morning periods
        $morningPeriods = DB::table('period_configs')->where('shift_type', 'morning')->get();
        
        foreach ($morningPeriods as $period) {
            // Seed evening version if it doesn't already exist
            $existsEvening = DB::table('period_configs')
                ->where('period_no', $period->period_no)
                ->where('shift_type', 'evening')
                ->exists();
            if (!$existsEvening) {
                DB::table('period_configs')->insert([
                    'period_no' => $period->period_no,
                    'start_time' => $period->start_time,
                    'end_time' => $period->end_time,
                    'is_break' => $period->is_break,
                    'is_assembly' => $period->is_assembly ?? false,
                    'label' => $period->label,
                    'shift_type' => 'evening',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed regular version if it doesn't already exist
            $existsRegular = DB::table('period_configs')
                ->where('period_no', $period->period_no)
                ->where('shift_type', 'regular')
                ->exists();
            if (!$existsRegular) {
                DB::table('period_configs')->insert([
                    'period_no' => $period->period_no,
                    'start_time' => $period->start_time,
                    'end_time' => $period->end_time,
                    'is_break' => $period->is_break,
                    'is_assembly' => $period->is_assembly ?? false,
                    'label' => $period->label,
                    'shift_type' => 'regular',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('period_configs')->whereIn('shift_type', ['evening', 'regular'])->delete();

        Schema::table('period_configs', function (Blueprint $table) {
            $table->dropUnique(['period_no', 'shift_type']);
            $table->dropColumn('shift_type');
            $table->unique('period_no');
        });
    }
};
