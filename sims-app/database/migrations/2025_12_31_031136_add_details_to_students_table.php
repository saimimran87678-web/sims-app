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
        Schema::table('students', function (Blueprint $table) {
            $table->string('sports')->nullable()->after('phone');
            $table->string('extra_curriculars')->nullable()->after('sports');
            $table->string('transport_mode')->default('none')->after('extra_curriculars'); // school_bus, private_van, bike, bicycle, walk, other, none
            $table->string('vehicle_number')->nullable()->after('transport_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['sports', 'extra_curriculars', 'transport_mode', 'vehicle_number']);
        });
    }
};
