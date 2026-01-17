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

        if ($this->is_active) {
            AcademicSession::where('id', '!=', $this->sessionId)->update(['is_active' => false]);
        }

        AcademicSession::updateOrCreate(['id' => $this->sessionId], [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active
        ]);

        session()->flash('message', $this->sessionId ? 'Session Updated Successfully.' : 'Session Created Successfully.');
        $this->closeModal();
        $this->resetInputFields();
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
