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
        // 1. Fee Heads
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Tuition Fee"
            $table->text('description')->nullable();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Fee Structures
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete(); // Null for class-wide, set for per-subject
            
            $table->decimal('amount', 10, 2);
            $table->enum('cycle', ['monthly', 'quarterly', 'annual', 'custom', 'one_time']);
            $table->date('custom_due_date')->nullable();
            
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Allow same fee head multiple times if effective_from differs (e.g., fee increases)
            $table->unique(['class_id', 'fee_head_id', 'subject_id', 'academic_session_id', 'effective_from'], 'fee_struct_unique');
        });

        // 3. Student Fee Overrides (Discounts/Custom amounts)
        Schema::create('student_fee_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        // 4. Fee Records (The Bill)
        Schema::create('fee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            
            $table->string('period'); // e.g., '2026-07', '2026-Q3', 'ADMISSION'
            $table->enum('cycle', ['monthly', 'quarterly', 'annual', 'custom', 'one_time']);
            
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->timestamps();

            // Prevent duplicate bills for the same period/cycle/session
            $table->unique(['student_id', 'period', 'cycle', 'academic_session_id'], 'fee_record_unique');
        });

        // 5. Fee Record Items (The Snapshot)
        Schema::create('fee_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_record_id')->constrained('fee_records')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->cascadeOnDelete(); // Reference only
            
            // Snapshots - these never change even if fee_heads or fee_structures do
            $table->string('fee_head_name');
            $table->string('subject_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // 6. Fee Payments
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_record_id')->constrained('fee_records')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('received_by')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable(); // Set after queue processes
            $table->timestamps();
        });

        // 7. Fee Invoices (Premium)
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_payment_id')->constrained('fee_payments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            $table->string('invoice_number')->unique(); // ALN-2607-001
            $table->integer('invoice_sequence');
            $table->string('school_code');
            $table->string('period_code');
            
            // Snapshots for quick search/verification
            $table->string('student_name');
            $table->string('roll_number')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('class_name');
            
            $table->json('invoice_data');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_record_items');
        Schema::dropIfExists('fee_records');
        Schema::dropIfExists('student_fee_overrides');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_heads');
    }
};
