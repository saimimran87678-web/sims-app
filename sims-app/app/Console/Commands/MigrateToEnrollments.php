<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicSession;

class MigrateToEnrollments extends Command
{
    protected $signature = 'sims:migrate-to-enrollments';
    protected $description = 'Migrate current student class, section, and roll number fields to the new enrollments table structure';

    public function handle()
    {
        $this->info('Starting student data migration to enrollments...');

        $students = Student::all();
        $totalStudents = $students->count();

        if ($totalStudents === 0) {
            $this->warn('No students found in the database to migrate.');
            return 0;
        }

        $processed = 0;
        $enrollmentsCreated = 0;
        $morningCount = 0;
        $eveningCount = 0;
        $dualShiftCount = 0;

        DB::beginTransaction();

        try {
            foreach ($students as $student) {
                if (!$student->class_id) {
                    $this->warn("Student ID {$student->id} ({$student->name}) has no class_id assigned. Skipping.");
                    continue;
                }

                $sessionId = null;
                $shiftType = 'morning';

                // 1. Try to resolve session from session_classes mapping
                $sessionClass = DB::table('session_classes')->where('class_id', $student->class_id)->first();
                if ($sessionClass) {
                    $sessionId = $sessionClass->academic_session_id;
                    if (strtolower($sessionClass->shift_type) === 'evening') {
                        $shiftType = 'evening';
                    }
                }

                // 2. Fallback to currently active session
                if (!$sessionId) {
                    $sessionId = AcademicSession::getActiveSessionId();
                }

                // 3. Normalize session and shift if it is a child session (Evening shift child session)
                if ($sessionId) {
                    $session = AcademicSession::find($sessionId);
                    if ($session) {
                        if ($session->parent_id) {
                            $sessionId = $session->parent_id;
                            $shiftType = 'evening';
                        } elseif (strtolower($session->shift_type) === 'evening') {
                            $shiftType = 'evening';
                        }
                    }
                }

                if (!$sessionId) {
                    $this->error("Could not resolve academic session for Student ID {$student->id} ({$student->name}). Skipping.");
                    continue;
                }

                // 4. Create or update the enrollment record
                $enrollment = Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $sessionId,
                        'shift_type' => $shiftType,
                    ],
                    [
                        'class_id' => $student->class_id,
                        'roll_number' => $student->roll_no,
                        'status' => $student->status ?: 'active',
                    ]
                );

                $processed++;
                $enrollmentsCreated++;

                if ($shiftType === 'morning') {
                    $morningCount++;
                } else {
                    $eveningCount++;
                }

                // Check for dual shift
                if ($enrollment->isDualShift()) {
                    $dualShiftCount++;
                }
            }

            DB::commit();

            $this->info('Migration completed successfully!');
            $this->line("--------------------------------------------------");
            $this->line("Total Students Processed:  {$processed} / {$totalStudents}");
            $this->line("Enrollment Records:        {$enrollmentsCreated}");
            $this->line(" - Morning Enrollments:    {$morningCount}");
            $this->line(" - Evening Enrollments:    {$eveningCount}");
            $this->line("Dual-Shift Students:       " . ($dualShiftCount / 2)); // Each pair counts twice
            $this->line("--------------------------------------------------");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
            return 1;
        }
    }
}
