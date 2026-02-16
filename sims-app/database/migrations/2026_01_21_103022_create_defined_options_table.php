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
        Schema::create('defined_options', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // 'sport', 'activity'
            $table->string('name');
            $table->timestamps();
        });

        // Seed default options
        $sports = [
            'Cricket', 'Football', 'Hockey', 'Badminton', 'Table Tennis', 
            'Volleyball', 'Basketball', 'Athletics'
        ];
        
        $activities = [
            'Debating', 'Drama', 'Science Club', 'Scouts', 'Art Club', 
            'Computer Club', 'Religious Society', 'Literary Society'
        ];

        $now = now();

        foreach ($sports as $sport) {
            DB::table('defined_options')->insert([
                'type' => 'sport',
                'name' => $sport,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($activities as $activity) {
            DB::table('defined_options')->insert([
                'type' => 'activity',
                'name' => $activity,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defined_options');
    }
};
