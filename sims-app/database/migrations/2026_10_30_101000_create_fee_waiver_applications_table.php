<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_waiver_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('fee_record_id')->constrained('fee_records')->restrictOnDelete();
            $table->decimal('waived_amount', 10, 2);
            $table->string('concession_type', 50)->default('hardship');
            $table->string('application_reference', 100);
            $table->string('approved_by', 100);
            $table->text('reason');
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'fee_record_id'], 'idx_fee_waiver_student_record');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_waiver_applications');
    }
};
