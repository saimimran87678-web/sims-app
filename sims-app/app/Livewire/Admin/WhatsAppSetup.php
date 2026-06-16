<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\WhatsAppService;

class WhatsAppSetup extends Component
{
    public $status = [];
    public $qrData = null;
    public $isConnected = false;
    public $errorMessage = null;

    // Queue Settings
    public $queueDelay;
    public $autoSendEnabled;
    public $autoSendStart;
    public $autoSendEnd;

    public function mount()
    {
        $this->authorize('students.manage'); // Reuse existing permission
        
        // Load Settings
        $this->queueDelay = \App\Models\Setting::get('whatsapp_queue_delay', 5);
        $this->autoSendEnabled = \App\Models\Setting::get('whatsapp_auto_send_enabled', 'false') === 'true';
        $this->autoSendStart = \App\Models\Setting::get('whatsapp_auto_send_start', '09:00');
        $this->autoSendEnd = \App\Models\Setting::get('whatsapp_auto_send_end', '22:00');

        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        try {
            $whatsapp = app(WhatsAppService::class);
            $this->status = $whatsapp->getStatus();
            $this->isConnected = $this->status['ready'] ?? false;
            $this->errorMessage = $this->status['error'] ?? null;

            if (!$this->isConnected) {
                $qrResponse = $whatsapp->getQrCode();
                $this->qrData = $qrResponse['qr'] ?? null;
            } else {
                $this->qrData = null;
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Cannot connect to WhatsApp service. Is it running?';
            $this->isConnected = false;
        }
    }

    public function logout()
    {
        try {
            $whatsapp = app(WhatsAppService::class);
            $whatsapp->logout();
            $this->errorMessage = "Logged out successfully. Waiting for new QR code...";
            $this->refreshStatus();
        } catch (\Exception $e) {
            $this->errorMessage = 'Logout failed: ' . $e->getMessage();
        }
    }

    public function saveSettings()
    {
        \App\Models\Setting::set('whatsapp_queue_delay', $this->queueDelay);
        \App\Models\Setting::set('whatsapp_auto_send_enabled', $this->autoSendEnabled ? 'true' : 'false');
        \App\Models\Setting::set('whatsapp_auto_send_start', $this->autoSendStart);
        \App\Models\Setting::set('whatsapp_auto_send_end', $this->autoSendEnd);
        session()->flash('message', 'Queue settings saved successfully.');
    }

    public function toggleMessageStatus($id)
    {
        $msg = \Illuminate\Support\Facades\DB::table('whatsapp_queue')->find($id);
        if ($msg) {
            $newStatus = $msg->status === 'paused' ? 'pending' : 'paused';
            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->where('id', $id)->update(['status' => $newStatus]);
        }
    }

    public function deleteMessage($id)
    {
        \Illuminate\Support\Facades\DB::table('whatsapp_queue')->where('id', $id)->delete();
    }

    public function sendManual($id)
    {
        $msg = \Illuminate\Support\Facades\DB::table('whatsapp_queue')->find($id);
        if ($msg) {
            $whatsapp = app(WhatsAppService::class);
            $result = $whatsapp->sendMessage($msg->phone, $msg->message);
            if ($result['success'] ?? false) {
                \Illuminate\Support\Facades\DB::table('whatsapp_queue')->where('id', $id)->update(['status' => 'sent', 'updated_at' => now()]);
            } else {
                \Illuminate\Support\Facades\DB::table('whatsapp_queue')->where('id', $id)->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Unknown error', 'updated_at' => now()]);
            }
        }
    }

    public function render()
    {
        $queue = \Illuminate\Support\Facades\DB::table('whatsapp_queue')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.whatsapp-setup', [
            'queue' => $queue
        ])->layout('components.layouts.admin', ['title' => 'WhatsApp Setup']);
    }
}
