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
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->cascadeOnDelete();
            $table->string('grade', 10);
            $table->decimal('percentage_from', 5, 2);
            $table->decimal('percentage_to', 5, 2);
            $table->decimal('gpa_point', 3, 2)->default(0.00);
            $table->string('remarks', 50);
            $table->string('color', 30)->default('text-gray-600');
            $table->boolean('is_passing')->default(true);
            $table->integer('order_seq')->default(0);
            $table->timestamps();

            $table->index(['academic_session_id', 'order_seq']);
        });

        // Seed default canonical grade scales (applicable globally when academic_session_id is NULL)
        $defaults = [
            [
                'academic_session_id' => null,
                'grade' => 'A+',
                'percentage_from' => 90.00,
                'percentage_to' => 100.00,
                'gpa_point' => 4.00,
                'remarks' => 'Outstanding',
                'color' => 'text-emerald-600',
                'is_passing' => true,
                'order_seq' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'A',
                'percentage_from' => 80.00,
                'percentage_to' => 89.99,
                'gpa_point' => 3.70,
                'remarks' => 'Excellent',
                'color' => 'text-green-600',
                'is_passing' => true,
                'order_seq' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'B',
                'percentage_from' => 70.00,
                'percentage_to' => 79.99,
                'gpa_point' => 3.00,
                'remarks' => 'Very Good',
                'color' => 'text-blue-600',
                'is_passing' => true,
                'order_seq' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'C',
                'percentage_from' => 60.00,
                'percentage_to' => 69.99,
                'gpa_point' => 2.00,
                'remarks' => 'Good',
                'color' => 'text-cyan-600',
                'is_passing' => true,
                'order_seq' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'D',
                'percentage_from' => 50.00,
                'percentage_to' => 59.99,
                'gpa_point' => 1.00,
                'remarks' => 'Satisfactory',
                'color' => 'text-amber-600',
                'is_passing' => true,
                'order_seq' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'E',
                'percentage_from' => 33.00,
                'percentage_to' => 49.99,
                'gpa_point' => 0.50,
                'remarks' => 'Pass',
                'color' => 'text-orange-600',
                'is_passing' => true,
                'order_seq' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'academic_session_id' => null,
                'grade' => 'F',
                'percentage_from' => 0.00,
                'percentage_to' => 32.99,
                'gpa_point' => 0.00,
                'remarks' => 'Fail / Needs Improvement',
                'color' => 'text-rose-600',
                'is_passing' => false,
                'order_seq' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('grade_scales')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_scales');
    }
};
