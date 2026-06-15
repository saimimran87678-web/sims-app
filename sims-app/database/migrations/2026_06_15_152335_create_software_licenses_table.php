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
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            
            // Core identity (encrypted)
            $table->text('license_key');
            $table->string('school_id');
            
            // Firebase token auth (encrypted)
            $table->text('firebase_refresh_token');
            
            // License status (encrypted)
            $table->text('status');
            
            // Expiry plain timestamp
            $table->timestamp('expires_at')->nullable();
            
            // Cryptographic signatures
            $table->text('rsa_signature');
            $table->string('integrity_hash');
            
            // Offline tracking variables
            $table->integer('offline_grace_days')->default(7);
            $table->timestamp('last_online_verified_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
