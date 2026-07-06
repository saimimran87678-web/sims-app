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

    // Security Verification Modal Fields
    public $isSecurityVerificationModalOpen = false;
    public $verificationMethod = 'password'; // 'password' or 'otp'
    public $verificationInput = '';
    public $otpSent = false;
    public $verificationOtp = '';
    public $verificationError = '';

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
        $this->admin_action_pin_enabled = (bool) Setting::get('admin_action_pin_enabled', false);
        $this->admin_action_pin = Setting::get('admin_action_pin', '');
    }

    public function updatedAdminActionPinEnabled($value)
    {
        if ($value === false) {
            $currentlyEnabled = (bool) Setting::get('admin_action_pin_enabled', false);
            if ($currentlyEnabled) {
                // Instantly force it back to true in component state so the UI toggle doesn't turn off until verified
                $this->admin_action_pin_enabled = true;

                // Open the security confirmation modal
                $this->isSecurityVerificationModalOpen = true;
                $this->verificationMethod = 'password';
                $this->verificationInput = '';
                $this->otpSent = false;
                $this->verificationError = '';
            }
        }
    }

    public function sendVerificationOtp()
    {
        $user = auth()->user();
        if (!$user) return;

        // Generate 6-digit OTP
        $this->verificationOtp = (string) rand(100000, 999999);
        $this->otpSent = true;
        $this->verificationError = '';

        // Store OTP in session with timestamp
        session([
            'admin_security_otp' => $this->verificationOtp,
            'admin_security_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            $instituteName = Setting::get('institute_name', 'IMCB G-6/2');
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $instituteName) {
                $message->to($user->email)
                    ->subject('Security Toggle Disable Code - ' . $instituteName)
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                            <div style='text-align: center; margin-bottom: 24px;'>
                                <h2 style='color: #1e3a5f; margin: 0; font-size: 24px; font-weight: 800;'>{$instituteName}</h2>
                                <p style='color: #64748b; margin: 4px 0 0 0; font-size: 13px;'>Security Action Verification</p>
                            </div>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 24px;'>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>Hello {$user->name},</p>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>You requested to disable <strong>Require PIN for Admin Modifications</strong>. Use the following 6-digit OTP code to verify your identity and complete this action:</p>
                            <div style='text-align: center; margin: 36px 0;'>
                                <span style='font-size: 36px; font-weight: 800; letter-spacing: 6px; color: #b91c1c; padding: 12px 24px; background-color: #fef2f2; border-radius: 8px; border: 1px solid #fee2e2; display: inline-block;'>{$this->verificationOtp}</span>
                            </div>
                            <p style='color: #ef4444; font-size: 13px; line-height: 1.5; margin-bottom: 0;'><strong>Note:</strong> This verification code is valid for 10 minutes. If you did not make this request, please change your password immediately.</p>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 24px; margin-bottom: 24px;'>
                            <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0;'>© " . date('Y') . " Adminova. All rights reserved.</p>
                        </div>
                    ");
            });
            session()->flash('otp_status', 'Verification OTP has been sent to your email.');
        } catch (\Exception $e) {
            $this->verificationError = 'Failed to send OTP: ' . $e->getMessage();
            $this->otpSent = false;
        }
    }

    public function verifySecurityAction()
    {
        $this->verificationError = '';

        if ($this->verificationMethod === 'password') {
            if (empty($this->verificationInput)) {
                $this->verificationError = 'Password is required.';
                return;
            }

            if (!\Illuminate\Support\Facades\Hash::check($this->verificationInput, auth()->user()->password)) {
                $this->verificationError = 'Incorrect password.';
                return;
            }
        } else {
            // OTP verification
            if (empty($this->verificationInput)) {
                $this->verificationError = 'OTP code is required.';
                return;
            }

            $sessionOtp = session('admin_security_otp');
            $expiresAt = session('admin_security_otp_expires_at');

            if (!$sessionOtp || !$expiresAt || now()->greaterThan($expiresAt)) {
                $this->verificationError = 'OTP code has expired or is invalid. Please request a new one.';
                return;
            }

            if ($this->verificationInput !== $sessionOtp) {
                $this->verificationError = 'Incorrect OTP code.';
                return;
            }

            // Clear session OTP
            session()->forget(['admin_security_otp', 'admin_security_otp_expires_at']);
        }

        // Verification successful! Disable the toggle and save it immediately to database.
        $this->admin_action_pin_enabled = false;
        Setting::set('admin_action_pin_enabled', false);
        Setting::set('admin_action_pin', '');
        
        $this->isSecurityVerificationModalOpen = false;
        $this->verificationInput = '';
        $this->otpSent = false;
        
        session()->flash('status', 'Admin Action Security disabled successfully.');
    }

    public function closeSecurityVerificationModal()
    {
        $this->isSecurityVerificationModalOpen = false;
        $this->verificationInput = '';
        $this->otpSent = false;
        $this->verificationError = '';
        // Restore correct toggle status from database
        $this->admin_action_pin_enabled = (bool) Setting::get('admin_action_pin_enabled', false);
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
