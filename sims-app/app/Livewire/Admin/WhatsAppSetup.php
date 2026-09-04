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
    #[\Livewire\Attributes\Url(as: 'tab', except: 'setup')]
    public $activeTab = 'setup'; // 'setup', 'queue', 'templates'

    // Connection Status & Credentials
    public $status = [];
    public $qrData = null;
    public $isConnected = false;
    public $errorMessage = null;

    public $serviceUrl;
    public $apiKey;
    public $isApiKeySaved = false;
    public $isEditingApiKey = false;
    public $apiTestResult = null;

    // Pairing Code
    public $pairingPhone = '';
    public $pairingCodeResult = null;

    // Queue Settings
    public $queueDelay;
    public $autoSendEnabled;
    public $autoSendStart;
    public $autoSendEnd;
    public $forceSendNow;

    // Filters for Queue Table
    #[\Livewire\Attributes\Url(except: '')]
    public $filterStatus = '';

    #[\Livewire\Attributes\Url(except: '')]
    public $search = '';

    // Message Templates
    public $templateAbsent;
    public $templateLeave;
    public $templateLate;
    public $templatePayment;
    public $templateIssuance;
    public $templateReminder;

    public $paginationTheme = 'tailwind';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

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

        // Load Service Credentials
        $this->serviceUrl = \App\Models\Setting::get('whatsapp_service_url', config('services.whatsapp.url', 'http://localhost:3000'));
        $savedKey = \App\Models\Setting::get('whatsapp_api_key');
        if (!empty($savedKey)) {
            $this->isApiKeySaved = true;
            $this->isEditingApiKey = false;
            $this->apiKey = $savedKey;
        } else {
            $this->isApiKeySaved = false;
            $this->isEditingApiKey = true;
            $this->apiKey = config('services.whatsapp.key', 'whatsapp12345');
        }

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
        $valAbsent = \App\Models\Setting::get("whatsapp_template_absent_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_absent'));
        $this->templateAbsent = (!is_null($valAbsent) && trim($valAbsent) !== '') ? $valAbsent : $defaultAbsent;

        $defaultLeave = "*Auto Generated Message*\n\nDear Parents,\nYour {relation} {student_name} (Roll No: {roll_no}) is on LEAVE today ({date}).\n\n- {school_name} Administration";
        $valLeave = \App\Models\Setting::get("whatsapp_template_leave_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_leave'));
        $this->templateLeave = (!is_null($valLeave) && trim($valLeave) !== '') ? $valLeave : $defaultLeave;

        $defaultLate = "*Urgent Message*\n\nDear Parents,\nWe noticed that your {relation} {student_name} (Roll No: {roll_no}) was marked absent/leave, but has now arrived late at school today at {time}.\nPlease ensure they arrive on time in the future to avoid any warning.\n\n- {school_name} Administration";
        $valLate = \App\Models\Setting::get("whatsapp_template_late_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_late'));
        $this->templateLate = (!is_null($valLate) && trim($valLate) !== '') ? $valLate : $defaultLate;

        $defaultPayment = "*Payment Confirmation*\n\nDear Parents,\nWe have received a payment of Rs. {amount} for {student_name} for the period {period}.\nRemaining Balance: Rs. {balance}\n\nView updated receipt:\n{challan_link}\n\nThank you.\n- {school_name} Administration";
        $valPayment = \App\Models\Setting::get("whatsapp_template_payment_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_payment'));
        $this->templatePayment = (!is_null($valPayment) && trim($valPayment) !== '') ? $valPayment : $defaultPayment;

        $defaultIssuance = "*Fee Voucher Issued*\n\nDear Parents,\nFee voucher of Rs. {amount} for {student_name} for the period {period} has been issued. Due date: {due_date}.\n\nView digital voucher:\n{challan_link}\n\n- {school_name} Administration";
        $valIssuance = \App\Models\Setting::get("whatsapp_template_issuance_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_issuance'));
        $this->templateIssuance = (!is_null($valIssuance) && trim($valIssuance) !== '') ? $valIssuance : $defaultIssuance;

        $defaultReminder = "*Fee Reminder*\n\nDear Parents,\nThis is a friendly reminder that a fee balance of Rs. {balance} is pending for {student_name} for the period {period}.\nPlease pay by {due_date} to avoid late charges.\n\nView voucher:\n{challan_link}\n\n- {school_name} Administration";
        $valReminder = \App\Models\Setting::get("whatsapp_template_reminder_{$scopedShift}", \App\Models\Setting::get('whatsapp_template_reminder'));
        $this->templateReminder = (!is_null($valReminder) && trim($valReminder) !== '') ? $valReminder : $defaultReminder;

        $this->refreshStatus();
    }

    public function enableApiKeyEdit()
    {
        $this->isEditingApiKey = true;
        $this->apiKey = '';
    }

    public function cancelApiKeyEdit()
    {
        if ($this->isApiKeySaved) {
            $this->isEditingApiKey = false;
            $this->apiKey = \App\Models\Setting::get('whatsapp_api_key');
        }
    }

    public function testApiConnection()
    {
        $whatsapp = new WhatsAppService();
        $this->apiTestResult = $whatsapp->testConnection($this->serviceUrl, $this->apiKey);
    }

    public function saveApiCredentials()
    {
        $this->validate([
            'serviceUrl' => 'required|url',
            'apiKey' => 'required|string|min:3',
        ]);

        \App\Models\Setting::set('whatsapp_service_url', rtrim($this->serviceUrl, '/'));
        \App\Models\Setting::set('whatsapp_api_key', trim($this->apiKey));

        $this->isApiKeySaved = true;
        $this->isEditingApiKey = false;

        session()->flash('message', 'WhatsApp Service URL & API Key updated successfully.');
        $this->apiTestResult = [
            'success' => true,
            'message' => 'Credentials saved to system settings!'
        ];

        $this->refreshStatus();
    }

    public function requestPairingCode()
    {
        $this->validate([
            'pairingPhone' => 'required|string|min:8',
        ]);

        try {
            $whatsapp = new WhatsAppService();
            $this->pairingCodeResult = $whatsapp->requestPairingCode($this->pairingPhone);
        } catch (\Exception $e) {
            $this->pairingCodeResult = [
                'success' => false,
                'error' => 'Failed to request pairing code: ' . $e->getMessage()
            ];
        }
    }

    public function refreshStatus()
    {
        try {
            $whatsapp = new WhatsAppService();
            $this->status = $whatsapp->getStatus();
            $this->isConnected = $this->status['ready'] ?? $this->status['isReady'] ?? false;
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
            $whatsapp = new WhatsAppService();
            $whatsapp->logout();
            $this->errorMessage = "Logged out successfully. Waiting for new QR code or pairing code...";
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

        if ($this->forceSendNow || $this->autoSendEnabled) {
            $this->processQueueBatch();
        }
    }

    public function updatedAutoSendEnabled($value)
    {
        $this->saveSettings();
        $this->processQueueBatch();
    }

    public function updatedForceSendNow($value)
    {
        $this->saveSettings();
        $this->processQueueBatch();
    }

    public function processQueueBatch()
    {
        $shouldProcess = false;

        if ($this->forceSendNow) {
            $shouldProcess = true;
        } elseif ($this->autoSendEnabled) {
            $now = \Carbon\Carbon::now();
            $start = \Carbon\Carbon::createFromTimeString($this->autoSendStart ?: '09:00');
            $end = \Carbon\Carbon::createFromTimeString($this->autoSendEnd ?: '22:00');
            if ($now->between($start, $end)) {
                $shouldProcess = true;
            }
        }

        if (!$shouldProcess) {
            return;
        }

        $whatsapp = app(WhatsAppService::class);
        if (!$whatsapp->isConnected()) {
            return;
        }

        $pendingMessages = DB::table('whatsapp_queue')
            ->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        foreach ($pendingMessages as $msg) {
            $result = $whatsapp->sendMessage($msg->phone, $msg->message);
            if ($result['success'] ?? false) {
                DB::table('whatsapp_queue')->where('id', $msg->id)->update([
                    'status' => 'sent',
                    'updated_at' => now(),
                    'error_message' => null
                ]);
            } else {
                DB::table('whatsapp_queue')->where('id', $msg->id)->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Service offline',
                    'updated_at' => now()
                ]);
            }
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
        \App\Models\Setting::set("whatsapp_template_issuance_{$scopedShift}", $this->templateIssuance);
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

    public function retryMessage($id)
    {
        $this->sendManual($id);
    }

    public function sendManual($id)
    {
        $msg = DB::table('whatsapp_queue')->find($id);
        if ($msg) {
            $whatsapp = app(WhatsAppService::class);
            $result = $whatsapp->sendMessage($msg->phone, $msg->message);
            if ($result['success'] ?? false) {
                DB::table('whatsapp_queue')->where('id', $id)->update([
                    'status' => 'sent', 
                    'updated_at' => now(),
                    'error_message' => null
                ]);
                session()->flash('message', 'Message dispatched successfully!');
            } else {
                DB::table('whatsapp_queue')->where('id', $id)->update([
                    'status' => 'failed', 
                    'error_message' => $result['error'] ?? 'Unknown error', 
                    'updated_at' => now()
                ]);
                session()->flash('error', 'Failed to send message: ' . ($result['error'] ?? 'Service offline'));
            }
        }
    }

    public function render()
    {
        if ($this->activeTab === 'queue') {
            $this->processQueueBatch();
        }

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
