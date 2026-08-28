<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up from any previous partially failed run
        Schema::dropIfExists('program_subjects');
        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('session_subjects');
        Schema::dropIfExists('session_classes');

        // 1. Create Academic Programs table (Subject Bundling)
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "I.C.S", "F.S.C Pre-Engineering", "I.Com"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Create program_subjects pivot table (Subject bundles)
        Schema::create('program_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->boolean('is_elective')->default(false);
            $table->timestamps();
        });

        // 3. Create session_classes pivot table
        Schema::create('session_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('shift_type')->default('Morning'); // Morning, Evening, Regular
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Create session_subjects pivot table
        Schema::create('session_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();
        });

        // 5. Migrate existing class-session relationships
        $classes = DB::table('classes')->get();
        foreach ($classes as $class) {
            if (isset($class->academic_session_id) && $class->academic_session_id) {
                $session = DB::table('academic_sessions')->where('id', $class->academic_session_id)->first();
                $shift = $session ? $session->shift_type : 'Morning';

                DB::table('session_classes')->insert([
                    'academic_session_id' => $class->academic_session_id,
                    'class_id' => $class->id,
                    'shift_type' => $shift,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Migrate subjects of this class
                $subjects = DB::table('subjects')->where('class_id', $class->id)->get();
                foreach ($subjects as $subject) {
                    DB::table('session_subjects')->insert([
                        'academic_session_id' => $class->academic_session_id,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 6. Remove academic_session_id from classes
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('academic_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->cascadeOnDelete();
        });

        $sessionClasses = DB::table('session_classes')->get();
        foreach ($sessionClasses as $sc) {
            DB::table('classes')
                ->where('id', $sc->class_id)
                ->update(['academic_session_id' => $sc->academic_session_id]);
        }

        Schema::dropIfExists('session_subjects');
        Schema::dropIfExists('session_classes');
        Schema::dropIfExists('program_subjects');
        Schema::dropIfExists('academic_programs');
    }
};
