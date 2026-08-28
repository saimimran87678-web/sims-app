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
        Schema::table('enrollments', function (Blueprint $table) {
            // Change shift_type from enum to string to support 'regular' (or 'Regular') along with morning and evening
            $table->string('shift_type')->default('morning')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->enum('shift_type', ['morning', 'evening'])->default('morning')->change();
        });
    }
};
