<?php

namespace App\Livewire\Admin;

use App\Models\Classes;
use App\Models\Subject;
use App\Models\Program;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ClassManager extends Component
{
    // Session
    public $selectedSessionId;
    public $academicSessions = [];
    public $canViewSessions = false;

    // Class list settings (not storing collection in public property)
    public $showArchived = false;
    public $showTrash = false; // Kept as alias to showArchived for compatibility

    // Add new class
    public $name = '';
    public $stage = 'auto'; // 'auto', or numeric string e.g. '-3', '-2', '-1', '0', '1', '7', etc.

    // Rename class
    public $renamingClassId = null;
    public $renamingClassName = '';

    // Delete warning modal
    public $showDeleteWarning = false;
    public $deletingClassId = null;
    public $deletingClassName = '';
    public $deletingStudentCount = 0;
    public $deletingTimetableCount = 0;

    // Subject & Program modal
    public $manageClassId = null;
    public $manageClassName = '';
    public $classSubjects = [];
    public $newSubjectName = '';
    public $availablePrograms = [];
    public $classAssignedProgramIds = [];
    public $classDefaultProgramId = null;
    public $modalActiveTab = 'programs';

    // Rename subject
    public $renamingSubjectId = null;
    public $renamingSubjectName = '';

    // Copy subjects
    public $selectedSubjectIds = [];
    public $copyTargetClassIds = [];
    public $showCopyPanel = false;

    public function mount()
    {
        $this->authorize('classes.manage');
        $this->academicSessions = \Illuminate\Support\Facades\DB::table('academic_sessions')->orderBy('start_date', 'desc')->get();
        $this->selectedSessionId = \App\Models\AcademicSession::getActiveSessionId();
        
        $this->canViewSessions = auth()->user()->hasRole('Super Admin') || 
                                 auth()->user()->role === 'admin' || 
                                 auth()->user()->can('classes.view-sessions');

        // Auto-focus add form when navigated from dashboard quick-action
        if (request()->boolean('open_add_modal')) {
            $this->dispatch('focus-class-input');
        }
    }

    public function getIsHistoricalSessionProperty(): bool
    {
        return \App\Models\AcademicSession::isSessionLocked($this->selectedSessionId);
    }

    public function getClassesList()
    {
        $shiftType = $this->getCurrentShift();
        $sessionId = $this->selectedSessionId;
        $session = \App\Models\AcademicSession::find($sessionId);

        if (!$this->isHistoricalSession) {
            // Active current session:
            $query = Classes::query()
                ->where('shift_type', $shiftType)
                ->whereNull('deleted_at')
                ->where(function ($sub) use ($session) {
                    $sub->whereNull('classes.academic_session_id')
                        ->orWhere('classes.academic_session_id', $session?->id)
                        ->orWhereExists(function ($ex) use ($session) {
                            $ex->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('academic_sessions as s_inc')
                                ->whereColumn('s_inc.id', 'classes.academic_session_id')
                                ->where(function ($d) use ($session) {
                                    $d->where('s_inc.start_date', '<', $session?->start_date ?? now())
                                        ->orWhere(function ($eq) use ($session) {
                                            $eq->where('s_inc.start_date', $session?->start_date ?? now())
                                                ->where('s_inc.id', '<=', $session?->id ?? 0);
                                        });
                                });
                        });
                })
                ->with(['nextClass'])
                ->withCount('subjects');

            if ($this->showArchived || $this->showTrash) {
                $query->where('is_active', false);
            } else {
                $query->where('is_active', true);
            }

            return $query->orderBy('numeric_value')->orderBy('name')->get();
        }

        // Concluded historical session:
        if (!$this->showArchived && !$this->showTrash) {
            // Active Classrooms during this historical session
            return Classes::forSession($sessionId, $shiftType)
                ->whereNull('deleted_at')
                ->with(['nextClass'])
                ->withCount('subjects')
                ->orderBy('numeric_value')
                ->orderBy('name')
                ->get();
        }

        // Archived Classrooms during this historical session:
        // Rooms that existed in or before this session AND were archived in or before this session
        return Classes::query()
            ->where('shift_type', $shiftType)
            ->whereNull('deleted_at')
            ->where(function ($inc) use ($session) {
                $inc->whereNull('classes.academic_session_id')
                    ->orWhere('classes.academic_session_id', $session?->id)
                    ->orWhereExists(function ($s1) use ($session) {
                        $s1->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('academic_sessions as s_inc')
                            ->whereColumn('s_inc.id', 'classes.academic_session_id')
                            ->where(function ($d1) use ($session) {
                                $d1->where('s_inc.start_date', '<', $session?->start_date)
                                    ->orWhere(function ($eq1) use ($session) {
                                        $eq1->where('s_inc.start_date', $session?->start_date)
                                            ->where('s_inc.id', '<=', $session?->id);
                                    });
                            });
                    });
            })
            ->where(function ($arch) use ($session) {
                $arch->where('classes.archived_academic_session_id', $session?->id)
                    ->orWhereExists(function ($s2) use ($session) {
                        $s2->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('academic_sessions as s_arch')
                            ->whereColumn('s_arch.id', 'classes.archived_academic_session_id')
                            ->where(function ($d2) use ($session) {
                                $d2->where('s_arch.start_date', '<', $session?->start_date)
                                    ->orWhere(function ($eq2) use ($session) {
                                        $eq2->where('s_arch.start_date', $session?->start_date)
                                            ->where('s_arch.id', '<=', $session?->id);
                                    });
                            });
                    });
            })
            ->with(['nextClass'])
            ->withCount('subjects')
            ->orderBy('numeric_value')
            ->orderBy('name')
            ->get();
    }

    public function getArchivedCountProperty(): int
    {
        $shiftType = $this->getCurrentShift();
        $sessionId = $this->selectedSessionId;
        $session = \App\Models\AcademicSession::find($sessionId);

        if (!$this->isHistoricalSession) {
            return Classes::where('shift_type', $shiftType)
                ->where('is_active', false)
                ->whereNull('deleted_at')
                ->where(function ($sub) use ($session) {
                    $sub->whereNull('classes.academic_session_id')
                        ->orWhere('classes.academic_session_id', $session?->id)
                        ->orWhereExists(function ($ex) use ($session) {
                            $ex->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('academic_sessions as s_inc')
                                ->whereColumn('s_inc.id', 'classes.academic_session_id')
                                ->where(function ($d) use ($session) {
                                    $d->where('s_inc.start_date', '<', $session?->start_date ?? now())
                                        ->orWhere(function ($eq) use ($session) {
                                            $eq->where('s_inc.start_date', $session?->start_date ?? now())
                                                ->where('s_inc.id', '<=', $session?->id ?? 0);
                                        });
                                });
                        });
                })
                ->count();
        }

        if (!$session) return 0;

        return Classes::query()
            ->where('shift_type', $shiftType)
            ->whereNull('deleted_at')
            ->where(function ($inc) use ($session) {
                $inc->whereNull('classes.academic_session_id')
                    ->orWhere('classes.academic_session_id', $session->id)
                    ->orWhereExists(function ($s1) use ($session) {
                        $s1->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('academic_sessions as s_inc')
                            ->whereColumn('s_inc.id', 'classes.academic_session_id')
                            ->where(function ($d1) use ($session) {
                                $d1->where('s_inc.start_date', '<', $session->start_date)
                                    ->orWhere(function ($eq1) use ($session) {
                                        $eq1->where('s_inc.start_date', $session->start_date)
                                            ->where('s_inc.id', '<=', $session->id);
                                    });
                            });
                    });
            })
            ->where(function ($arch) use ($session) {
                $arch->where('classes.archived_academic_session_id', $session->id)
                    ->orWhereExists(function ($s2) use ($session) {
                        $s2->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('academic_sessions as s_arch')
                            ->whereColumn('s_arch.id', 'classes.archived_academic_session_id')
                            ->where(function ($d2) use ($session) {
                                $d2->where('s_arch.start_date', '<', $session->start_date)
                                    ->orWhere(function ($eq2) use ($session) {
                                        $eq2->where('s_arch.start_date', $session->start_date)
                                            ->where('s_arch.id', '<=', $session->id);
                                    });
                            });
                    });
            })
            ->count();
    }

    public function getCurrentShift()
    {
        return \App\Support\ActiveShift::single($this->selectedSessionId);
    }

    public function toggleArchived()
    {
        $this->showArchived = !$this->showArchived;
        $this->showTrash = $this->showArchived;
        $this->renamingClassId = null;
    }

    public function toggleTrash()
    {
        $this->toggleArchived();
    }

    public function archiveClass($id)
    {
        $this->authorize('classes.manage');
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be archived in a concluded session.');
            return;
        }

        $class = Classes::findOrFail($id);
        $class->is_active = false;
        $class->archived_academic_session_id = $this->selectedSessionId ?: \App\Models\AcademicSession::getActiveSessionId();
        $class->save();
        session()->flash('message', "Classroom '{$class->name}' moved to Archived Classrooms.");
    }

    public function restoreClass($id)
    {
        $this->authorize('classes.manage');
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be restored in a concluded session.');
            return;
        }

        $class = Classes::findOrFail($id);
        $class->is_active = true;
        $class->archived_academic_session_id = null;
        $class->save();
        session()->flash('message', "Classroom '{$class->name}' activated and restored to the school building.");
    }

    public function toggleActiveClass($id)
    {
        $this->authorize('classes.manage');
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be modified in a concluded session.');
            return;
        }

        $class = Classes::findOrFail($id);
        $class->is_active = !$class->is_active;
        $class->archived_academic_session_id = $class->is_active ? null : ($this->selectedSessionId ?: \App\Models\AcademicSession::getActiveSessionId());
        $class->save();
        $status = $class->is_active ? 'reactivated' : 'archived';
        session()->flash('message', "Classroom '{$class->name}' {$status} successfully.");
    }

    // -----------------------------------------------------------------------
    // ADD CLASS
    // -----------------------------------------------------------------------
    public function save()
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'New classrooms cannot be created in a concluded academic session. Please switch to the active academic session.');
            return;
        }

        $this->validate(['name' => 'required|string|max:50']);

        $className = trim($this->name);
        // If pure number (e.g. "7" or "10"), format as "Class 7"
        if (is_numeric($className)) {
            $className = 'Class ' . $className;
        }

        $shiftType = $this->getCurrentShift();
        $classExists = Classes::where('name', $className)
            ->where('shift_type', $shiftType)
            ->exists();

        if ($classExists) {
            $this->addError('name', 'This classroom already exists in the selected shift.');
            return;
        }

        // Determine numeric_value: use manual stage selection if chosen, otherwise auto-detect
        if ($this->stage !== 'auto' && is_numeric($this->stage)) {
            $numericValue = (int) $this->stage;
        } else {
            $numericValue = Classes::detectNumericValue($className);
        }

        Classes::create([
            'name' => $className,
            'numeric_value' => $numericValue,
            'academic_session_id' => $this->selectedSessionId ?: \App\Models\AcademicSession::getActiveSessionId(),
            'shift_type' => $shiftType,
            'is_active' => true,
        ]);

        $this->name = '';
        $this->stage = 'auto';
        session()->flash('message', 'Classroom "' . $className . '" added to the school building successfully.');
    }

    // -----------------------------------------------------------------------
    // RENAME CLASS
    // -----------------------------------------------------------------------
    public function startRenameClass($classId, $className)
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be renamed in a concluded session.');
            return;
        }

        $this->renamingClassId = $classId;
        $this->renamingClassName = $className;
    }

    public function cancelRenameClass()
    {
        $this->renamingClassId = null;
        $this->renamingClassName = '';
    }

    public function saveClassName()
    {
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be renamed in a concluded session.');
            return;
        }

        $this->validate(['renamingClassName' => 'required|string|max:50']);

        $newName = trim($this->renamingClassName);
        if (is_numeric($newName)) {
            $newName = 'Class ' . $newName;
        }

        $class = Classes::findOrFail($this->renamingClassId);

        // Check duplicate within the same shift
        $exists = Classes::where('name', $newName)
            ->where('shift_type', $class->shift_type)
            ->where('id', '!=', $this->renamingClassId)
            ->exists();

        if ($exists) {
            $this->addError('renamingClassName', 'A classroom with this name already exists in this shift.');
            return;
        }

        $hasEnrollments = DB::table('enrollments')->where('class_id', $class->id)->exists();
        $numericValue = Classes::detectNumericValue($newName);

        $updateData = ['name' => $newName];
        // Protect educational grade level if student records already exist
        if (!$hasEnrollments) {
            $updateData['numeric_value'] = $numericValue;
        }

        $class->update($updateData);

        $this->renamingClassId = null;
        $this->renamingClassName = '';
        session()->flash('message', 'Classroom renamed successfully.');
    }

    // -----------------------------------------------------------------------
    // DIRECT DELETE & ARCHIVE (Eliminates legacy two-step trash dance)
    // -----------------------------------------------------------------------
    public function deleteClassDirectly($id)
    {
        $this->authorize('classes.manage');
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Classrooms cannot be deleted in a concluded session.');
            $this->showDeleteWarning = false;
            $this->deletingClassId = null;
            return;
        }

        $class = Classes::find($id) ?? Classes::withTrashed()->find($id);
        if (!$class) return;

        $hasEnrollments = DB::table('enrollments')->where('class_id', $class->id)->exists();
        $hasFees = DB::table('fee_records')->where('class_id', $class->id)->exists();
        $hasMarks = DB::table('marks_configs')->where('class_id', $class->id)->exists();

        if ($hasEnrollments || $hasFees || $hasMarks) {
            session()->flash('error', "Classroom '{$class->name}' cannot be deleted because historical student, exam, or fee records exist. Please archive it instead to keep it inactive.");
            $this->showDeleteWarning = false;
            $this->deletingClassId = null;
            return;
        }

        // Clean up class program allowances and legacy subjects
        DB::table('class_programs')->where('class_id', $class->id)->delete();
        DB::table('subjects')->where('class_id', $class->id)->delete();
        DB::table('timetables')->where('class_id', $class->id)->delete();
        $class->forceDelete();

        $this->showDeleteWarning = false;
        $this->deletingClassId = null;
        $this->deletingClassName = '';
        $this->deletingStudentCount = 0;
        $this->deletingTimetableCount = 0;
        $this->manageClassId = null;
        session()->flash('message', "Mistaken classroom '{$class->name}' permanently deleted.");
    }

    public function confirmDelete($id)
    {
        $this->deleteClassDirectly($id);
    }

    public function delete()
    {
        if ($this->deletingClassId) {
            $this->deleteClassDirectly($this->deletingClassId);
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteWarning = false;
        $this->deletingClassId = null;
    }

    public function restore($id)
    {
        $this->restoreClass($id);
    }

    public function permanentDelete($id)
    {
        $this->deleteClassDirectly($id);
    }

    // -----------------------------------------------------------------------
    // SUBJECT MODAL
    // -----------------------------------------------------------------------
    public function openSubjectModal($classId, $className)
    {
        $this->manageClassId = $classId;
        $this->manageClassName = $className;
        $this->renamingSubjectId = null;
        $this->renamingSubjectName = '';
        $this->selectedSubjectIds = [];
        $this->copyTargetClassIds = [];
        $this->showCopyPanel = false;
        $this->modalActiveTab = 'programs';

        $this->availablePrograms = Program::active()->with('subjects')->get();
        $assigned = DB::table('class_programs')->where('class_id', $classId)->get();
        $this->classAssignedProgramIds = $assigned->pluck('program_id')->map(fn($id) => (int) $id)->toArray();
        $defaultRow = $assigned->firstWhere('is_default', 1);
        $this->classDefaultProgramId = $defaultRow ? (int) $defaultRow->program_id : ($this->classAssignedProgramIds[0] ?? null);

        $this->loadSubjects($classId);
    }

    public function toggleClassTrack($programId)
    {
        if (!$this->manageClassId || $this->isHistoricalSession) return;

        $programId = (int) $programId;
        $exists = DB::table('class_programs')
            ->where('class_id', $this->manageClassId)
            ->where('program_id', $programId)
            ->exists();

        if ($exists) {
            DB::table('class_programs')
                ->where('class_id', $this->manageClassId)
                ->where('program_id', $programId)
                ->delete();

            $this->classAssignedProgramIds = array_diff($this->classAssignedProgramIds, [$programId]);

            if ($this->classDefaultProgramId == $programId) {
                $this->classDefaultProgramId = reset($this->classAssignedProgramIds) ?: null;
                if ($this->classDefaultProgramId) {
                    DB::table('class_programs')
                        ->where('class_id', $this->manageClassId)
                        ->where('program_id', $this->classDefaultProgramId)
                        ->update(['is_default' => true]);
                }
            }
        } else {
            $isDefault = empty($this->classAssignedProgramIds);
            DB::table('class_programs')->insert([
                'class_id' => $this->manageClassId,
                'program_id' => $programId,
                'is_default' => $isDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->classAssignedProgramIds[] = $programId;
            if ($isDefault) {
                $this->classDefaultProgramId = $programId;
            }
        }

        $this->loadSubjects($this->manageClassId);
    }

    public function updatedClassAssignedProgramIds($newIds)
    {
        if (!$this->manageClassId || $this->isHistoricalSession) return;

        $newIds = array_map('intval', (array) $newIds);
        $currentIds = DB::table('class_programs')
            ->where('class_id', $this->manageClassId)
            ->pluck('program_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Removed
        $toRemove = array_diff($currentIds, $newIds);
        if (!empty($toRemove)) {
            DB::table('class_programs')
                ->where('class_id', $this->manageClassId)
                ->whereIn('program_id', $toRemove)
                ->delete();
        }

        // Added
        $toAdd = array_diff($newIds, $currentIds);
        foreach ($toAdd as $addId) {
            DB::table('class_programs')->insert([
                'class_id' => $this->manageClassId,
                'program_id' => $addId,
                'is_default' => empty($currentIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Fix default program if previous default was removed
        if ($this->classDefaultProgramId && !in_array($this->classDefaultProgramId, $newIds)) {
            $this->classDefaultProgramId = reset($newIds) ?: null;
            if ($this->classDefaultProgramId) {
                DB::table('class_programs')
                    ->where('class_id', $this->manageClassId)
                    ->where('program_id', $this->classDefaultProgramId)
                    ->update(['is_default' => true]);
            }
        }

        $this->classAssignedProgramIds = $newIds;
        $this->loadSubjects($this->manageClassId);
    }

    public function setClassDefaultTrack($programId)
    {
        if (!$this->manageClassId || $this->isHistoricalSession) return;

        $programId = (int) $programId;
        DB::table('class_programs')->where('class_id', $this->manageClassId)->update(['is_default' => false]);
        DB::table('class_programs')->where('class_id', $this->manageClassId)->where('program_id', $programId)->update(['is_default' => true]);
        $this->classDefaultProgramId = $programId;
        $this->loadSubjects($this->manageClassId);
    }

    public function loadSubjects($classId)
    {
        $this->classSubjects = Subject::forClass($classId)->get();
    }

    public function addSubject()
    {
        if (!$this->manageClassId || $this->isHistoricalSession) return;

        $this->validate(['newSubjectName' => 'required|string|max:255']);

        $name = trim($this->newSubjectName);
        $subject = Subject::firstOrCreate(
            ['name' => $name],
            [
                'code' => Subject::formatAbbreviation($name),
                'category' => 'General',
                'class_id' => null,
                'is_active' => true,
            ]
        );

        $class = Classes::find($this->manageClassId);
        if ($class && $class->programs()->exists()) {
            foreach ($class->programs as $prog) {
                if (!$prog->subjects()->where('subjects.id', $subject->id)->exists()) {
                    $prog->subjects()->attach($subject->id, [
                        'subject_type' => 'compulsory',
                        'sort_order' => $prog->subjects()->count() + 1,
                    ]);
                }
            }
        } elseif ($class) {
            $defaultProg = Program::firstOrCreate(
                ['code' => 'GEN-' . $class->id],
                ['name' => $class->name . ' General Program', 'category' => 'General', 'is_active' => true]
            );
            if (!$class->programs()->where('programs.id', $defaultProg->id)->exists()) {
                $class->programs()->attach($defaultProg->id, ['is_default' => true]);
            }
            if (!$defaultProg->subjects()->where('subjects.id', $subject->id)->exists()) {
                $defaultProg->subjects()->attach($subject->id, [
                    'subject_type' => 'compulsory',
                    'sort_order' => $defaultProg->subjects()->count() + 1,
                ]);
            }
        }

        $this->newSubjectName = '';
        $this->loadSubjects($this->manageClassId);
    }

    // -----------------------------------------------------------------------
    // RENAME SUBJECT
    // -----------------------------------------------------------------------
    public function startRenameSubject($subjectId, $subjectName)
    {
        $this->renamingSubjectId = $subjectId;
        $this->renamingSubjectName = $subjectName;
    }

    public function cancelRenameSubject()
    {
        $this->renamingSubjectId = null;
        $this->renamingSubjectName = '';
    }

    public function saveSubjectName()
    {
        if ($this->isHistoricalSession) return;
        $this->validate(['renamingSubjectName' => 'required|string|max:255']);

        $words = explode(' ', trim($this->renamingSubjectName));
        $code = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        if (strlen($code) < 2) {
            $code = strtoupper(substr($this->renamingSubjectName, 0, 3));
        }

        Subject::where('id', $this->renamingSubjectId)->update([
            'name' => $this->renamingSubjectName,
            'code' => $code,
        ]);

        $this->renamingSubjectId = null;
        $this->renamingSubjectName = '';
        $this->loadSubjects($this->manageClassId);
    }

    public function deleteSubject($subjectId)
    {
        if ($this->isHistoricalSession) return;
        $class = Classes::find($this->manageClassId);
        if ($class) {
            foreach ($class->programs as $prog) {
                $prog->subjects()->detach($subjectId);
            }
        }
        // Remove legacy row only if specifically tied to this class
        Subject::where('id', $subjectId)->where('class_id', $this->manageClassId)->delete();

        // Remove from selection if it was selected
        $this->selectedSubjectIds = array_diff($this->selectedSubjectIds, [$subjectId]);
        $this->loadSubjects($this->manageClassId);
    }

    public function updatedSelectedSubjectIds()
    {
        $this->showCopyPanel = count($this->selectedSubjectIds) > 0;
        if (!$this->showCopyPanel) {
            $this->copyTargetClassIds = [];
        }
    }

    public function copySubjectsToClasses()
    {
        if ($this->isHistoricalSession || empty($this->selectedSubjectIds) || empty($this->copyTargetClassIds)) {
            return;
        }

        $subjectsToCopy = Subject::whereIn('id', $this->selectedSubjectIds)->get();
        $copiedCount = 0;
        $skippedCount = 0;

        foreach ($this->copyTargetClassIds as $targetClassId) {
            if ($targetClassId == $this->manageClassId) continue; // skip source class

            $targetClass = Classes::find($targetClassId);
            if (!$targetClass) continue;

            $targetProg = $targetClass->programs()->first();
            if (!$targetProg) {
                $targetProg = Program::firstOrCreate(
                    ['code' => 'GEN-' . $targetClassId],
                    ['name' => $targetClass->name . ' General Program', 'category' => 'General', 'is_active' => true]
                );
                $targetClass->programs()->attach($targetProg->id, ['is_default' => true]);
            }

            foreach ($subjectsToCopy as $subject) {
                if (!$targetProg->subjects()->where('subjects.id', $subject->id)->exists()) {
                    $targetProg->subjects()->attach($subject->id, [
                        'subject_type' => 'compulsory',
                        'sort_order' => $targetProg->subjects()->count() + 1,
                    ]);
                    $copiedCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        $msg = "Copied {$copiedCount} subject(s) successfully.";
        if ($skippedCount > 0) {
            $msg .= " {$skippedCount} already existed and were skipped.";
        }

        $this->selectedSubjectIds = [];
        $this->copyTargetClassIds = [];
        $this->showCopyPanel = false;
        session()->flash('message', $msg);
    }

    public function closeModal()
    {
        $this->manageClassId = null;
        $this->manageClassName = '';
        $this->newSubjectName = '';
        $this->renamingSubjectId = null;
        $this->renamingSubjectName = '';
        $this->selectedSubjectIds = [];
        $this->copyTargetClassIds = [];
        $this->showCopyPanel = false;
    }

    public function updateNextClass($classId, $nextClassId)
    {
        $this->authorize('classes.manage');
        if ($this->isHistoricalSession) {
            session()->flash('error', 'Historical academic sessions are sealed. Progression ladder cannot be altered in a concluded session.');
            return;
        }

        Classes::where('id', $classId)
            ->update([
                'next_class_id' => $nextClassId ?: null,
            ]);

        session()->flash('message', 'Permanent progression path ("Promotes To") updated successfully.');
    }

    public function render()
    {
        $layout = request()->is('teacher/*')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        // All active classes in the current shift for "Promotes To" progression ladder
        $allClasses = Classes::forSession($this->selectedSessionId, $this->getCurrentShift())
            ->whereNull('deleted_at')
            ->orderBy('numeric_value')
            ->orderBy('name')
            ->get();

        $hasFinalExam = \App\Models\Exam::where('academic_session_id', $this->selectedSessionId)
            ->where('type', 'Final-Term')
            ->exists();

        return view('livewire.admin.class-manager', [
            'classes' => $this->getClassesList(),
            'allClasses' => $allClasses,
            'hasFinalExam' => $hasFinalExam,
            'hasNextSession' => true,
        ])->layout($layout, ['title' => 'Class Management']);
    }
}
