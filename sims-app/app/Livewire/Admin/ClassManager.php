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
    public $showTrash = false;

    // Add new class
    public $name = '';

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

    public function getClassesList()
    {
        // Institutional classrooms are permanent assets
        $query = Classes::query()
            ->where('shift_type', $this->getCurrentShift())
            ->with(['nextClass'])
            ->withCount('subjects');

        if ($this->showTrash) {
            $query->onlyTrashed();
        }

        return $query->orderBy('numeric_value')->orderBy('name')->get();
    }

    public function getCurrentShift()
    {
        return \App\Support\ActiveShift::single($this->selectedSessionId);
    }

    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        $this->renamingClassId = null;
    }

    public function toggleActiveClass($id)
    {
        $this->authorize('classes.manage');
        $class = Classes::findOrFail($id);
        $class->is_active = !$class->is_active;
        $class->save();
        $status = $class->is_active ? 'reactivated' : 'archived';
        session()->flash('message', "Classroom '{$class->name}' {$status} successfully.");
    }

    // -----------------------------------------------------------------------
    // ADD CLASS
    // -----------------------------------------------------------------------
    public function save()
    {
        $this->validate(['name' => 'required|string|max:50']);

        $className = trim($this->name);
        if (!str_starts_with(strtolower($className), 'class ')) {
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

        $numericValue = (int) filter_var($className, FILTER_SANITIZE_NUMBER_INT);
        Classes::create([
            'name' => $className,
            'numeric_value' => $numericValue ?: 0,
            'academic_session_id' => $this->selectedSessionId,
            'shift_type' => $shiftType,
            'is_active' => true,
        ]);

        $this->name = '';
        session()->flash('message', 'Classroom "' . $className . '" added to the school building successfully.');
    }

    // -----------------------------------------------------------------------
    // RENAME CLASS
    // -----------------------------------------------------------------------
    public function startRenameClass($classId, $className)
    {
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
        $this->validate(['renamingClassName' => 'required|string|max:50']);

        $newName = trim($this->renamingClassName);
        if (!str_starts_with(strtolower($newName), 'class ')) {
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
        $numericValue = (int) filter_var($newName, FILTER_SANITIZE_NUMBER_INT);

        $updateData = ['name' => $newName];
        // Protect educational grade level if student records already exist
        if (!$hasEnrollments) {
            $updateData['numeric_value'] = $numericValue ?: 0;
        }

        $class->update($updateData);

        $this->renamingClassId = null;
        $this->renamingClassName = '';
        session()->flash('message', 'Classroom renamed successfully.');
    }

    // -----------------------------------------------------------------------
    // SAFE DELETE & ARCHIVE (protects institutional records)
    // -----------------------------------------------------------------------
    public function confirmDelete($id)
    {
        $class = Classes::withCount(['students', 'timetables'])->findOrFail($id);

        $hasEnrollments = DB::table('enrollments')->where('class_id', $id)->exists();
        $hasFees = DB::table('fee_records')->where('class_id', $id)->exists();
        $hasMarks = DB::table('marks_configs')->where('class_id', $id)->exists();

        if ($hasEnrollments || $hasFees || $hasMarks) {
            session()->flash('error', "Classroom '{$class->name}' cannot be deleted because historical student, exam, or fee records exist. Please archive it instead to keep it inactive.");
            return;
        }

        $this->deletingClassId = $id;
        $this->deletingClassName = $class->name;
        $this->deletingStudentCount = $class->students_count;
        $this->deletingTimetableCount = $class->timetables_count;

        if ($class->students_count === 0 && $class->timetables_count === 0) {
            // No linked data — move to trash directly
            $this->delete();
        } else {
            // Show warning modal
            $this->showDeleteWarning = true;
        }
    }

    public function delete()
    {
        $class = Classes::find($this->deletingClassId);
        if ($class) {
            $hasEnrollments = DB::table('enrollments')->where('class_id', $class->id)->exists();
            $hasFees = DB::table('fee_records')->where('class_id', $class->id)->exists();
            $hasMarks = DB::table('marks_configs')->where('class_id', $class->id)->exists();

            if ($hasEnrollments || $hasFees || $hasMarks) {
                session()->flash('error', "Classroom '{$class->name}' cannot be deleted because historical student, exam, or fee records exist. Please archive it instead.");
                $this->showDeleteWarning = false;
                $this->deletingClassId = null;
                return;
            }

            $class->delete(); // soft delete
        }

        $this->showDeleteWarning = false;
        $this->deletingClassId = null;
        $this->deletingClassName = '';
        $this->deletingStudentCount = 0;
        $this->deletingTimetableCount = 0;
        $this->manageClassId = null;
        session()->flash('message', 'Classroom moved to Trash.');
    }

    public function cancelDelete()
    {
        $this->showDeleteWarning = false;
        $this->deletingClassId = null;
    }

    // -----------------------------------------------------------------------
    // TRASH — RESTORE & PERMANENT DELETE
    // -----------------------------------------------------------------------
    public function restore($id)
    {
        Classes::onlyTrashed()->where('id', $id)->restore();
        session()->flash('message', 'Classroom restored successfully.');
    }

    public function permanentDelete($id)
    {
        $class = Classes::onlyTrashed()->where('id', $id)->first();
        if ($class) {
            // Check if there are any enrollments, fees, or marks linked to this class
            $hasEnrollments = DB::table('enrollments')->where('class_id', $id)->exists();
            $hasFees = DB::table('fee_records')->where('class_id', $id)->exists();
            $hasMarks = DB::table('marks_configs')->where('class_id', $id)->exists();

            if ($hasEnrollments || $hasFees || $hasMarks) {
                session()->flash('error', 'Cannot permanently delete class because historical academic or financial records exist.');
                return;
            }

            // Clean up class program allowances and legacy subjects
            DB::table('class_programs')->where('class_id', $id)->delete();
            DB::table('subjects')->where('class_id', $id)->delete();
            $class->forceDelete();
        }
        session()->flash('message', 'Classroom permanently deleted.');
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
        if (!$this->manageClassId) return;

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
        if (!$this->manageClassId) return;

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
        if (!$this->manageClassId) return;

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
        if (!$this->manageClassId) return;

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
        if (empty($this->selectedSubjectIds) || empty($this->copyTargetClassIds)) {
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
        $allClasses = Classes::where('shift_type', $this->getCurrentShift())
            ->where('is_active', true)
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
