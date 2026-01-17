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

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.class-manager')->layout($layout, ['title' => 'Class Management']);
    }
}
