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
        // 1. Create schedule_templates table
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. Seed Default Template
        $defaultTemplateId = DB::table('schedule_templates')->insertGetId([
            'name' => 'Default Schedule',
            'description' => 'Migrated from previous version',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Modify period_configs
        // First delete existing period configs? No, user said "PRESERVE Period Configs".
        // But we need to link them to the default template.
        Schema::table('period_configs', function (Blueprint $table) use ($defaultTemplateId) {
            $table->foreignId('schedule_template_id')->nullable()->after('id')->constrained('schedule_templates')->onDelete('cascade');
            $table->json('days')->nullable()->after('label')->comment('Array of days this period applies to. Null = All relevant days.');
        });

        // Link existing periods to default template
        DB::table('period_configs')->update(['schedule_template_id' => $defaultTemplateId]);
        
        // Make schedule_template_id non-nullable after populating
        Schema::table('period_configs', function (Blueprint $table) {
             $table->foreignId('schedule_template_id')->nullable(false)->change();
        });


        // 4. Modify timetables
        // User said: "DELETE all existing class/teacher assignments (timetables)"
        DB::table('timetables')->truncate();

        Schema::table('timetables', function (Blueprint $table) {
            $table->foreignId('schedule_template_id')->after('id')->constrained('schedule_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['schedule_template_id']);
            $table->dropColumn('schedule_template_id');
        });

        Schema::table('period_configs', function (Blueprint $table) {
            $table->dropForeign(['schedule_template_id']);
            $table->dropColumn(['schedule_template_id', 'days']);
        });

        Schema::dropIfExists('schedule_templates');
    }
};
