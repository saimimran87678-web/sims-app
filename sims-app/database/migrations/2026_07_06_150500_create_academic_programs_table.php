<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables from Step 1 to avoid conflicts
        Schema::dropIfExists('program_subjects');
        Schema::dropIfExists('academic_programs');

        // Create new programs table
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "F.Sc Pre-Medical", "ICS", "I.Com", "Arts"
            $table->string('code')->unique(); // e.g., "FSC-PREMED", "ICS", "ICOM"
            $table->string('level'); // e.g., "Matric", "Intermediate", "Primary"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create program_subjects pivot table
        Schema::create('program_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('subject_type'); // enum: compulsory, elective
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['program_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_subjects');
        Schema::dropIfExists('programs');
    }
};
