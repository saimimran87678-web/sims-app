<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->string('user_email', 150)->nullable();
            $table->string('user_role', 100)->nullable();
            $table->string('action', 50)->index(); // created, updated, deleted, login, logout, override, etc.
            $table->string('module', 50)->index(); // students, fees, schedule, substitutions, settings, etc.
            $table->string('model_type', 150)->nullable();
            $table->string('model_id', 100)->nullable();
            $table->string('subject_label', 255)->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->string('shift_type', 20)->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
