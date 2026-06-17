<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Artisan;

class AcademicSessionManager extends Component
{
    public $sessions;
    public $name, $start_date, $end_date, $is_active = false;
    public $sessionId;
    public $isModalOpen = false;

    public function render()
    {
        $this->sessions = AcademicSession::orderBy('start_date', 'desc')->get();
        return view('livewire.admin.academic-session-manager')->layout('components.layouts.admin', ['title' => 'Academic Sessions']);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $session = AcademicSession::updateOrCreate(['id' => $this->sessionId], [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ]);

        // If this is a new session, auto-attach all users
        if (!$this->sessionId) {
            $users = \App\Models\User::pluck('id');
            $pivotData = [];
            foreach ($users as $userId) {
                $pivotData[] = [
                    'user_id' => $userId,
                    'academic_session_id' => $session->id,
                    'is_active' => true,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            \Illuminate\Support\Facades\DB::table('session_user')->insert($pivotData);
        }

        session()->flash('message', $this->sessionId ? 'Session Updated Successfully.' : 'Session Created Successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function generateEveningShift($parentId)
    {
        $parent = AcademicSession::findOrFail($parentId);

        // Ensure parent is Morning
        if ($parent->shift_type !== 'Morning') {
            $parent->update(['shift_type' => 'Morning']);
        }

        // Create the evening shift
        $evening = AcademicSession::create([
            'name' => $parent->name . ' (Evening)',
            'start_date' => $parent->start_date,
            'end_date' => $parent->end_date,
            'is_active' => $parent->is_active,
            'parent_id' => $parent->id,
            'shift_type' => 'Evening'
        ]);

        // Auto-attach all users to the evening shift as well
        $users = \App\Models\User::pluck('id');
        $pivotData = [];
        foreach ($users as $userId) {
            $pivotData[] = [
                'user_id' => $userId,
                'academic_session_id' => $evening->id,
                'is_active' => true,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        \Illuminate\Support\Facades\DB::table('session_user')->insert($pivotData);

        session()->flash('message', 'Evening Shift Generated Successfully!');
    }

    public function edit($id)
    {
        $session = AcademicSession::findOrFail($id);
        $this->sessionId = $id;
        $this->name = $session->name;
        $this->start_date = $session->start_date;
        $this->end_date = $session->end_date;
        $this->is_active = $session->is_active;

        $this->isModalOpen = true;
    }

    public function delete($id)
    {
        AcademicSession::find($id)->delete();
        session()->flash('message', 'Session Deleted Successfully.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->is_active = false;
        $this->sessionId = null;
    }

    public function runAutoUpdate()
    {
        Artisan::call('app:update-academic-session');
        session()->flash('message', 'Auto-update ran successfully: ' . Artisan::output());
    }
}
