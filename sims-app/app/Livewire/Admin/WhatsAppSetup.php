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

    public function mount()
    {
        $this->authorize('students.manage'); // Reuse existing permission
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

    public function render()
    {
        return view('livewire.admin.whatsapp-setup')->layout('components.layouts.admin', ['title' => 'WhatsApp Setup']);
    }
}
