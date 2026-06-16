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
        Schema::create('whatsapp_queue', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->text('message');
            $table->enum('status', ['pending', 'paused', 'sent', 'failed'])->default('pending');
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queue');
    }
};
