<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassAndSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates classes 6 to 12 with 2 or 3 sections (e.g., 6A, 6B, 7A, 7B, 7C)
     * without inserting rows into the sections table, and attaches common subjects.
     */
    public function run(): void
    {
        // 1. Resolve or establish active academic session
        $session = AcademicSession::where('is_active', true)->first()
            ?? AcademicSession::firstOrCreate(
                ['name' => '2026-2027'],
                [
                    'start_date' => now()->startOfYear(),
                    'end_date'   => now()->endOfYear(),
                    'is_active'  => true,
                    'shift_type' => 'Regular',
                ]
            );

        $sessionId = $session->id;
        $shiftType = ($session->shift_type === 'Regular') ? 'regular' : 'morning';

        // 2. Define curriculum subjects by education level
        $middleSubjects = [
            ['name' => 'English',          'code' => 'ENG'],
            ['name' => 'Mathematics',      'code' => 'MATH'],
            ['name' => 'General Science',  'code' => 'SCI'],
            ['name' => 'Urdu',             'code' => 'URD'],
            ['name' => 'Islamiat',         'code' => 'ISL'],
            ['name' => 'Social Studies',   'code' => 'SST'],
            ['name' => 'Computer Science', 'code' => 'CS'],
        ];

        $secondarySubjects = [
            ['name' => 'English',          'code' => 'ENG'],
            ['name' => 'Mathematics',      'code' => 'MATH'],
            ['name' => 'Urdu',             'code' => 'URD'],
            ['name' => 'Islamiat',         'code' => 'ISL'],
            ['name' => 'Pakistan Studies', 'code' => 'PST'],
            ['name' => 'Physics',          'code' => 'PHY'],
            ['name' => 'Chemistry',        'code' => 'CHEM'],
            ['name' => 'Biology',          'code' => 'BIO'],
            ['name' => 'Computer Science', 'code' => 'CS'],
        ];

        $higherSecondarySubjects = [
            ['name' => 'English',          'code' => 'ENG'],
            ['name' => 'Urdu',             'code' => 'URD'],
            ['name' => 'Islamic Education','code' => 'IED'],
            ['name' => 'Pakistan Studies', 'code' => 'PST'],
            ['name' => 'Physics',          'code' => 'PHY'],
            ['name' => 'Chemistry',        'code' => 'CHEM'],
            ['name' => 'Mathematics',      'code' => 'MATH'],
            ['name' => 'Biology',          'code' => 'BIO'],
            ['name' => 'Computer Science', 'code' => 'CS'],
        ];

        // 3. Inject classes from grade 6 to 12
        $grades = range(6, 12);
        $createdClasses = [];

        DB::beginTransaction();
        try {
            foreach ($grades as $grade) {
                // Randomly choose 2 sections (A, B) or 3 sections (A, B, C)
                $sectionCount = rand(2, 3);
                $sections = array_slice(['A', 'B', 'C'], 0, $sectionCount);

                // Select subject package for this grade
                if ($grade <= 8) {
                    $subjects = $middleSubjects;
                } elseif ($grade <= 10) {
                    $subjects = $secondarySubjects;
                } else {
                    $subjects = $higherSecondarySubjects;
                }

                foreach ($sections as $sec) {
                    $className = "{$grade}{$sec}";

                    // Find or create the class entry without global session scope
                    $class = Classes::withoutGlobalScope('active_session')
                        ->where('name', $className)
                        ->where('academic_session_id', $sessionId)
                        ->where('shift_type', $shiftType)
                        ->first();

                    if (!$class) {
                        $class = Classes::create([
                            'name'                => $className,
                            'numeric_value'       => $grade,
                            'academic_session_id' => $sessionId,
                            'shift_type'          => $shiftType,
                        ]);
                    }

                    $createdClasses[$className] = $class;

                    // Add common subjects for this class
                    foreach ($subjects as $sub) {
                        Subject::firstOrCreate(
                            [
                                'class_id' => $class->id,
                                'name'     => $sub['name'],
                            ],
                            [
                                'code'     => $sub['code'],
                            ]
                        );
                    }
                }
            }

            // 4. Link next_class_id for progressive promotion (e.g., 6A -> 7A, 6B -> 7B)
            foreach ($createdClasses as $className => $class) {
                $grade = $class->numeric_value;
                $sec = substr($className, strlen((string) $grade));
                $nextClassName = ($grade + 1) . $sec;

                if (isset($createdClasses[$nextClassName])) {
                    $class->update(['next_class_id' => $createdClasses[$nextClassName]->id]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
