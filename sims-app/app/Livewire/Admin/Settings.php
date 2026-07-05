<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;

class Settings extends Component
{
    use WithFileUploads;

    public $institute_name;
    public $institute_formal_name;
    public $institute_short_name;
    public $institute_phone;
    public $institute_logo;
    public $logo; // Temporary uploaded logo file
    public $weekend_mode;
    public $admin_action_pin_enabled = false;
    public $admin_action_pin = '';
    public $successMessage = '';

    protected function rules()
    {
        return [
            'institute_name' => 'required|string|max:255',
            'institute_formal_name' => 'nullable|string|max:255',
            'institute_short_name' => 'nullable|string|max:50',
            'institute_phone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:1024', // Max 1MB logo image
            'weekend_mode'   => 'required|in:sun_only,sat_sun',
            'admin_action_pin_enabled' => 'boolean',
            'admin_action_pin' => $this->admin_action_pin_enabled ? 'required|string|min:4|max:6' : 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->institute_name = Setting::getGlobal('institute_name', 'IMCB G-6/2');
        $this->institute_formal_name = Setting::getGlobal('institute_formal_name', 'Islamabad Model College for Boys (VI-X), G-6/2 Islamabad');
        $this->institute_short_name = Setting::getGlobal('institute_short_name', 'IMCB');
        $this->institute_phone = Setting::getGlobal('institute_phone', '');
        $this->institute_logo = Setting::getGlobal('institute_logo', '');
        $this->weekend_mode   = Setting::get('weekend_mode', 'sat_sun');
        $this->admin_action_pin_enabled = Setting::get('admin_action_pin_enabled', false);
        $this->admin_action_pin = Setting::get('admin_action_pin', '');
    }

    public function save()
    {
        $this->validate();

        if ($this->logo) {
            // Store file under public/branding directory
            $fileName = 'logo_' . time() . '.' . $this->logo->getClientOriginalExtension();
            $path = $this->logo->storeAs('branding', $fileName, 'public');
            $logoUrl = 'storage/' . $path;

            // Optional: delete old logo if exists
            if ($this->institute_logo && file_exists(public_path($this->institute_logo))) {
                @unlink(public_path($this->institute_logo));
            }

            Setting::setGlobal('institute_logo', $logoUrl);
            $this->institute_logo = $logoUrl;
            $this->logo = null;
        }

        Setting::setGlobal('institute_name', $this->institute_name);
        Setting::setGlobal('institute_formal_name', $this->institute_formal_name ?? '');
        Setting::setGlobal('institute_short_name', $this->institute_short_name ?? '');
        Setting::setGlobal('institute_phone', $this->institute_phone ?? '');
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
