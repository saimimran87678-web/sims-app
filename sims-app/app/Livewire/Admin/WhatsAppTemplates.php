<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class WhatsAppTemplates extends Component
{
    public $templateAbsent;
    public $templateLeave;
    public $templateLate;

    public function mount()
    {
        $this->authorize('students.manage'); // Reuse existing permission

        // Load Template Settings
        $instituteName = \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
        $this->templateAbsent = \App\Models\Setting::get('whatsapp_template_absent', "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is ABSENT from school today ({date}).\nPlease contact the Class Teacher and give a valid reason.\n\n- {school_name} Administration");
        $this->templateLeave = \App\Models\Setting::get('whatsapp_template_leave', "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is on LEAVE today ({date}).\n\n- {school_name} Administration");
        $this->templateLate = \App\Models\Setting::get('whatsapp_template_late', "*Urgent Message*\n\nDear Parents,\nWe noticed that your {relation} {student_name} (Roll No: {roll_no}) was marked absent/leave, but has now arrived late at school today at {time}.\nPlease ensure they arrive on time in the future to avoid any warning.\n\n- {school_name} Administration");
    }

    public function saveSettings()
    {
        // Save Template Settings
        \App\Models\Setting::set('whatsapp_template_absent', $this->templateAbsent);
        \App\Models\Setting::set('whatsapp_template_leave', $this->templateLeave);
        \App\Models\Setting::set('whatsapp_template_late', $this->templateLate);

        session()->flash('message', 'Message templates saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.whatsapp-templates')->layout('components.layouts.admin', ['title' => 'WhatsApp Templates']);
    }
}
