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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('cnic')->nullable();
            $table->string('cnic_hash', 64)->nullable()->unique();
            $table->text('phone')->nullable();
            $table->string('phone_hash', 64)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->boolean('whatsapp_opt_in')->default(true);
            $table->timestamps();
        });

        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('guardian_id')->constrained('guardians')->onDelete('restrict');
            $table->string('relation', 50)->default('Father');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_emergency')->default(false);
            $table->boolean('can_pickup')->default(false);
            $table->decimal('concession_percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'guardian_id']);
            $table->index(['student_id', 'is_primary']);
        });

        // Safe Backfill: Migrate existing student fathers/guardians into normalized entities
        $now = now();
        $students = DB::table('students')->get();

        foreach ($students as $student) {
            $hasFather = !empty($student->father_name) || !empty($student->father_cnic_hash) || !empty($student->father_cnic);
            $hasGuardian = !empty($student->guardian_cnic_hash) || !empty($student->guardian_cnic);
            $hasPhone = !empty($student->phone);

            if (!$hasFather && !$hasGuardian && !$hasPhone) {
                continue;
            }

            $cnicHash = $student->is_guardian_primary
                ? ($student->guardian_cnic_hash ?: $student->father_cnic_hash)
                : ($student->father_cnic_hash ?: $student->guardian_cnic_hash);

            $cnicRaw = $student->is_guardian_primary
                ? ($student->guardian_cnic ?: $student->father_cnic)
                : ($student->father_cnic ?: $student->guardian_cnic);

            $name = $student->father_name ?: ($student->name ? 'Guardian of ' . $student->name : 'Guardian');
            $relation = $student->guardian_relation ?: ($student->is_guardian_primary ? 'Guardian' : 'Father');

            $guardianId = null;

            if (!empty($cnicHash)) {
                $existing = DB::table('guardians')->where('cnic_hash', $cnicHash)->first();
                if ($existing) {
                    $guardianId = $existing->id;
                }
            }

            if (!$guardianId) {
                $guardianId = DB::table('guardians')->insertGetId([
                    'name' => $name,
                    'cnic' => $cnicRaw,
                    'cnic_hash' => $cnicHash,
                    'phone' => $student->phone,
                    'phone_hash' => $student->phone_hash,
                    'address' => $student->address,
                    'whatsapp_opt_in' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('student_guardian')->updateOrInsert(
                [
                    'student_id' => $student->id,
                    'guardian_id' => $guardianId,
                ],
                [
                    'relation' => $relation,
                    'is_primary' => true,
                    'is_emergency' => true,
                    'can_pickup' => true,
                    'concession_percentage' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('guardians');
    }
};
