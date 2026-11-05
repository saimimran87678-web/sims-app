<?php

namespace App\Livewire\Admin;

use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CurriculumManager extends Component
{
    // Active navigation tab: 'catalogue', 'programs', 'offerings'
    public $activeTab = 'catalogue';

    // Global filters
    public $search = '';
    public $categoryFilter = '';
    public $levelFilter = '';

    // Session for Class Offerings
    public $selectedSessionId;
    public $academicSessions = [];

    // --- Subject Modal State ---
    public $showSubjectModal = false;
    public $editingSubjectId = null;
    public $subjectName = '';
    public $subjectCode = '';
    public $subjectCategory = 'General';
    public $subjectIsActive = true;

    // --- Program Modal State ---
    public $showProgramModal = false;
    public $editingProgramId = null;
    public $programName = '';
    public $programCode = '';
    public $programLevel = 'Matric';
    public $programDescription = '';
    public $programIsActive = true;
    public $programPreselectedSubjectIds = [];

    // --- Program Bundled Subjects Drawer/Modal State ---
    public $showProgramSubjectsModal = false;
    public $selectedProgramId = null;
    public $newSubjectIdToAdd = '';
    public $newSubjectIdsToAdd = [];
    public $newSubjectType = 'compulsory'; // compulsory | elective

    // --- Class Offerings Modal State ---
    public $showClassOfferingsModal = false;
    public $offeringClassId = null;
    public $offeringClassName = '';
    public $offeringProgramIds = [];
    public $offeringDefaultProgramId = null;

    public function mount()
    {
        $this->authorize('classes.manage');

        $this->academicSessions = DB::table('academic_sessions')
            ->orderBy('start_date', 'desc')
            ->get();
        $this->selectedSessionId = AcademicSession::getActiveSessionId();

        if (request()->has('tab') && in_array(request('tab'), ['catalogue', 'programs', 'offerings'])) {
            $this->activeTab = request('tab');
        }
    }

    public function setTab($tab)
    {
        if (in_array($tab, ['catalogue', 'programs', 'offerings'])) {
            $this->activeTab = $tab;
            $this->search = '';
        }
    }

    // -------------------------------------------------------------------------
    // MASTER SUBJECT CATALOGUE ACTIONS
    // -------------------------------------------------------------------------

    public function openCreateSubjectModal()
    {
        $this->resetValidation();
        $this->editingSubjectId = null;
        $this->subjectName = '';
        $this->subjectCode = '';
        $this->subjectCategory = 'General';
        $this->subjectIsActive = true;
        $this->showSubjectModal = true;
    }

    public function editSubject($id)
    {
        $this->resetValidation();
        $subject = Subject::findOrFail($id);
        $this->editingSubjectId = $subject->id;
        $this->subjectName = $subject->name;
        $this->subjectCode = $subject->code ?? '';
        $this->subjectCategory = $subject->category ?? 'General';
        $this->subjectIsActive = (bool) $subject->is_active;
        $this->showSubjectModal = true;
    }

    public function saveSubject()
    {
        $this->validate([
            'subjectName' => 'required|string|max:100|unique:subjects,name,' . $this->editingSubjectId,
            'subjectCode' => 'nullable|string|max:20|unique:subjects,code,' . $this->editingSubjectId,
            'subjectCategory' => 'required|string|max:50',
        ]);

        $code = trim($this->subjectCode);
        if (empty($code)) {
            $code = Subject::formatAbbreviation($this->subjectName);
        } else {
            $code = strtoupper($code);
        }

        if ($this->editingSubjectId) {
            $subject = Subject::findOrFail($this->editingSubjectId);
            $subject->update([
                'name' => trim($this->subjectName),
                'code' => $code,
                'category' => $this->subjectCategory,
                'is_active' => $this->subjectIsActive,
            ]);
            session()->flash('message', "Subject '{$subject->name}' updated successfully.");
        } else {
            $subject = Subject::create([
                'name' => trim($this->subjectName),
                'code' => $code,
                'category' => $this->subjectCategory,
                'class_id' => null,
                'is_active' => $this->subjectIsActive,
            ]);
            session()->flash('message', "Canonical Subject '{$subject->name}' added to Master Catalogue.");
        }

        $this->showSubjectModal = false;
    }

    public function toggleSubjectActive($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['is_active' => !$subject->is_active]);
        session()->flash('message', "Subject '{$subject->name}' status updated.");
    }

    public function deleteSubject($id)
    {
        $subject = Subject::findOrFail($id);

        $breakdown = $subject->getLinkedRecordsBreakdown();
        if ($breakdown['total'] > 0) {
            $reasons = [];
            if ($breakdown['programs'] > 0) {
                $reasons[] = "{$breakdown['programs']} " . \Illuminate\Support\Str::plural('academic program bundle', $breakdown['programs']);
            }
            if ($breakdown['exam_marks'] > 0) {
                $reasons[] = "{$breakdown['exam_marks']} " . \Illuminate\Support\Str::plural('student exam mark', $breakdown['exam_marks']);
            }
            if ($breakdown['allocations'] > 0) {
                $reasons[] = "{$breakdown['allocations']} " . \Illuminate\Support\Str::plural('teacher allocation', $breakdown['allocations']);
            }
            if ($breakdown['marks_configs'] > 0) {
                $reasons[] = "{$breakdown['marks_configs']} " . \Illuminate\Support\Str::plural('marks configuration', $breakdown['marks_configs']);
            }
            if ($breakdown['exam_schedules'] > 0) {
                $reasons[] = "{$breakdown['exam_schedules']} " . \Illuminate\Support\Str::plural('exam schedule', $breakdown['exam_schedules']);
            }
            if ($breakdown['timetables'] > 0) {
                $reasons[] = "{$breakdown['timetables']} " . \Illuminate\Support\Str::plural('timetable slot', $breakdown['timetables']);
            }
            if ($breakdown['substitutions'] > 0) {
                $reasons[] = "{$breakdown['substitutions']} " . \Illuminate\Support\Str::plural('substitution record', $breakdown['substitutions']);
            }
            if ($breakdown['student_subjects'] > 0) {
                $reasons[] = "{$breakdown['student_subjects']} " . \Illuminate\Support\Str::plural('student elective record', $breakdown['student_subjects']);
            }
            if ($breakdown['grade_locks'] > 0) {
                $reasons[] = "{$breakdown['grade_locks']} " . \Illuminate\Support\Str::plural('grade lock', $breakdown['grade_locks']);
            }
            if ($breakdown['fee_structures'] > 0) {
                $reasons[] = "{$breakdown['fee_structures']} " . \Illuminate\Support\Str::plural('fee structure', $breakdown['fee_structures']);
            }

            $detail = implode(', ', $reasons);
            session()->flash('error', "Cannot delete subject '{$subject->name}': it is linked to {$detail}. Remove it from academic programs or deactivate it instead to protect historic records.");
            return;
        }

        $subjectName = $subject->name;
        $subject->delete();

        session()->flash('message', "Subject '{$subjectName}' was deleted successfully from the Master Catalogue.");
    }

    // -------------------------------------------------------------------------
    // PROGRAM ACTIONS
    // -------------------------------------------------------------------------

    public function openCreateProgramModal()
    {
        $this->resetValidation();
        $this->editingProgramId = null;
        $this->programName = '';
        $this->programCode = '';
        $this->programLevel = 'Matric';
        $this->programDescription = '';
        $this->programIsActive = true;
        $this->programPreselectedSubjectIds = [];
        $this->showProgramModal = true;
    }

    public function editProgram($id)
    {
        $this->resetValidation();
        $program = Program::with('subjects')->findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->programName = $program->name;
        $this->programCode = $program->code;
        $this->programLevel = $program->level ?? 'Matric';
        $this->programDescription = $program->description ?? '';
        $this->programIsActive = (bool) $program->is_active;
        $this->programPreselectedSubjectIds = $program->subjects->pluck('id')->map(fn($id) => (int) $id)->toArray();
        $this->showProgramModal = true;
    }

    public function saveProgram()
    {
        $this->validate([
            'programName' => 'required|string|max:150',
            'programCode' => 'required|string|max:30|unique:programs,code,' . $this->editingProgramId,
            'programLevel' => 'required|string|max:50',
            'programDescription' => 'nullable|string|max:500',
        ]);

        $code = strtoupper(trim($this->programCode));

        if ($this->editingProgramId) {
            $program = Program::findOrFail($this->editingProgramId);
            $program->update([
                'name' => trim($this->programName),
                'code' => $code,
                'level' => $this->programLevel,
                'description' => $this->programDescription,
                'is_active' => $this->programIsActive,
            ]);
            session()->flash('message', "Academic program '{$program->name}' updated successfully.");
        } else {
            $program = Program::create([
                'name' => trim($this->programName),
                'code' => $code,
                'level' => $this->programLevel,
                'description' => $this->programDescription,
                'is_active' => $this->programIsActive,
            ]);

            if (!empty($this->programPreselectedSubjectIds)) {
                $order = 1;
                foreach ($this->programPreselectedSubjectIds as $subId) {
                    $program->subjects()->attach((int) $subId, [
                        'subject_type' => 'compulsory',
                        'sort_order' => $order++,
                    ]);
                }
            }

            session()->flash('message', "Academic program '{$program->name}' created successfully.");
        }

        $this->programPreselectedSubjectIds = [];
        $this->showProgramModal = false;
    }

    public function toggleProgramActive($id)
    {
        $program = Program::findOrFail($id);
        $program->update(['is_active' => !$program->is_active]);
        session()->flash('message', "Program '{$program->name}' status updated.");
    }

    // -------------------------------------------------------------------------
    // PROGRAM SUBJECTS BUNDLER (MANAGE BUNDLE)
    // -------------------------------------------------------------------------

    public function openProgramSubjectsModal($programId)
    {
        $this->selectedProgramId = $programId;
        $this->newSubjectIdToAdd = '';
        $this->newSubjectIdsToAdd = [];
        $this->newSubjectType = 'compulsory';
        $this->showProgramSubjectsModal = true;
    }

    public function closeProgramSubjectsModal()
    {
        $this->showProgramSubjectsModal = false;
        $this->selectedProgramId = null;
        $this->newSubjectIdToAdd = '';
        $this->newSubjectIdsToAdd = [];
    }

    public function addSubjectsToProgram()
    {
        if (!$this->selectedProgramId || empty($this->newSubjectIdsToAdd)) {
            return;
        }

        $program = Program::findOrFail($this->selectedProgramId);
        $addedCount = 0;
        $existingCount = $program->subjects()->count();

        foreach ($this->newSubjectIdsToAdd as $subId) {
            $subId = (int) $subId;
            if (!$program->subjects()->where('subjects.id', $subId)->exists()) {
                $program->subjects()->attach($subId, [
                    'subject_type' => $this->newSubjectType,
                    'sort_order' => ++$existingCount,
                ]);
                $addedCount++;
            }
        }

        $this->newSubjectIdsToAdd = [];
        $this->newSubjectIdToAdd = '';
        session()->flash('bundle_message', "✓ {$addedCount} subject(s) added to program bundle.");
    }

    public function addSubjectToProgram()
    {
        if ($this->newSubjectIdToAdd) {
            $subId = (int) $this->newSubjectIdToAdd;
            if (!in_array($subId, array_map('intval', $this->newSubjectIdsToAdd))) {
                $this->newSubjectIdsToAdd[] = $subId;
            }
        }
        $this->addSubjectsToProgram();
    }

    public function toggleSubjectTypeInProgram($subjectId)
    {
        if (!$this->selectedProgramId) {
            return;
        }

        $program = Program::findOrFail($this->selectedProgramId);
        $current = $program->subjects()->where('subjects.id', $subjectId)->first();
        if ($current) {
            $newType = ($current->pivot->subject_type === 'compulsory') ? 'elective' : 'compulsory';
            $program->subjects()->updateExistingPivot($subjectId, ['subject_type' => $newType]);
            session()->flash('bundle_message', "Subject marked as {$newType}.");
        }
    }

    public function removeSubjectFromProgram($subjectId)
    {
        if (!$this->selectedProgramId) {
            return;
        }

        $program = Program::findOrFail($this->selectedProgramId);
        $program->subjects()->detach($subjectId);
        session()->flash('bundle_message', 'Subject removed from program.');
    }

    // -------------------------------------------------------------------------
    // CLASS OFFERINGS ACTIONS (ATTACH TRACKS TO CLASSES)
    // -------------------------------------------------------------------------

    public function openClassOfferingsModal($classId)
    {
        $class = Classes::withoutGlobalScope('active_session')->findOrFail($classId);
        $this->offeringClassId = $class->id;
        $this->offeringClassName = $class->name;

        // Current assigned program IDs
        $assigned = DB::table('class_programs')
            ->where('class_id', $class->id)
            ->get();

        $this->offeringProgramIds = $assigned->pluck('program_id')->map(fn($id) => (int) $id)->toArray();
        $defaultRow = $assigned->firstWhere('is_default', 1);
        $this->offeringDefaultProgramId = $defaultRow ? (int) $defaultRow->program_id : ($this->offeringProgramIds[0] ?? null);

        $this->showClassOfferingsModal = true;
    }

    public function saveClassOfferings()
    {
        if (!$this->offeringClassId) {
            return;
        }

        $class = Classes::withoutGlobalScope('active_session')->findOrFail($this->offeringClassId);

        // Delete existing offerings for this class
        DB::table('class_programs')->where('class_id', $class->id)->delete();

        // Re-attach selected programs
        $selectedIds = array_filter(array_map('intval', $this->offeringProgramIds));

        if (!empty($selectedIds)) {
            // Ensure default program is in selected
            if (!$this->offeringDefaultProgramId || !in_array($this->offeringDefaultProgramId, $selectedIds)) {
                $this->offeringDefaultProgramId = $selectedIds[0];
            }

            foreach ($selectedIds as $progId) {
                DB::table('class_programs')->insert([
                    'class_id' => $class->id,
                    'program_id' => $progId,
                    'is_default' => ($progId == $this->offeringDefaultProgramId),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->showClassOfferingsModal = false;
        session()->flash('message', "Curriculum offerings for {$class->name} updated successfully.");
    }

    public function clearOfferingsForClass($classId)
    {
        $class = Classes::withoutGlobalScope('active_session')->find($classId);
        if ($class) {
            DB::table('class_programs')->where('class_id', $classId)->delete();
            session()->flash('message', "Removed all program offerings from {$class->name}.");
        }
    }

    public function clearAllClassOfferings()
    {
        $cleared = DB::table('class_programs')->delete();
        DB::table('enrollments')->whereNotNull('program_id')->update(['program_id' => null]);
        DB::table('student_subject')->delete();

        session()->flash('message', "✓ Cleared all {$cleared} class program offerings across all classes for testing.");
    }

    public function restoreDefaultOfferings()
    {
        \Illuminate\Support\Facades\Artisan::call('curriculum:restore-defaults');
        session()->flash('message', '✓ Restored standard default academic programs across all classes.');
    }

    // -------------------------------------------------------------------------
    // RENDER
    // -------------------------------------------------------------------------

    public function render()
    {
        // 1. Master Catalogue Data
        $subjectsQuery = Subject::query()->withCount('programs')->orderBy('name');
        if ($this->search && $this->activeTab === 'catalogue') {
            $subjectsQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }
        if ($this->categoryFilter) {
            $subjectsQuery->where('category', $this->categoryFilter);
        }
        $subjects = $subjectsQuery->get();
        $categories = Subject::select('category')->distinct()->pluck('category')->filter()->values();

        // 2. Programs Data
        $programsQuery = Program::with(['subjects'])->orderBy('level')->orderBy('name');
        if ($this->search && $this->activeTab === 'programs') {
            $programsQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }
        if ($this->levelFilter) {
            $programsQuery->where('level', $this->levelFilter);
        }
        $programs = $programsQuery->get();
        $levels = Program::select('level')->distinct()->pluck('level')->filter()->values();

        // Selected Program for Bundler Modal
        $activeProgram = $this->selectedProgramId
            ? Program::with('subjects')->find($this->selectedProgramId)
            : null;

        $unassignedCatalogueSubjects = $activeProgram
            ? Subject::whereNotIn('id', $activeProgram->subjects->pluck('id'))->where('is_active', true)->orderBy('name')->get()
            : collect();

        // 3. Class Offerings Data
        $classes = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->with(['programs.subjects'])
            ->orderBy('numeric_value')
            ->orderBy('name')
            ->get();

        $allActivePrograms = Program::active()->orderBy('name')->get();

        return view('livewire.admin.curriculum-manager', [
            'subjects' => $subjects,
            'categories' => $categories,
            'programs' => $programs,
            'levels' => $levels,
            'activeProgram' => $activeProgram,
            'unassignedCatalogueSubjects' => $unassignedCatalogueSubjects,
            'allActiveSubjects' => Subject::active()->orderBy('name')->get(),
            'classes' => $classes,
            'allActivePrograms' => $allActivePrograms,
        ])->layout('components.layouts.admin');
    }
}
