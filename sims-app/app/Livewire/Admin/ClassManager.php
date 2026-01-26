<?php

namespace App\Livewire\Admin;

use App\Models\Classes;
use App\Models\Subject;
use Livewire\Component;

class ClassManager extends Component
{
    public $classes;
    public $name = '';
    
    // Subject Management
    public $manageClassId = null;
    public $manageClassName = '';
    public $classSubjects = [];
    public $newSubjectName = '';

    public $selectedSessionId;
    public $academicSessions = [];

    public function mount()
    {
        $this->authorize('classes.manage');
        $this->academicSessions = \Illuminate\Support\Facades\DB::table('academic_sessions')->orderBy('start_date', 'desc')->get();
        // Default to active session
        $this->selectedSessionId = $this->academicSessions->where('is_active', true)->first()->id ?? $this->academicSessions->first()->id ?? null;
        
        $this->loadClasses();
    }

    public function loadClasses()
    {
        // Use Model to leverage scopes (though here we explicitly filter by selected session)
        // We use withoutGlobalScope('active_session') because we might be viewing an INACTIVE session.
        if ($this->selectedSessionId) {
            // Enforce Scope
            if (!auth()->user()->can('classes.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
                 $activeSessionId = \App\Models\AcademicSession::where('is_active', true)->value('id');
                 if ($this->selectedSessionId != $activeSessionId) {
                     $this->selectedSessionId = $activeSessionId;
                 }
            }

            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->withCount('subjects')
                ->orderBy('numeric_value')
                ->get();
        } else {
            $this->classes = collect();
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:50',
        ]);

        // Auto-prefix "Class " if not already present
        $className = trim($this->name);
        if (!str_starts_with(strtolower($className), 'class ')) {
            $className = 'Class ' . $className;
        }

        // Check for duplicate
        // Check for duplicate IN THIS SESSION
        $exists = Classes::withoutGlobalScope('active_session')
            ->where('name', $className)
            ->where('academic_session_id', $this->selectedSessionId)
            ->exists();
        
        if ($exists) {
            $this->addError('name', 'This class already exists in the selected session.');
            return;
        }

        // Extract numeric value for sorting (e.g. "Class 11B" -> 11)
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

    public function delete($id)
    {
        Classes::withoutGlobalScope('active_session')->where('id', $id)->delete();
        $this->loadClasses();
        $this->manageClassId = null; // Close modal if open
    }

    // Modal Logic
    public function openSubjectModal($classId, $className)
    {
        $this->manageClassId = $classId;
        $this->manageClassName = $className;
        $this->loadSubjects($classId);
    }

    public function loadSubjects($classId)
    {
        $this->classSubjects = \Illuminate\Support\Facades\DB::table('subjects')
            ->where('class_id', $classId)
            ->get();
    }

    public function addSubject()
    {
        if (!$this->manageClassId) return;

        $this->validate([
            'newSubjectName' => 'required|string|max:255',
        ]);

        \Illuminate\Support\Facades\DB::table('subjects')->insert([
            'class_id' => $this->manageClassId,
            'name' => $this->newSubjectName,
            'code' => strtoupper(substr($this->newSubjectName, 0, 3)), // Auto-code
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->newSubjectName = '';
        $this->loadSubjects($this->manageClassId);
        $this->loadClasses(); // Update counts
    }

    public $editingSubjectId = null;
    public $editingSubjectName = '';

    public function editSubject($subjectId)
    {
        $subject = \Illuminate\Support\Facades\DB::table('subjects')->where('id', $subjectId)->first();
        if ($subject) {
            $this->editingSubjectId = $subjectId;
            $this->editingSubjectName = $subject->name;
        }
    }

    public function updateSubject()
    {
        if (!$this->editingSubjectId) return;

        $this->validate([
            'editingSubjectName' => 'required|string|max:255',
        ]);

        \Illuminate\Support\Facades\DB::table('subjects')->where('id', $this->editingSubjectId)->update([
            'name' => $this->editingSubjectName,
            'code' => strtoupper(substr($this->editingSubjectName, 0, 3)),
            'updated_at' => now(),
        ]);

        $this->editingSubjectId = null;
        $this->editingSubjectName = '';
        $this->loadSubjects($this->manageClassId);
    }

    public function cancelEditSubject()
    {
        $this->editingSubjectId = null;
        $this->editingSubjectName = '';
    }

    public function deleteSubject($subjectId)
    {
        \Illuminate\Support\Facades\DB::table('subjects')->where('id', $subjectId)->delete();
        $this->loadSubjects($this->manageClassId);
        $this->loadClasses(); // Update counts
    }

    public function closeModal()
    {
        $this->manageClassId = null;
        $this->manageClassName = '';
        $this->newSubjectName = '';
    }

    public function copySubjectsToSections()
    {
        if (!$this->manageClassId) return;

        $currentClass = Classes::withoutGlobalScope('active_session')->find($this->manageClassId);
        if (!$currentClass) return;

        // Find sibling classes (same numeric value, same session, different section)
        $siblings = Classes::withoutGlobalScope('active_session')
            ->where('numeric_value', $currentClass->numeric_value)
            ->where('academic_session_id', $currentClass->academic_session_id)
            ->where('id', '!=', $currentClass->id)
            ->get();

        if ($siblings->isEmpty()) {
            $this->dispatch('show-toast', type: 'error', message: 'No other sections found for Class ' . $currentClass->numeric_value);
            return;
        }

        $subjectsToCopy = \Illuminate\Support\Facades\DB::table('subjects')->where('class_id', $currentClass->id)->get();

        if ($subjectsToCopy->isEmpty()) {
            $this->dispatch('show-toast', type: 'error', message: 'No subjects to copy.');
            return;
        }

        $copyCount = 0;
        $classCount = 0;

        foreach ($siblings as $sibling) {
            $classCount++;
            foreach ($subjectsToCopy as $subject) {
                // Check if subject exists (by code or name)
                $exists = \Illuminate\Support\Facades\DB::table('subjects')
                    ->where('class_id', $sibling->id)
                    ->where(function($q) use ($subject) {
                        $q->where('code', $subject->code)
                          ->orWhere('name', $subject->name);
                    })
                    ->exists();

                if (!$exists) {
                    \Illuminate\Support\Facades\DB::table('subjects')->insert([
                        'class_id' => $sibling->id,
                        'name' => $subject->name,
                        'code' => $subject->code, // Keep same code
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $copyCount++;
                }
            }
        }

        // Refresh if we are viewing the source class (no change needed visually, but good practice)
        // Actually we might want to notify user
        session()->flash('message', "Copied {$copyCount} subjects to {$classCount} other sections.");
        // We can also close modal or stay open. Stay open is better to see result? No, result is in other classes.
    }

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.class-manager')->layout($layout, ['title' => 'Class Management']);
    }
}
