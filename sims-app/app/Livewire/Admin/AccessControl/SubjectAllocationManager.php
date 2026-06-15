<?php

namespace App\Livewire\Admin\AccessControl;

use Livewire\Component;
use App\Models\User;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class SubjectAllocationManager extends Component
{
    // Selection State
    public $selectedUserId;
    public $selectedClassId;
    public $selectedSubjectId;

    // Data Collections
    public $users = [];
    public $classes = [];
    public $subjects = [];
    public $allocations = []; // Existing allocations for selected user

    public function mount()
    {
        // Enforce View Permission
        if (!auth()->user()->can('allocations.view')) {
            abort(403, 'Unauthorized access to Allocation Manager.');
        }

        // Load users
        $this->users = User::orderBy('name')->get();
        
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $this->classes = Classes::where('academic_session_id', $activeSessionId)
            ->orderBy('numeric_value')
            ->get();
    }

    public function updatedSelectedUserId()
    {
        $this->loadAllocations();
    }

    public function updatedSelectedClassId()
    {
        $this->loadSubjects();
        $this->selectedSubjectId = null;
    }

    public function loadSubjects()
    {
        if ($this->selectedClassId) {
            $this->subjects = Subject::where('class_id', $this->selectedClassId)->get();
        } else {
            $this->subjects = [];
        }
    }

    public function loadAllocations()
    {
        if (!$this->selectedUserId) {
            $this->allocations = [];
            return;
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        $this->allocations = DB::table('subject_allocations')
            ->join('classes', 'subject_allocations.class_id', '=', 'classes.id')
            ->join('subjects', 'subject_allocations.subject_id', '=', 'subjects.id')
            ->where('subject_allocations.user_id', $this->selectedUserId)
            ->where('classes.academic_session_id', $activeSessionId)
            ->select(
                'subject_allocations.id',
                'subject_allocations.class_id',
                'subject_allocations.subject_id',
                'classes.name as class_name',
                'subjects.name as subject_name'
            )
            ->orderBy('classes.numeric_value')
            ->get();

        // Fetch User Locks
        $locks = DB::table('grade_locks')
            ->where('user_id', $this->selectedUserId)
            ->get()
            ->map(function ($lock) {
                return $lock->class_id . '-' . $lock->subject_id;
            })->toArray(); // ['classId-subjectId', ...]

        // Mark Manual Allocations
        foreach ($this->allocations as $alloc) {
            $key = $alloc->class_id . '-' . $alloc->subject_id;
            $alloc->is_locked = in_array($key, $locks);
            $alloc->is_inherent = false;
        }

        // MERGE: Add inherent Class Teacher Subject if exists
        $user = User::with('class')->find($this->selectedUserId);
        if ($user && $user->class_id && $user->class_subject) {
            $class = Classes::find($user->class_id);
            // Try to find subject ID by name in that class
            $subject = Subject::where('class_id', $user->class_id)
                ->where('name', $user->class_subject)
                ->first();

            if ($class && $subject) {
                // Create a synthetic object matching query structure
                $inherent = new \stdClass();
                $inherent->id = null; // No allocation ID
                $inherent->class_id = $class->id;
                $inherent->subject_id = $subject->id;
                $inherent->class_name = $class->name;
                $inherent->subject_name = $user->class_subject;
                $inherent->is_inherent = true; // Flag for UI
                
                $key = $class->id . '-' . $subject->id;
                $inherent->is_locked = in_array($key, $locks);
                
                // Prepend or push
                $this->allocations->prepend($inherent);
            }
        }
    }

    public function allocate()
    {
        if (!auth()->user()->can('allocations.manage')) {
            session()->flash('error', 'You do not have permission to allocate subjects.');
            return;
        }

        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'selectedClassId' => 'required|exists:classes,id',
            'selectedSubjectId' => 'required|exists:subjects,id',
        ]);

        // Check for duplicate
        $exists = DB::table('subject_allocations')
            ->where('user_id', $this->selectedUserId)
            ->where('class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->exists();

        if ($exists) {
            session()->flash('error', 'This subject is already allocated to the user.');
            return;
        }

        DB::table('subject_allocations')->insert([
            'user_id' => $this->selectedUserId,
            'class_id' => $this->selectedClassId,
            'subject_id' => $this->selectedSubjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->loadAllocations();
        session()->flash('message', 'Subject allocated successfully.');
        
        // Reset selection for rapid entry
        // $this->selectedSubjectId = null; 
    }

    public function deallocate($id)
    {
        if (!auth()->user()->can('allocations.manage')) {
            session()->flash('error', 'You do not have permission to remove allocations.');
            return;
        }

        DB::table('subject_allocations')->where('id', $id)->delete();
        $this->loadAllocations();
        session()->flash('message', 'Allocation removed.');
        session()->flash('message', 'Allocation removed.');
    }

    public function toggleLock($classId, $subjectId)
    {
        if (!auth()->user()->can('allocations.lock')) {
            session()->flash('error', 'You do not have permission to lock/unlock gradebooks.');
            return;
        }

        if (!$this->selectedUserId) return;

        $exists = DB::table('grade_locks')
            ->where('user_id', $this->selectedUserId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->exists();

        if ($exists) {
            DB::table('grade_locks')
                ->where('user_id', $this->selectedUserId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->delete();
            session()->flash('message', 'Gradebook unlocked.');
        } else {
            DB::table('grade_locks')->insert([
                'user_id' => $this->selectedUserId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            session()->flash('message', 'Gradebook locked to prevent edits.');
        }
        $this->loadAllocations();
    }

    public function render()
    {
        $layout = (auth()->check() && auth()->user()->role === 'teacher')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        return view('livewire.admin.access-control.subject-allocation-manager')
            ->layout($layout, ['title' => 'Data Scope']);
    }
}
