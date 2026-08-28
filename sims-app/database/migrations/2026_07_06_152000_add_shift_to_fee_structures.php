<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_structures')) {
            Schema::create('fee_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('fee_head_id')->constrained('fee_heads')->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->enum('cycle', ['monthly', 'quarterly', 'annual', 'custom', 'one_time']);
                $table->date('custom_due_date')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->enum('shift_type', ['morning', 'evening', 'both'])->default('both');
                $table->timestamps();

                $table->unique(['class_id', 'fee_head_id', 'subject_id', 'academic_session_id', 'effective_from'], 'fee_struct_unique');
            });
        } else {
            Schema::table('fee_structures', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_structures', 'shift_type')) {
                    $table->enum('shift_type', ['morning', 'evening', 'both'])->default('both');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
