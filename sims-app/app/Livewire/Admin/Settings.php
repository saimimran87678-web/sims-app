<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;

class Settings extends Component
{
    public $institute_name;
    public $weekend_mode;
    public $admin_action_pin_enabled = false;
    public $admin_action_pin = '';
    public $successMessage = '';

    protected function rules()
    {
        return [
            'institute_name' => 'required|string|max:255',
            'weekend_mode'   => 'required|in:sun_only,sat_sun',
            'admin_action_pin_enabled' => 'boolean',
            'admin_action_pin' => $this->admin_action_pin_enabled ? 'required|string|min:4|max:6' : 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->institute_name = Setting::get('institute_name', 'IMCB G-6/2');
        $this->weekend_mode   = Setting::get('weekend_mode', 'sat_sun');
        $this->admin_action_pin_enabled = Setting::get('admin_action_pin_enabled', false);
        $this->admin_action_pin = Setting::get('admin_action_pin', '');
    }

    public function save()
    {
        $this->validate();

        Setting::set('institute_name', $this->institute_name);
        Setting::set('weekend_mode',   $this->weekend_mode);
        Setting::set('admin_action_pin_enabled', $this->admin_action_pin_enabled);
        Setting::set('admin_action_pin', $this->admin_action_pin_enabled ? $this->admin_action_pin : '');

        session()->flash('status', 'Settings updated successfully!');
    }

    public function render()
    {
        return view('livewire.admin.settings')
            ->layout('components.layouts.admin', ['title' => 'System Settings']);
    }
}
