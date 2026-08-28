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

    // Active Tab Navigation
    public $activeTab = 'setup'; // 'setup', 'queue', 'templates'

    // Connection Status
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

    // Message Templates
    public $templateAbsent;
    public $templateLeave;
    public $templateLate;
    public $templatePayment;
    public $templateReminder;

    protected $queryString = [
        'activeTab' => ['except' => 'setup', 'as' => 'tab'],
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

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
        $this->authorize('students.manage');

        // Allow tab switching via query parameter or route name
        if (request()->has('tab')) {
            $this->activeTab = request('tab');
        } elseif (request()->routeIs('admin.whatsapp-templates')) {
            $this->activeTab = 'templates';
        }

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

        // Load Templates
        $defaultAbsent = "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is ABSENT from school today ({date}).\nPlease contact the Class Teacher and give a valid reason.\n\n- {school_name} Administration";
        $this->templateAbsent = \App\Models\Setting::get("whatsapp_template_absent_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_absent', $defaultAbsent));

        $defaultLeave = "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is on LEAVE today ({date}).\n\n- {school_name} Administration";
        $this->templateLeave = \App\Models\Setting::get("whatsapp_template_leave_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_leave', $defaultLeave));

        $defaultLate = "*Urgent Message*\n\nDear Parents,\nWe noticed that your {relation} {student_name} (Roll No: {roll_no}) was marked absent/leave, but has now arrived late at school today at {time}.\nPlease ensure they arrive on time in the future to avoid any warning.\n\n- {school_name} Administration";
        $this->templateLate = \App\Models\Setting::get("whatsapp_template_late_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_late', $defaultLate));

        $defaultPayment = "*Payment Confirmation*\n\nDear Parents,\nWe have received a payment of Rs. {amount} for {student_name} for the period {period}.\nRemaining Balance: Rs. {balance}\n\nView updated receipt:\n{challan_link}\n\nThank you.\n- {school_name} Administration";
        $this->templatePayment = \App\Models\Setting::get("whatsapp_template_payment_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_payment', $defaultPayment));

        $defaultReminder = "*Fee Reminder*\n\nDear Parents,\nThis is a friendly reminder that a fee balance of Rs. {balance} is pending for {student_name} for the period {period}.\nPlease pay by {due_date} to avoid late charges.\n\nView voucher:\n{challan_link}\n\n- {school_name} Administration";
        $this->templateReminder = \App\Models\Setting::get("whatsapp_template_reminder_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_reminder', $defaultReminder));

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

        session()->flash('message', 'Auto-send settings saved successfully.');

        try {
            $artisanPath = base_path('artisan');
            shell_exec("php {$artisanPath} whatsapp:process-queue > /dev/null 2>&1 &");
        } catch (\Exception $e) {
            Log::error('Failed to launch queue daemon: ' . $e->getMessage());
        }
    }

    public function saveTemplates()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $scopedShift = ($shiftType === 'both') ? 'morning' : $shiftType;

        \App\Models\Setting::set("whatsapp_template_absent_{$scopedShift}", $this->templateAbsent);
        \App\Models\Setting::set("whatsapp_template_leave_{$scopedShift}", $this->templateLeave);
        \App\Models\Setting::set("whatsapp_template_late_{$scopedShift}", $this->templateLate);
        \App\Models\Setting::set("whatsapp_template_payment_{$scopedShift}", $this->templatePayment);
        \App\Models\Setting::set("whatsapp_template_reminder_{$scopedShift}", $this->templateReminder);

        session()->flash('message', 'All WhatsApp message templates saved successfully.');
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
        ])->layout('components.layouts.admin', ['title' => 'WhatsApp Manager']);
    }
}
