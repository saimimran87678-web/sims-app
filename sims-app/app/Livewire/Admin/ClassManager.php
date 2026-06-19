<?php

namespace App\Livewire\Admin;

use App\Models\Classes;
use App\Models\Subject;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ClassManager extends Component
{
    // Session
    public $selectedSessionId;
    public $academicSessions = [];

    // Class list
    public $classes;
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

    // Subject modal
    public $manageClassId = null;
    public $manageClassName = '';
    public $classSubjects = [];
    public $newSubjectName = '';

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
        $this->loadClasses();
    }

    public function loadClasses()
    {
        if (!$this->selectedSessionId) {
            $this->classes = collect();
            return;
        }

        // Enforce scope for non-privileged users
        if (!auth()->user()->can('classes.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            if ($this->selectedSessionId != $activeSessionId) {
                $this->selectedSessionId = $activeSessionId;
            }
        }

        $query = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->withCount('subjects');

        if ($this->showTrash) {
            $query->onlyTrashed();
        }

        $this->classes = $query->orderBy('numeric_value')->get();
    }

    public function updatedSelectedSessionId()
    {
        $this->loadClasses();
    }

    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        $this->renamingClassId = null;
        $this->loadClasses();
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

        $exists = Classes::withoutGlobalScope('active_session')
            ->where('name', $className)
            ->where('academic_session_id', $this->selectedSessionId)
            ->exists();

        if ($exists) {
            $this->addError('name', 'This class already exists in the selected session.');
            return;
        }

        $numericValue = (int) filter_var($className, FILTER_SANITIZE_NUMBER_INT);

        Classes::create([
            'name' => $className,
            'numeric_value' => $numericValue ?: 0,
            'academic_session_id' => $this->selectedSessionId,
        ]);

        $this->name = '';
        $this->loadClasses();
        session()->flash('message', 'Class "' . $className . '" added successfully.');
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

        // Check duplicate in session (exclude current)
        $exists = Classes::withoutGlobalScope('active_session')
            ->where('name', $newName)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('id', '!=', $this->renamingClassId)
            ->exists();

        if ($exists) {
            $this->addError('renamingClassName', 'A class with this name already exists.');
            return;
        }

        $numericValue = (int) filter_var($newName, FILTER_SANITIZE_NUMBER_INT);

        Classes::withoutGlobalScope('active_session')
            ->where('id', $this->renamingClassId)
            ->update([
                'name' => $newName,
                'numeric_value' => $numericValue ?: 0,
            ]);

        $this->renamingClassId = null;
        $this->renamingClassName = '';
        $this->loadClasses();
        session()->flash('message', 'Class renamed successfully.');
    }

    // -----------------------------------------------------------------------
    // SAFE DELETE (with dependency warning)
    // -----------------------------------------------------------------------
    public function confirmDelete($id)
    {
        $class = Classes::withoutGlobalScope('active_session')->withCount(['students', 'timetables'])->findOrFail($id);

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
        Classes::withoutGlobalScope('active_session')
            ->where('id', $this->deletingClassId)
            ->first()
            ->delete(); // soft delete

        $this->showDeleteWarning = false;
        $this->deletingClassId = null;
        $this->deletingClassName = '';
        $this->deletingStudentCount = 0;
        $this->deletingTimetableCount = 0;
        $this->manageClassId = null;
        $this->loadClasses();
        session()->flash('message', 'Class moved to Trash.');
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
        Classes::withoutGlobalScope('active_session')->onlyTrashed()->where('id', $id)->restore();
        $this->loadClasses();
        session()->flash('message', 'Class restored successfully.');
    }

    public function permanentDelete($id)
    {
        $class = Classes::withoutGlobalScope('active_session')->onlyTrashed()->where('id', $id)->first();
        if ($class) {
            // Hard-delete subjects too
            DB::table('subjects')->where('class_id', $id)->delete();
            $class->forceDelete();
        }
        $this->loadClasses();
        session()->flash('message', 'Class permanently deleted.');
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
        $this->loadSubjects($classId);
    }

    public function loadSubjects($classId)
    {
        $this->classSubjects = Subject::where('class_id', $classId)->get();
    }

    public function addSubject()
    {
        if (!$this->manageClassId) return;

        $this->validate(['newSubjectName' => 'required|string|max:255']);

        // Generate a smarter code from initials
        $words = explode(' ', trim($this->newSubjectName));
        $code = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), $words)));
        if (strlen($code) < 2) {
            $code = strtoupper(substr($this->newSubjectName, 0, 3));
        }

        Subject::create([
            'class_id' => $this->manageClassId,
            'name'     => $this->newSubjectName,
            'code'     => $code,
        ]);

        $this->newSubjectName = '';
        $this->loadSubjects($this->manageClassId);
        $this->loadClasses();
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
        Subject::where('id', $subjectId)->delete();
        // Remove from selection if it was selected
        $this->selectedSubjectIds = array_diff($this->selectedSubjectIds, [$subjectId]);
        $this->loadSubjects($this->manageClassId);
        $this->loadClasses();
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

            foreach ($subjectsToCopy as $subject) {
                // Check if subject with same name already exists in target class
                $exists = Subject::where('class_id', $targetClassId)
                    ->where('name', $subject->name)
                    ->exists();

                if (!$exists) {
                    Subject::create([
                        'class_id' => $targetClassId,
                        'name'     => $subject->name,
                        'code'     => $subject->code,
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
        $this->loadClasses();
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

    public function render()
    {
        $this->loadClasses();

        $layout = request()->is('teacher/*')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        return view('livewire.admin.class-manager')->layout($layout, ['title' => 'Class Management']);
    }
}
