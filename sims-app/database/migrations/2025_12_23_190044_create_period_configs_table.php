<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('period_no')->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->string('label')->nullable(); // "Period 1", "Break", etc.
            $table->timestamps();
        });

        // Seed default 7 periods + 1 break
        DB::table('period_configs')->insert([
            ['period_no' => 1, 'start_time' => '08:00', 'end_time' => '08:40', 'is_break' => false, 'label' => 'Period 1', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 2, 'start_time' => '08:40', 'end_time' => '09:20', 'is_break' => false, 'label' => 'Period 2', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 3, 'start_time' => '09:20', 'end_time' => '10:00', 'is_break' => false, 'label' => 'Period 3', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 4, 'start_time' => '10:00', 'end_time' => '10:30', 'is_break' => true, 'label' => 'Break', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 5, 'start_time' => '10:30', 'end_time' => '11:10', 'is_break' => false, 'label' => 'Period 4', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 6, 'start_time' => '11:10', 'end_time' => '11:50', 'is_break' => false, 'label' => 'Period 5', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 7, 'start_time' => '11:50', 'end_time' => '12:30', 'is_break' => false, 'label' => 'Period 6', 'created_at' => now(), 'updated_at' => now()],
            ['period_no' => 8, 'start_time' => '12:30', 'end_time' => '13:10', 'is_break' => false, 'label' => 'Period 7', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('period_configs');
    }
};
