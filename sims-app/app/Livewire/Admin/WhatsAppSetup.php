<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppSetup extends Component
{
    use WithPagination;

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

    // Filters for Queue Table
    public $filterStatus = '';
    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->authorize('students.manage'); // Reuse existing permission

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $scopedShift = ($shiftType === 'both') ? 'morning' : $shiftType;
        
        // Load Queue Settings scoped to session and shift
        $this->queueDelay = \App\Models\Setting::get("whatsapp_queue_delay_{$scopedShift}", 5);
        $this->autoSendEnabled = \App\Models\Setting::get("whatsapp_auto_send_enabled_{$scopedShift}", 'false') === 'true';
        $this->autoSendStart = \App\Models\Setting::get("whatsapp_auto_send_start_{$scopedShift}", '09:00');
        $this->autoSendEnd = \App\Models\Setting::get("whatsapp_auto_send_end_{$scopedShift}", '22:00');
        $this->forceSendNow = \App\Models\Setting::get("whatsapp_force_send_now_{$scopedShift}", 'false') === 'true';

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
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $scopedShift = ($shiftType === 'both') ? 'morning' : $shiftType;

        \App\Models\Setting::set("whatsapp_queue_delay_{$scopedShift}", $this->queueDelay);
        \App\Models\Setting::set("whatsapp_auto_send_enabled_{$scopedShift}", $this->autoSendEnabled ? 'true' : 'false');
        \App\Models\Setting::set("whatsapp_auto_send_start_{$scopedShift}", $this->autoSendStart);
        \App\Models\Setting::set("whatsapp_auto_send_end_{$scopedShift}", $this->autoSendEnd);
        \App\Models\Setting::set("whatsapp_force_send_now_{$scopedShift}", $this->forceSendNow ? 'true' : 'false');

        session()->flash('message', 'Settings saved. Queue processor updated.');

        try {
            $artisanPath = base_path('artisan');
            shell_exec("php {$artisanPath} whatsapp:process-queue > /dev/null 2>&1 &");
        } catch (\Exception $e) {
            Log::error('Failed to launch queue daemon: ' . $e->getMessage());
        }
    }

    public function toggleMessageStatus($id)
    {
        $msg = DB::table('whatsapp_queue')->find($id);
        if ($msg) {
            $newStatus = $msg->status === 'paused' ? 'pending' : 'paused';
            DB::table('whatsapp_queue')->where('id', $id)->update(['status' => $newStatus]);
        }
    }

    public function deleteMessage($id)
    {
        DB::table('whatsapp_queue')->where('id', $id)->delete();
        session()->flash('message', 'Message deleted from queue.');
    }

    public function sendManual($id)
    {
        $msg = DB::table('whatsapp_queue')->find($id);
        if ($msg) {
            $whatsapp = app(WhatsAppService::class);
            $result = $whatsapp->sendMessage($msg->phone, $msg->message);
            if ($result['success'] ?? false) {
                DB::table('whatsapp_queue')->where('id', $id)->update(['status' => 'sent', 'updated_at' => now()]);
                session()->flash('message', 'Message sent successfully!');
            } else {
                DB::table('whatsapp_queue')->where('id', $id)->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Unknown error', 'updated_at' => now()]);
                session()->flash('error', 'Failed to send message: ' . ($result['error'] ?? 'Service offline'));
            }
        }
    }

    public function render()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $query = DB::table('whatsapp_queue')
            ->leftJoin('students', 'whatsapp_queue.student_id', '=', 'students.id')
            ->leftJoin('enrollments', function($join) use ($activeSessionId) {
                $join->on('students.id', '=', 'enrollments.student_id')
                     ->where('enrollments.academic_session_id', '=', $activeSessionId);
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->where(function($q) use ($shiftType) {
                $q->where(function($sub) use ($shiftType) {
                    $sub->whereNotNull('whatsapp_queue.student_id')
                        ->whereNotNull('enrollments.id')
                        ->when($shiftType !== 'both', function ($s) use ($shiftType) {
                            $s->where('enrollments.shift_type', $shiftType);
                        });
                })->orWhereNull('whatsapp_queue.student_id');
            });

        if ($this->filterStatus) {
            $query->where('whatsapp_queue.status', $this->filterStatus);
        }

        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('students.name', 'like', $search)
                  ->orWhere('students.admission_no', 'like', $search)
                  ->orWhere('enrollments.roll_number', 'like', $search)
                  ->orWhere('whatsapp_queue.phone', 'like', $search)
                  ->orWhere('whatsapp_queue.message', 'like', $search);
            });
        }

        $queue = $query->select(
                'whatsapp_queue.*',
                'students.name as student_name',
                'students.admission_no',
                'enrollments.roll_number as roll_no',
                'classes.name as class_name'
            )
            ->orderBy('whatsapp_queue.id', 'desc')
            ->paginate(15);

        return view('livewire.admin.whatsapp-setup', [
            'queue' => $queue
        ])->layout('components.layouts.admin', ['title' => 'WhatsApp Setup']);
    }
}
