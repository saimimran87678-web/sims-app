<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Map all duplicate subjects to their canonical min(id)
        $duplicates = DB::table('subjects')->orderBy('id')->get();
        $nameToCanonicalId = [];
        $subjectIdMapping = []; // old_id => canonical_id

        foreach ($duplicates as $sub) {
            $name = trim($sub->name);
            if (!isset($nameToCanonicalId[$name])) {
                $nameToCanonicalId[$name] = $sub->id;
            }
            $subjectIdMapping[$sub->id] = $nameToCanonicalId[$name];
        }

        // Remap foreign key references across all referencing tables
        foreach ($subjectIdMapping as $oldId => $canonicalId) {
            if ($oldId === $canonicalId) {
                continue;
            }

            DB::table('subject_allocations')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('exam_schedules')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('timetables')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('grade_locks')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('substitutions')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('exam_marks')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('fee_structures')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
            DB::table('student_subject')->where('subject_id', $oldId)->update(['subject_id' => $canonicalId]);
        }

        // Remap marks_configs.subject_id based on canonical name matching
        foreach ($nameToCanonicalId as $name => $canonicalId) {
            DB::table('marks_configs')
                ->where('subject', $name)
                ->update(['subject_id' => $canonicalId]);
        }

        // 2. Remove duplicate subject rows, keeping only canonical IDs
        $canonicalIds = array_values($nameToCanonicalId);
        DB::table('subjects')->whereNotIn('id', $canonicalIds)->delete();

        // 3. Make class_id nullable on subjects and add category & is_active
        Schema::dropIfExists('subjects_canonical');
        Schema::create('subjects_canonical', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->string('category')->default('General');
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Copy canonical subjects into subjects_canonical
        $canonicalRows = DB::table('subjects')->get();
        foreach ($canonicalRows as $row) {
            DB::table('subjects_canonical')->insert([
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'category' => 'General',
                'class_id' => null, // Decoupled from specific class!
                'is_active' => true,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }

        // Add all standard canonical subjects if they don't exist
        $canonicalCatalogue = [
            ['name' => 'English', 'code' => 'ENG', 'category' => 'Language'],
            ['name' => 'Mathematics', 'code' => 'MATH', 'category' => 'Mathematics'],
            ['name' => 'General Science', 'code' => 'SCI', 'category' => 'Science'],
            ['name' => 'Urdu', 'code' => 'URD', 'category' => 'Language'],
            ['name' => 'Islamiat', 'code' => 'ISL', 'category' => 'Religion/Ethics'],
            ['name' => 'Social Studies', 'code' => 'SST', 'category' => 'Social Studies'],
            ['name' => 'Computer Science', 'code' => 'CS', 'category' => 'Computer'],
            ['name' => 'Pakistan Studies', 'code' => 'PST', 'category' => 'Social Studies'],
            ['name' => 'Physics', 'code' => 'PHY', 'category' => 'Science'],
            ['name' => 'Chemistry', 'code' => 'CHEM', 'category' => 'Science'],
            ['name' => 'Biology', 'code' => 'BIO', 'category' => 'Science'],
            ['name' => 'Islamic Education', 'code' => 'IED', 'category' => 'Religion/Ethics'],
            ['name' => 'Tarjuma-tul-Quran', 'code' => 'TQ', 'category' => 'Religion/Ethics'],
            ['name' => 'Statistics', 'code' => 'STAT', 'category' => 'Science/Commerce'],
            ['name' => 'Principles of Accounting', 'code' => 'POA', 'category' => 'Commerce'],
            ['name' => 'Economics', 'code' => 'ECON', 'category' => 'Commerce'],
            ['name' => 'Arabic', 'code' => 'ARB', 'category' => 'Language'],
        ];

        foreach ($canonicalCatalogue as $sub) {
            if (!DB::table('subjects_canonical')->where('name', $sub['name'])->exists() && !DB::table('subjects_canonical')->where('code', $sub['code'])->exists()) {
                DB::table('subjects_canonical')->insert([
                    'name' => $sub['name'],
                    'code' => $sub['code'],
                    'category' => $sub['category'],
                    'class_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Swap tables: drop old subjects and rename subjects_canonical -> subjects
        Schema::dropIfExists('subjects');
        Schema::rename('subjects_canonical', 'subjects');

        // 4. Create program_subjects pivot table
        Schema::dropIfExists('program_subjects');
        Schema::create('program_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('subject_type')->default('compulsory'); // compulsory | elective
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['program_id', 'subject_id']);
        });

        // 5. Create class_programs pivot table
        Schema::dropIfExists('class_programs');
        Schema::create('class_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['class_id', 'program_id']);
        });

        // 6. Seed Standard Academic Programs
        $programs = [
            [
                'name' => 'Middle School General',
                'code' => 'MID-GEN',
                'level' => 'Middle',
                'description' => 'General curriculum for Classes 1 to 8 with standard compulsory subjects.',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Mathematics', 'type' => 'compulsory'],
                    ['name' => 'General Science', 'type' => 'compulsory'],
                    ['name' => 'Islamiat', 'type' => 'compulsory'],
                    ['name' => 'Social Studies', 'type' => 'compulsory'],
                    ['name' => 'Computer Science', 'type' => 'compulsory'],
                ]
            ],
            [
                'name' => 'SSC - Science (Biology Group)',
                'code' => 'SSC-BIO',
                'level' => 'Matric',
                'description' => 'Secondary School Certificate - Science track with Biology (Classes 9–10).',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamiat', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Mathematics', 'type' => 'compulsory'],
                    ['name' => 'Physics', 'type' => 'compulsory'],
                    ['name' => 'Chemistry', 'type' => 'compulsory'],
                    ['name' => 'Biology', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'SSC - Science (Computer Science Group)',
                'code' => 'SSC-CS',
                'level' => 'Matric',
                'description' => 'Secondary School Certificate - Science track with Computer Science (Classes 9–10).',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamiat', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Mathematics', 'type' => 'compulsory'],
                    ['name' => 'Physics', 'type' => 'compulsory'],
                    ['name' => 'Chemistry', 'type' => 'compulsory'],
                    ['name' => 'Computer Science', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'HSSC - F.Sc Pre-Medical',
                'code' => 'HSSC-MED',
                'level' => 'Intermediate',
                'description' => 'Higher Secondary Certificate - Pre-Medical (Classes 11–12).',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamic Education', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Physics', 'type' => 'compulsory'],
                    ['name' => 'Chemistry', 'type' => 'compulsory'],
                    ['name' => 'Biology', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'HSSC - F.Sc Pre-Engineering',
                'code' => 'HSSC-ENG',
                'level' => 'Intermediate',
                'description' => 'Higher Secondary Certificate - Pre-Engineering (Classes 11–12).',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamic Education', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Physics', 'type' => 'compulsory'],
                    ['name' => 'Chemistry', 'type' => 'compulsory'],
                    ['name' => 'Mathematics', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'HSSC - ICS (Physics)',
                'code' => 'HSSC-ICSP',
                'level' => 'Intermediate',
                'description' => 'Higher Secondary Certificate - Intermediate in Computer Science with Physics.',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamic Education', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Physics', 'type' => 'elective'],
                    ['name' => 'Computer Science', 'type' => 'elective'],
                    ['name' => 'Mathematics', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'HSSC - ICS (Statistics)',
                'code' => 'HSSC-ICSS',
                'level' => 'Intermediate',
                'description' => 'Higher Secondary Certificate - Intermediate in Computer Science with Statistics.',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamic Education', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Statistics', 'type' => 'elective'],
                    ['name' => 'Computer Science', 'type' => 'elective'],
                    ['name' => 'Mathematics', 'type' => 'elective'],
                ]
            ],
            [
                'name' => 'HSSC - I.Com',
                'code' => 'HSSC-ICOM',
                'level' => 'Intermediate',
                'description' => 'Higher Secondary Certificate - Intermediate in Commerce.',
                'subjects' => [
                    ['name' => 'English', 'type' => 'compulsory'],
                    ['name' => 'Urdu', 'type' => 'compulsory'],
                    ['name' => 'Islamic Education', 'type' => 'compulsory'],
                    ['name' => 'Pakistan Studies', 'type' => 'compulsory'],
                    ['name' => 'Principles of Accounting', 'type' => 'elective'],
                    ['name' => 'Economics', 'type' => 'elective'],
                    ['name' => 'Mathematics', 'type' => 'elective'],
                ]
            ]
        ];

        // Fetch subject ID map
        $subjectMap = DB::table('subjects')->pluck('id', 'name')->toArray();

        foreach ($programs as $progData) {
            $progId = DB::table('programs')->insertGetId([
                'name' => $progData['name'],
                'code' => $progData['code'],
                'level' => $progData['level'],
                'description' => $progData['description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $order = 1;
            foreach ($progData['subjects'] as $subItem) {
                if (isset($subjectMap[$subItem['name']])) {
                    DB::table('program_subjects')->insert([
                        'program_id' => $progId,
                        'subject_id' => $subjectMap[$subItem['name']],
                        'subject_type' => $subItem['type'],
                        'sort_order' => $order++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 7. Associate existing Classes with Programs
        $midGenId = DB::table('programs')->where('code', 'MID-GEN')->value('id');
        $sscBioId = DB::table('programs')->where('code', 'SSC-BIO')->value('id');
        $sscCsId  = DB::table('programs')->where('code', 'SSC-CS')->value('id');
        $hsscMedId = DB::table('programs')->where('code', 'HSSC-MED')->value('id');
        $hsscEngId = DB::table('programs')->where('code', 'HSSC-ENG')->value('id');
        $hsscIcsPId = DB::table('programs')->where('code', 'HSSC-ICSP')->value('id');

        $classes = DB::table('classes')->get();
        foreach ($classes as $class) {
            $grade = (int) $class->numeric_value;
            if ($grade >= 1 && $grade <= 8 && $midGenId) {
                DB::table('class_programs')->insertOrIgnore([
                    'class_id' => $class->id,
                    'program_id' => $midGenId,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($grade === 9 || $grade === 10) {
                if ($sscCsId) {
                    DB::table('class_programs')->insertOrIgnore([
                        'class_id' => $class->id,
                        'program_id' => $sscCsId,
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($sscBioId) {
                    DB::table('class_programs')->insertOrIgnore([
                        'class_id' => $class->id,
                        'program_id' => $sscBioId,
                        'is_default' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($grade >= 11 && $grade <= 12) {
                foreach ([$hsscMedId, $hsscEngId, $hsscIcsPId] as $idx => $pId) {
                    if ($pId) {
                        DB::table('class_programs')->insertOrIgnore([
                            'class_id' => $class->id,
                            'program_id' => $pId,
                            'is_default' => ($idx === 0),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 8. Backfill enrollments.program_id for existing students
        $enrollments = DB::table('enrollments')->whereNull('program_id')->get();
        foreach ($enrollments as $enr) {
            $class = DB::table('classes')->where('id', $enr->class_id)->first();
            if (!$class) {
                continue;
            }

            $grade = (int) $class->numeric_value;
            if ($grade <= 8 && $midGenId) {
                DB::table('enrollments')->where('id', $enr->id)->update(['program_id' => $midGenId]);
            } elseif ($grade === 9 || $grade === 10) {
                $hasBio = DB::table('student_subject')
                    ->join('subjects', 'student_subject.subject_id', '=', 'subjects.id')
                    ->where('student_subject.student_id', $enr->student_id)
                    ->where('subjects.name', 'Biology')
                    ->exists();

                $targetProg = $hasBio ? $sscBioId : $sscCsId;
                if ($targetProg) {
                    DB::table('enrollments')->where('id', $enr->id)->update(['program_id' => $targetProg]);
                }
            } else {
                $defaultProgId = DB::table('class_programs')
                    ->where('class_id', $enr->class_id)
                    ->orderByDesc('is_default')
                    ->value('program_id');
                if ($defaultProgId) {
                    DB::table('enrollments')->where('id', $enr->id)->update(['program_id' => $defaultProgId]);
                }
            }
        }

        // 9. Final synchronization for marks_configs.subject_id
        $canonicalMap = DB::table('subjects')->pluck('id', 'name')->toArray();
        foreach ($canonicalMap as $subName => $canonId) {
            DB::table('marks_configs')
                ->where('subject', $subName)
                ->update(['subject_id' => $canonId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_programs');
        Schema::dropIfExists('program_subjects');
    }
};
