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
        // Add academic_session_id to holidays
        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable()->constrained()->onDelete('cascade');
        });

        // For SQLite, modifying primary keys is hard, so we create a new table, copy data, and rename
        Schema::create('settings_new', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->foreignId('academic_session_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // A specific setting key should be unique within the same academic session,
            // or globally if academic_session_id is null.
            $table->unique(['key', 'academic_session_id']);
        });

        // Copy existing settings to the new table (they will be global/fallback initially)
        $oldSettings = DB::table('settings')->get();
        foreach ($oldSettings as $setting) {
            DB::table('settings_new')->insert([
                'key' => $setting->key,
                'value' => $setting->value,
                'academic_session_id' => null, // Default to global
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ]);
        }

        Schema::dropIfExists('settings');
        Schema::rename('settings_new', 'settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('settings_old', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Copy back
        $newSettings = DB::table('settings')->whereNull('academic_session_id')->get();
        foreach ($newSettings as $setting) {
            DB::table('settings_old')->insert([
                'key' => $setting->key,
                'value' => $setting->value,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ]);
        }

        Schema::dropIfExists('settings');
        Schema::rename('settings_old', 'settings');

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
