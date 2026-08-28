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

    // Import classes
    public $showImportModal = false;
    public $importSourceSessionId = '';
    public $importSubjects = true;
    public $selectedSourceClassIds = [];

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
        if (!$this->selectedSessionId) {
            return collect();
        }

        // Update canViewSessions just in case the context changes
        $this->canViewSessions = auth()->user()->hasRole('Super Admin') || 
                                 auth()->user()->role === 'admin' || 
                                 auth()->user()->can('classes.view-sessions');

        // Enforce scope for non-privileged users
        if (!$this->canViewSessions) {
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            if ($this->selectedSessionId != $activeSessionId) {
                $this->selectedSessionId = $activeSessionId;
            }
        }

        $query = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $this->getCurrentShift())
            ->with(['nextClass'])
            ->withCount('subjects');

        if ($this->showTrash) {
            $query->onlyTrashed();
        }

        return $query->orderBy('numeric_value')->get();
    }

    public function getCurrentShift()
    {
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }
        return $shiftType;
    }

    public function getSourceShift()
    {
        if (!$this->importSourceSessionId) {
            return 'morning';
        }
        $sourceSession = \App\Models\AcademicSession::find($this->importSourceSessionId);
        $isSourceRegular = ($sourceSession && $sourceSession->shift_type === 'Regular');
        if ($isSourceRegular) {
            return 'regular';
        }
        $targetShift = $this->getCurrentShift();
        return ($targetShift === 'regular') ? 'morning' : $targetShift;
    }

    public function updatedSelectedSessionId()
    {
        // Handled automatically via reactivity
    }

    public function toggleTrash()
    {
        $this->showTrash = !$this->showTrash;
        $this->renamingClassId = null;
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
        $classExists = Classes::withoutGlobalScope('active_session')
            ->where('name', $className)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->exists();

        if ($classExists) {
            $this->addError('name', 'This class already exists in the selected session and shift.');
            return;
        }

        $numericValue = (int) filter_var($className, FILTER_SANITIZE_NUMBER_INT);
        Classes::create([
            'name' => $className,
            'numeric_value' => $numericValue ?: 0,
            'academic_session_id' => $this->selectedSessionId,
            'shift_type' => $shiftType,
        ]);

        $this->name = '';
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

        $class = Classes::withoutGlobalScope('active_session')->findOrFail($this->renamingClassId);

        // Check duplicate within the same session and shift
        $exists = Classes::withoutGlobalScope('active_session')
            ->where('name', $newName)
            ->where('academic_session_id', $class->academic_session_id)
            ->where('shift_type', $class->shift_type)
            ->where('id', '!=', $this->renamingClassId)
            ->exists();

        if ($exists) {
            $this->addError('renamingClassName', 'A class with this name already exists in this session.');
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
        session()->flash('message', 'Class restored successfully.');
    }

    public function permanentDelete($id)
    {
        $class = Classes::withoutGlobalScope('active_session')->onlyTrashed()->where('id', $id)->first();
        if ($class) {
            // Check if there are any enrollments linked to this class
            $hasEnrollments = DB::table('enrollments')->where('class_id', $id)->exists();
            if ($hasEnrollments) {
                session()->flash('message', 'Cannot permanently delete class because it has associated student enrollments.');
                return;
            }

            // Hard-delete subjects too
            DB::table('subjects')->where('class_id', $id)->delete();
            $class->forceDelete();
        }
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
        Classes::withoutGlobalScope('active_session')
            ->where('id', $classId)
            ->update([
                'next_class_id' => $nextClassId ?: null,
            ]);

        session()->flash('message', 'Next class configuration updated successfully.');
    }

    public function openImportModal()
    {
        $this->showImportModal = true;
        $this->importSourceSessionId = '';
        $this->importSubjects = true;
        $this->selectedSourceClassIds = [];
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
    }

    public function updatedImportSourceSessionId($value)
    {
        $this->selectedSourceClassIds = [];
    }

    public function getSourceClasses()
    {
        if (!$this->importSourceSessionId) {
            return collect();
        }
        return Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->importSourceSessionId)
            ->where('shift_type', $this->getSourceShift())
            ->orderBy('numeric_value')
            ->get();
    }

    public function toggleSelectAllSourceClasses()
    {
        $allIds = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->importSourceSessionId)
            ->where('shift_type', $this->getSourceShift())
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        if (count($this->selectedSourceClassIds) === count($allIds)) {
            $this->selectedSourceClassIds = [];
        } else {
            $this->selectedSourceClassIds = $allIds;
        }
    }

    public function importClasses()
    {
        $this->validate([
            'importSourceSessionId' => 'required|exists:academic_sessions,id',
        ]);

        if ($this->importSourceSessionId == $this->selectedSessionId) {
            $this->addError('importSourceSessionId', 'Source and target sessions cannot be the same.');
            return;
        }

        if (empty($this->selectedSourceClassIds)) {
            $this->addError('selectedSourceClassIds', 'Please select at least one class to import.');
            return;
        }

        $sourceClasses = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->importSourceSessionId)
            ->where('shift_type', $this->getSourceShift())
            ->whereIn('id', $this->selectedSourceClassIds)
            ->get();

        if ($sourceClasses->isEmpty()) {
            $this->addError('selectedSourceClassIds', 'No valid classes selected for import.');
            return;
        }

        $copiedCount = 0;
        $skippedCount = 0;
        $classMap = [];

        foreach ($sourceClasses as $sc) {
            // Check if class with same name already exists in target session and shift
            $existingClass = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->where('shift_type', $this->getCurrentShift())
                ->where('name', $sc->name)
                ->first();

            if (!$existingClass) {
                $newClass = Classes::create([
                    'academic_session_id' => $this->selectedSessionId,
                    'name' => $sc->name,
                    'numeric_value' => $sc->numeric_value,
                    'shift_type' => $this->getCurrentShift(),
                ]);
                $classMap[$sc->id] = $newClass->id;
                $copiedCount++;

                if ($this->importSubjects) {
                    $sourceSubjects = Subject::where('class_id', $sc->id)->get();
                    foreach ($sourceSubjects as $subj) {
                        Subject::create([
                            'class_id' => $newClass->id,
                            'name'     => $subj->name,
                            'code'     => $subj->code,
                        ]);
                    }
                }
            } else {
                $classMap[$sc->id] = $existingClass->id;
                $skippedCount++;

                if ($this->importSubjects) {
                    $sourceSubjects = Subject::where('class_id', $sc->id)->get();
                    foreach ($sourceSubjects as $subj) {
                        $exists = Subject::where('class_id', $existingClass->id)
                            ->where('name', $subj->name)
                            ->exists();
                        if (!$exists) {
                             Subject::create([
                                 'class_id' => $existingClass->id,
                                 'name'     => $subj->name,
                                 'code'     => $subj->code,
                             ]);
                        }
                    }
                }
            }
        }

        // Map next_class_ids for newly created classes
        foreach ($sourceClasses as $sc) {
            if ($sc->next_class_id && isset($classMap[$sc->next_class_id]) && isset($classMap[$sc->id])) {
                $targetClassId = $classMap[$sc->id];
                Classes::withoutGlobalScope('active_session')
                    ->where('id', $targetClassId)
                    ->update([
                        'next_class_id' => $classMap[$sc->next_class_id]
                    ]);
            }
        }

        $msg = "Successfully imported {$copiedCount} class(es).";
        if ($skippedCount > 0) {
            $msg .= " {$skippedCount} class(es) already existed and were merged/skipped.";
        }

        $this->showImportModal = false;
        $this->importSourceSessionId = '';
        $this->selectedSourceClassIds = [];
        session()->flash('message', $msg);
    }

    public function render()
    {
        $layout = request()->is('teacher/*')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        $currentSession = \App\Models\AcademicSession::find($this->selectedSessionId);
        $nextSession = null;
        if ($currentSession) {
            $nextSession = \App\Models\AcademicSession::where('start_date', '>', $currentSession->start_date)
                ->orderBy('start_date', 'asc')
                ->first();
        }

        $hasNextSession = !is_null($nextSession);

        $allClasses = collect();
        if ($hasNextSession) {
            $allClasses = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $nextSession->id)
                ->where('shift_type', $this->getCurrentShift())
                ->whereNull('deleted_at')
                ->orderBy('numeric_value')
                ->get();
        }

        $hasFinalExam = \App\Models\Exam::where('academic_session_id', $this->selectedSessionId)
            ->where('type', 'Final-Term')
            ->exists();

        return view('livewire.admin.class-manager', [
            'classes' => $this->getClassesList(),
            'allClasses' => $allClasses,
            'hasFinalExam' => $hasFinalExam,
            'hasNextSession' => $hasNextSession,
        ])->layout($layout, ['title' => 'Class Management']);
    }
}
