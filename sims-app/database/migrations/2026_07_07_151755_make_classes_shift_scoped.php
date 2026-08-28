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
        // 1. Add shift_type column to classes table
        if (!Schema::hasColumn('classes', 'shift_type')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('shift_type')->default('morning');
            });
        }

        // 2. Load all academic sessions
        $sessions = DB::table('academic_sessions')->get();

        foreach ($sessions as $session) {
            $shiftType = $session->shift_type;

            if ($shiftType === 'Regular') {
                // If the session is regular, all its classes should be regular shift
                DB::table('classes')
                    ->where('academic_session_id', $session->id)
                    ->update(['shift_type' => 'regular']);
            } else {
                // For other sessions (Dual / Morning & Evening)
                // First, mark all existing classes in this session as morning
                DB::table('classes')
                    ->where('academic_session_id', $session->id)
                    ->update(['shift_type' => 'morning']);

                // Find all morning classes in this session
                $morningClasses = DB::table('classes')
                    ->where('academic_session_id', $session->id)
                    ->where('shift_type', 'morning')
                    ->get();

                $classMapping = []; // morning_class_id => evening_class_id
                $subjectMapping = []; // morning_subject_id => evening_subject_id

                // Duplicate classes for evening shift
                foreach ($morningClasses as $class) {
                    $eveningClassId = DB::table('classes')->insertGetId([
                        'name' => $class->name,
                        'numeric_value' => $class->numeric_value,
                        'academic_session_id' => $class->academic_session_id,
                        'shift_type' => 'evening',
                        'next_class_id' => null, // Will update in the next loop
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $classMapping[$class->id] = $eveningClassId;

                    // Duplicate subjects for this class
                    $subjects = DB::table('subjects')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($subjects as $subject) {
                        $eveningSubjectId = DB::table('subjects')->insertGetId([
                            'class_id' => $eveningClassId,
                            'name' => $subject->name,
                            'code' => $subject->code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $subjectMapping[$subject->id] = $eveningSubjectId;
                    }

                    // Duplicate timetables for evening class
                    $timetables = DB::table('timetables')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($timetables as $tt) {
                        $eveningSubId = $subjectMapping[$tt->subject_id] ?? $tt->subject_id;
                        DB::table('timetables')->insert([
                            'class_id' => $eveningClassId,
                            'subject_id' => $eveningSubId,
                            'day' => $tt->day,
                            'start_time' => $tt->start_time,
                            'end_time' => $tt->end_time,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Duplicate subject allocations
                    $allocations = DB::table('subject_allocations')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($allocations as $alloc) {
                        $eveningSubId = $subjectMapping[$alloc->subject_id] ?? $alloc->subject_id;
                        DB::table('subject_allocations')->insert([
                            'class_id' => $eveningClassId,
                            'subject_id' => $eveningSubId,
                            'user_id' => $alloc->user_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Duplicate fee structures and update shift_type
                    $feeStructures = DB::table('fee_structures')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($feeStructures as $fs) {
                        if ($fs->shift_type === 'evening') {
                            DB::table('fee_structures')
                                ->where('id', $fs->id)
                                ->update(['class_id' => $eveningClassId]);
                        } elseif ($fs->shift_type === 'both') {
                            // Update the existing one to morning
                            DB::table('fee_structures')
                                ->where('id', $fs->id)
                                ->update(['shift_type' => 'morning']);

                            // Create a new one for evening class
                            DB::table('fee_structures')->insert([
                                'class_id' => $eveningClassId,
                                'fee_head_id' => $fs->fee_head_id,
                                'academic_session_id' => $fs->academic_session_id,
                                'subject_id' => $fs->subject_id ? ($subjectMapping[$fs->subject_id] ?? null) : null,
                                'amount' => $fs->amount,
                                'cycle' => $fs->cycle,
                                'custom_due_date' => $fs->custom_due_date,
                                'effective_from' => $fs->effective_from,
                                'effective_to' => $fs->effective_to,
                                'is_active' => $fs->is_active,
                                'shift_type' => 'evening',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Duplicate marks configs
                    $marksConfigs = DB::table('marks_configs')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($marksConfigs as $mc) {
                        $eveningSubId = $mc->subject_id ? ($subjectMapping[$mc->subject_id] ?? null) : null;
                        DB::table('marks_configs')->insert([
                            'class_id' => $eveningClassId,
                            'subject_id' => $eveningSubId,
                            'academic_session_id' => $mc->academic_session_id,
                            'quiz_weight' => $mc->quiz_weight,
                            'assignment_weight' => $mc->assignment_weight,
                            'exam_weight' => $mc->exam_weight,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Duplicate teacher class access
                    $accesses = DB::table('user_class_access')
                        ->where('class_id', $class->id)
                        ->get();

                    foreach ($accesses as $acc) {
                        DB::table('user_class_access')->insert([
                            'user_id' => $acc->user_id,
                            'class_id' => $eveningClassId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Get all evening students in this morning class (so we can update enrollments, fee_records, etc.)
                    $eveningEnrollments = DB::table('enrollments')
                        ->where('academic_session_id', $session->id)
                        ->where('shift_type', 'evening')
                        ->where('class_id', $class->id)
                        ->get();

                    $eveningStudentIds = $eveningEnrollments->pluck('student_id')->toArray();

                    // Update enrollments
                    DB::table('enrollments')
                        ->where('academic_session_id', $session->id)
                        ->where('shift_type', 'evening')
                        ->where('class_id', $class->id)
                        ->update(['class_id' => $eveningClassId]);

                    // Update promoted_to_class_id in enrollments
                    DB::table('enrollments')
                        ->where('academic_session_id', $session->id)
                        ->where('shift_type', 'evening')
                        ->where('promoted_to_class_id', $class->id)
                        ->update(['promoted_to_class_id' => $eveningClassId]);

                    // Update fee records
                    if (!empty($eveningStudentIds)) {
                        DB::table('fee_records')
                            ->where('class_id', $class->id)
                            ->whereIn('student_id', $eveningStudentIds)
                            ->update(['class_id' => $eveningClassId]);
                    }

                    // Update student_subject (pivot for electives)
                    if (!empty($eveningStudentIds)) {
                        $studentSubjects = DB::table('student_subject')
                            ->whereIn('student_id', $eveningStudentIds)
                            ->get();

                        foreach ($studentSubjects as $ss) {
                            if (isset($subjectMapping[$ss->subject_id])) {
                                DB::table('student_subject')
                                    ->where('student_id', $ss->student_id)
                                    ->where('subject_id', $ss->subject_id)
                                    ->update(['subject_id' => $subjectMapping[$ss->subject_id]]);
                            }
                        }

                        // Update exam_marks
                        $examMarks = DB::table('exam_marks')
                            ->whereIn('student_id', $eveningStudentIds)
                            ->get();

                        foreach ($examMarks as $em) {
                            if (isset($subjectMapping[$em->subject_id])) {
                                DB::table('exam_marks')
                                    ->where('id', $em->id)
                                    ->update(['subject_id' => $subjectMapping[$em->subject_id]]);
                            }
                        }
                    }
                }

                // Update next_class_id for evening classes
                foreach ($morningClasses as $class) {
                    if ($class->next_class_id && isset($classMapping[$class->id]) && isset($classMapping[$class->next_class_id])) {
                        DB::table('classes')
                            ->where('id', $classMapping[$class->id])
                            ->update(['next_class_id' => $classMapping[$class->next_class_id]]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we can find all evening classes, map everything back to morning, and delete evening classes.
        $eveningClasses = DB::table('classes')->where('shift_type', 'evening')->get();

        foreach ($eveningClasses as $eveningClass) {
            // Find morning class in the same session with same name
            $morningClass = DB::table('classes')
                ->where('academic_session_id', $eveningClass->academic_session_id)
                ->where('name', $eveningClass->name)
                ->where('shift_type', 'morning')
                ->first();

            if ($morningClass) {
                // Revert enrollments
                DB::table('enrollments')
                    ->where('class_id', $eveningClass->id)
                    ->update(['class_id' => $morningClass->id]);

                DB::table('enrollments')
                    ->where('promoted_to_class_id', $eveningClass->id)
                    ->update(['promoted_to_class_id' => $morningClass->id]);

                // Revert fee records
                DB::table('fee_records')
                    ->where('class_id', $eveningClass->id)
                    ->update(['class_id' => $morningClass->id]);

                // Revert fee structures class_id
                DB::table('fee_structures')
                    ->where('class_id', $eveningClass->id)
                    ->update(['class_id' => $morningClass->id]);

                // Find evening subjects mapping back to morning
                $eveningSubjects = DB::table('subjects')
                    ->where('class_id', $eveningClass->id)
                    ->get();

                foreach ($eveningSubjects as $eveningSub) {
                    $morningSub = DB::table('subjects')
                        ->where('class_id', $morningClass->id)
                        ->where('name', $eveningSub->name)
                        ->first();

                    if ($morningSub) {
                        // Revert student_subject
                        DB::table('student_subject')
                            ->where('subject_id', $eveningSub->id)
                            ->update(['subject_id' => $morningSub->id]);

                        // Revert exam_marks
                        DB::table('exam_marks')
                            ->where('subject_id', $eveningSub->id)
                            ->update(['subject_id' => $morningSub->id]);
                    }
                }
            }

            // Delete evening subjects, timetables, allocations, configs, accesses, and classes
            DB::table('subjects')->where('class_id', $eveningClass->id)->delete();
            DB::table('timetables')->where('class_id', $eveningClass->id)->delete();
            DB::table('subject_allocations')->where('class_id', $eveningClass->id)->delete();
            DB::table('marks_configs')->where('class_id', $eveningClass->id)->delete();
            DB::table('classes')->where('id', $eveningClass->id)->delete();
        }

        // Revert shift_type to default/drop column
        if (Schema::hasColumn('classes', 'shift_type')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('shift_type');
            });
        }
    }
};
