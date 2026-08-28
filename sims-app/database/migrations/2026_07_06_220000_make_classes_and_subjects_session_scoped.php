<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add academic_session_id to classes table
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->cascadeOnDelete();
        });

        // 2. Fetch all mappings from session_classes
        $sessionClasses = DB::table('session_classes')->orderBy('academic_session_id', 'asc')->get();

        $processedClasses = []; // original_class_id => true if already assigned to its first session
        $classSessionMapping = []; // [original_class_id][session_id] => final_class_id
        $subjectMapping = []; // [original_subject_id][session_id] => final_subject_id

        foreach ($sessionClasses as $sc) {
            $originalClassId = $sc->class_id;
            $sessionId = $sc->academic_session_id;

            if (!isset($processedClasses[$originalClassId])) {
                // Update the original class with this session ID
                DB::table('classes')->where('id', $originalClassId)->update([
                    'academic_session_id' => $sessionId,
                ]);
                $processedClasses[$originalClassId] = true;
                $classSessionMapping[$originalClassId][$sessionId] = $originalClassId;

                // Subjects of the original class belong to this session's class
                $subjects = DB::table('subjects')->where('class_id', $originalClassId)->get();
                foreach ($subjects as $sub) {
                    $subjectMapping[$sub->id][$sessionId] = $sub->id;
                }
            } else {
                // Duplicate the class for the new session
                $originalClass = DB::table('classes')->where('id', $originalClassId)->first();
                if ($originalClass) {
                    $newClassId = DB::table('classes')->insertGetId([
                        'name' => $originalClass->name,
                        'numeric_value' => $originalClass->numeric_value,
                        'next_class_id' => $originalClass->next_class_id,
                        'academic_session_id' => $sessionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $classSessionMapping[$originalClassId][$sessionId] = $newClassId;

                    // Duplicate the subjects of the original class for the new class
                    $sessionSubjects = DB::table('session_subjects')
                        ->where('class_id', $originalClassId)
                        ->where('academic_session_id', $sessionId)
                        ->pluck('subject_id');

                    $subjectsToDuplicate = DB::table('subjects')
                        ->where('class_id', $originalClassId)
                        ->whereIn('id', $sessionSubjects)
                        ->get();

                    foreach ($subjectsToDuplicate as $sub) {
                        $newSubId = DB::table('subjects')->insertGetId([
                            'class_id' => $newClassId,
                            'name' => $sub->name,
                            'code' => $sub->code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $subjectMapping[$sub->id][$sessionId] = $newSubId;
                    }
                }
            }
        }

        // 3. For any class that was not in session_classes (orphan classes), assign active session or first session as fallback
        $activeSession = DB::table('academic_sessions')->where('is_active', true)->first();
        $fallbackSessionId = $activeSession ? $activeSession->id : (DB::table('academic_sessions')->first()?->id);
        
        if ($fallbackSessionId) {
            DB::table('classes')->whereNull('academic_session_id')->update([
                'academic_session_id' => $fallbackSessionId,
            ]);
        }

        // 4. Update the next_class_id mapping for all classes
        $allClasses = DB::table('classes')->get();
        foreach ($allClasses as $cls) {
            if ($cls->next_class_id) {
                $sessId = $cls->academic_session_id;
                $origNextClassId = $cls->next_class_id;
                if (isset($classSessionMapping[$origNextClassId][$sessId])) {
                    $newNextClassId = $classSessionMapping[$origNextClassId][$sessId];
                    DB::table('classes')->where('id', $cls->id)->update([
                        'next_class_id' => $newNextClassId,
                    ]);
                }
            }
        }

        // 5. Update foreign keys in all other tables
        // A. enrollments
        $enrollments = DB::table('enrollments')->get();
        foreach ($enrollments as $enrollment) {
            $sessId = $enrollment->academic_session_id;
            $classId = $enrollment->class_id;
            $promotedClassId = $enrollment->promoted_to_class_id;

            $updateData = [];
            if (isset($classSessionMapping[$classId][$sessId])) {
                $updateData['class_id'] = $classSessionMapping[$classId][$sessId];
            }
            if ($promotedClassId && isset($classSessionMapping[$promotedClassId][$sessId])) {
                $updateData['promoted_to_class_id'] = $classSessionMapping[$promotedClassId][$sessId];
            }

            if (!empty($updateData)) {
                DB::table('enrollments')->where('id', $enrollment->id)->update($updateData);
            }
        }

        // B. subject_allocations
        $allocations = DB::table('subject_allocations')
            ->select('subject_allocations.*', 'session_classes.academic_session_id')
            ->join('session_classes', 'subject_allocations.class_id', '=', 'session_classes.class_id')
            ->get();

        foreach ($allocations as $alloc) {
            $sessId = $alloc->academic_session_id;
            $classId = $alloc->class_id;
            $subjectId = $alloc->subject_id;

            $newClassId = $classSessionMapping[$classId][$sessId] ?? null;
            $newSubjectId = $subjectMapping[$subjectId][$sessId] ?? null;

            if ($newClassId || $newSubjectId) {
                $targetClassId = $newClassId ?? $classId;
                $targetSubjectId = $newSubjectId ?? $subjectId;

                $exists = DB::table('subject_allocations')
                    ->where('class_id', $targetClassId)
                    ->where('subject_id', $targetSubjectId)
                    ->exists();

                if (!$exists) {
                    DB::table('subject_allocations')->where('id', $alloc->id)->update([
                        'class_id' => $targetClassId,
                        'subject_id' => $targetSubjectId,
                    ]);
                } else {
                    DB::table('subject_allocations')->where('id', $alloc->id)->delete();
                }
            }
        }

        // C. timetables
        $timetables = DB::table('timetables')
            ->select('timetables.*', 'session_classes.academic_session_id')
            ->join('session_classes', 'timetables.class_id', '=', 'session_classes.class_id')
            ->get();

        foreach ($timetables as $tt) {
            $sessId = $tt->academic_session_id;
            $classId = $tt->class_id;
            $subjectId = $tt->subject_id;

            $newClassId = $classSessionMapping[$classId][$sessId] ?? null;
            $newSubjectId = $subjectMapping[$subjectId][$sessId] ?? null;

            $updateData = [];
            if ($newClassId) {
                $updateData['class_id'] = $newClassId;
            }
            if ($newSubjectId) {
                $updateData['subject_id'] = $newSubjectId;
            }

            if (!empty($updateData)) {
                DB::table('timetables')->where('id', $tt->id)->update($updateData);
            }
        }

        // D. fee_structures
        $feeStructures = DB::table('fee_structures')->get();
        foreach ($feeStructures as $fs) {
            $sessId = $fs->academic_session_id;
            $classId = $fs->class_id;
            if (isset($classSessionMapping[$classId][$sessId])) {
                DB::table('fee_structures')->where('id', $fs->id)->update([
                    'class_id' => $classSessionMapping[$classId][$sessId],
                ]);
            }
        }

        // E. fee_records
        $feeRecords = DB::table('fee_records')->get();
        foreach ($feeRecords as $fr) {
            $sessId = $fr->academic_session_id;
            $classId = $fr->class_id;
            if (isset($classSessionMapping[$classId][$sessId])) {
                DB::table('fee_records')->where('id', $fr->id)->update([
                    'class_id' => $classSessionMapping[$classId][$sessId],
                ]);
            }
        }

        // F. marks_configs
        $marksConfigs = DB::table('marks_configs')->get();
        foreach ($marksConfigs as $mc) {
            $sessId = $mc->academic_session_id;
            $classId = $mc->class_id;
            $subjectId = $mc->subject_id;

            $updateData = [];
            if (isset($classSessionMapping[$classId][$sessId])) {
                $updateData['class_id'] = $classSessionMapping[$classId][$sessId];
            }
            if ($subjectId && isset($subjectMapping[$subjectId][$sessId])) {
                $updateData['subject_id'] = $subjectMapping[$subjectId][$sessId];
            }

            if (!empty($updateData)) {
                DB::table('marks_configs')->where('id', $mc->id)->update($updateData);
            }
        }

        // G. student_subject
        $studentSubjects = DB::table('student_subject')->get();
        foreach ($studentSubjects as $ss) {
            $subjectId = $ss->subject_id;
            $studentId = $ss->student_id;
            
            $enrollments = DB::table('enrollments')->where('student_id', $studentId)->get();
            foreach ($enrollments as $enrollment) {
                $sessId = $enrollment->academic_session_id;
                if (isset($subjectMapping[$subjectId][$sessId])) {
                    $newSubId = $subjectMapping[$subjectId][$sessId];
                    if ($newSubId !== $subjectId) {
                        $exists = DB::table('student_subject')
                            ->where('student_id', $studentId)
                            ->where('subject_id', $newSubId)
                            ->exists();
                        if (!$exists) {
                            DB::table('student_subject')->insert([
                                'student_id' => $studentId,
                                'subject_id' => $newSubId,
                            ]);
                            DB::table('student_subject')
                                ->where('student_id', $studentId)
                                ->where('subject_id', $subjectId)
                                ->delete();
                        }
                    }
                }
            }
        }

        // H. exam_marks
        $examMarks = DB::table('exam_marks')
            ->select('exam_marks.*', 'exams.academic_session_id')
            ->join('exams', 'exam_marks.exam_id', '=', 'exams.id')
            ->get();

        foreach ($examMarks as $em) {
            $sessId = $em->academic_session_id;
            $subjectId = $em->subject_id;

            if (isset($subjectMapping[$subjectId][$sessId])) {
                $newSubId = $subjectMapping[$subjectId][$sessId];
                if ($newSubId !== $subjectId) {
                    DB::table('exam_marks')->where('id', $em->id)->update([
                        'subject_id' => $newSubId,
                    ]);
                }
            }
        }

        // I. user_class_access
        $classAccesses = DB::table('user_class_access')->get();
        foreach ($classAccesses as $ca) {
            $classId = $ca->class_id;
            $userId = $ca->user_id;

            if (isset($classSessionMapping[$classId])) {
                foreach ($classSessionMapping[$classId] as $sessId => $newClassId) {
                    if ($newClassId !== $classId) {
                        $exists = DB::table('user_class_access')
                            ->where('user_id', $userId)
                            ->where('class_id', $newClassId)
                            ->exists();
                        if (!$exists) {
                            DB::table('user_class_access')->insert([
                                'user_id' => $userId,
                                'class_id' => $newClassId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        // 6. Make academic_session_id on classes table not null and drop pivot tables
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable(false)->change();
        });

        Schema::dropIfExists('session_subjects');
        Schema::dropIfExists('session_classes');
        Schema::dropIfExists('program_subjects');
        Schema::dropIfExists('academic_programs');
    }

    public function down(): void
    {
        Schema::create('session_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('shift_type')->default('Regular');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('session_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();
        });

        $classes = DB::table('classes')->get();
        foreach ($classes as $c) {
            if ($c->academic_session_id) {
                DB::table('session_classes')->insert([
                    'academic_session_id' => $c->academic_session_id,
                    'class_id' => $c->id,
                    'shift_type' => 'Regular',
                    'is_active' => true,
                ]);

                $subjects = DB::table('subjects')->where('class_id', $c->id)->get();
                foreach ($subjects as $s) {
                    DB::table('session_subjects')->insert([
                        'academic_session_id' => $c->academic_session_id,
                        'class_id' => $c->id,
                        'subject_id' => $s->id,
                    ]);
                }
            }
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
