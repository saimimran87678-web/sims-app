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

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $scopedShift = ($shiftType === 'both') ? 'morning' : $shiftType;

        $templateAbsentDefault = \App\Models\Setting::get('whatsapp_template_absent', "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is ABSENT from school today ({date}).\nPlease contact the Class Teacher and give a valid reason.\n\n- {school_name} Administration");
        $this->templateAbsent = \App\Models\Setting::get("whatsapp_template_absent_{$scopedShift}", $templateAbsentDefault);

        $templateLeaveDefault = \App\Models\Setting::get('whatsapp_template_leave', "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is on LEAVE today ({date}).\n\n- {school_name} Administration");
        $this->templateLeave = \App\Models\Setting::get("whatsapp_template_leave_{$scopedShift}", $templateLeaveDefault);

        $templateLateDefault = \App\Models\Setting::get('whatsapp_template_late', "*Urgent Message*\n\nDear Parents,\nWe noticed that your {relation} {student_name} (Roll No: {roll_no}) was marked absent/leave, but has now arrived late at school today at {time}.\nPlease ensure they arrive on time in the future to avoid any warning.\n\n- {school_name} Administration");
        $this->templateLate = \App\Models\Setting::get("whatsapp_template_late_{$scopedShift}", $templateLateDefault);
    }

    public function saveSettings()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $scopedShift = ($shiftType === 'both') ? 'morning' : $shiftType;

        \App\Models\Setting::set("whatsapp_template_absent_{$scopedShift}", $this->templateAbsent);
        \App\Models\Setting::set("whatsapp_template_leave_{$scopedShift}", $this->templateLeave);
        \App\Models\Setting::set("whatsapp_template_late_{$scopedShift}", $this->templateLate);

        session()->flash('message', 'Message templates saved successfully.');
    }

    public function render()
    {
        return view('livewire.admin.whatsapp-templates')->layout('components.layouts.admin', ['title' => 'WhatsApp Templates']);
    }
}
