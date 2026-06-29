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
    public $forceSendNow;

    public function mount()
    {
        $this->authorize('students.manage'); // Reuse existing permission
        
        // Load Queue Settings (global — not session-scoped)
        $this->queueDelay = \App\Models\Setting::getGlobal('whatsapp_queue_delay', 5);
        $this->autoSendEnabled = \App\Models\Setting::getGlobal('whatsapp_auto_send_enabled', 'false') === 'true';
        $this->autoSendStart = \App\Models\Setting::getGlobal('whatsapp_auto_send_start', '09:00');
        $this->autoSendEnd = \App\Models\Setting::getGlobal('whatsapp_auto_send_end', '22:00');
        $this->forceSendNow = \App\Models\Setting::getGlobal('whatsapp_force_send_now', 'false') === 'true';

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
        \App\Models\Setting::setGlobal('whatsapp_queue_delay', $this->queueDelay);
        \App\Models\Setting::setGlobal('whatsapp_auto_send_enabled', $this->autoSendEnabled ? 'true' : 'false');
        \App\Models\Setting::setGlobal('whatsapp_auto_send_start', $this->autoSendStart);
        \App\Models\Setting::setGlobal('whatsapp_auto_send_end', $this->autoSendEnd);
        \App\Models\Setting::setGlobal('whatsapp_force_send_now', $this->forceSendNow ? 'true' : 'false');

        session()->flash('message', 'Settings saved. Queue processor updated.');

        // Start the daemon in the background. The daemon itself checks the toggles
        // and time window on each loop iteration, so it self-manages start/stop.
        try {
            $artisanPath = base_path('artisan');
            shell_exec("php {$artisanPath} whatsapp:process-queue > /dev/null 2>&1 &");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to launch queue daemon: ' . $e->getMessage());
        }
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
            ->leftJoin('students', 'whatsapp_queue.student_id', '=', 'students.id')
            ->select('whatsapp_queue.*', 'students.name as student_name')
            ->orderBy('whatsapp_queue.id', 'desc')
            ->paginate(10);

        return view('livewire.admin.whatsapp-setup', [
            'queue' => $queue
        ])->layout('components.layouts.admin', ['title' => 'WhatsApp Setup']);
    }
}
