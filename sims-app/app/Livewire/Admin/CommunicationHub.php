<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Classes;
use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class CommunicationHub extends Component
{
    use WithFileUploads;

    // Recipient Selection
    public $selectedClasses = []; // Array of class IDs
    public $selectedStudents = []; // Array of student IDs
    
    // Message Content
    public $messageType = 'text'; 
    public $messageText = '';
    public $attachment; 
    public $voiceBlob; 

    // Data Loading
    public $classes = [];
    public $availableStudents = []; // Students to show in list (grouped by class_id)

    // UI State
    public $sending = false;
    public $successMessage = '';
    public $errorMessage = '';

    protected $rules = [
        'selectedStudents' => 'required|array|min:1',
        'messageText' => 'required_without:attachment',
    ];

    public function mount()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $this->classes = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $activeSessionId)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
            ->orderBy('numeric_value')
            ->get();
    }

    // When classes are checked/unchecked
    public function updatedSelectedClasses()
    {
        // 1. Fetch students for selected classes
        if (!empty($this->selectedClasses)) {
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

            $students = Student::join('enrollments', 'students.id', '=', 'enrollments.student_id')
                ->whereIn('enrollments.class_id', $this->selectedClasses)
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('enrollments.shift_type', $shiftType);
                })
                ->orderBy('enrollments.class_id')
                ->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')
                ->select('students.*', 'enrollments.class_id', 'enrollments.roll_number as roll_no')
                ->get();
                
            $this->availableStudents = $students->groupBy('class_id')->toArray();
            
            // 2. Intelligent Selection Logic
            // Current valid student IDs based on selected classes
            $validStudentIds = $students->pluck('id')->toArray();
            
            // Filter selectedStudents to only include valid ones (removes students from unchecked classes)
            $this->selectedStudents = array_intersect($this->selectedStudents, $validStudentIds);
            
        } else {
            $this->availableStudents = [];
            $this->selectedStudents = [];
        }
    }

    public function toggleClassStudents($classId)
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        // Helper to select/deselect all students of a specific class
        $classStudents = Student::join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.class_id', $classId)
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.status', 'active')
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('enrollments.shift_type', $shiftType);
            })
            ->pluck('students.id')
            ->toArray();
        
        // Check if all are currently selected
        $intersect = array_intersect($classStudents, $this->selectedStudents);
        $allSelected = count($intersect) === count($classStudents);
        
        if ($allSelected) {
            // Deselect all
            $this->selectedStudents = array_diff($this->selectedStudents, $classStudents);
        } else {
             // Select all
             $this->selectedStudents = array_unique(array_merge($this->selectedStudents, $classStudents));
        }
    }

    public function sendMessage()
    {
        $this->validate();
        $this->sending = true;
        $this->successMessage = '';
        $this->errorMessage = '';

        try {
            $whatsapp = app(WhatsAppService::class);
            
            if (!$whatsapp->isConnected()) {
                $this->addError('whatsapp', 'WhatsApp is not connected. Please go to WhatsApp Setup first.');
                $this->sending = false;
                return;
            }

            // 1. Gather Recipients
            // Fetch select students with phone numbers
            $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

            $recipients = Student::join('enrollments', 'students.id', '=', 'enrollments.student_id')
                ->whereIn('students.id', $this->selectedStudents)
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->whereNotNull('students.phone')
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('enrollments.shift_type', $shiftType);
                })
                ->select('students.phone', 'students.name', 'students.id')
                ->get()
                ->map(function ($s) {
                    return [
                        'phone' => $s->phone,
                        'name' => $s->name,
                        'id' => $s->id
                    ];
                });

            if ($recipients->isEmpty()) {
                $this->addError('recipients', 'No students selected with valid phone numbers.');
                $this->sending = false;
                return;
            }

            // 2. Prepare Message Data
            $filePath = null;
            if ($this->attachment) {
                $filePath = $this->attachment->store('temp_whatsapp', 'local');
                $fullPath = storage_path('app/' . $filePath);
            }

            // 3. Send Messages
            $count = 0;
            $isVoice = $this->messageType === 'voice';

            foreach ($recipients as $recipient) {
                if ($this->messageType === 'text') {
                    $result = $whatsapp->sendMessage($recipient['phone'], $this->messageText);
                } else {
                    $result = $whatsapp->sendMediaMessage(
                        $recipient['phone'], 
                        $this->messageText, 
                        $fullPath, 
                        $isVoice
                    );
                }
                
                if ($result['success'] ?? false) $count++;
            }

            // Cleanup
            if ($filePath) {
                @unlink($fullPath);
            }

            $this->successMessage = "Message sent successfully to $count parents!";
            $this->reset(['messageText', 'attachment', 'messageType', 'selectedClasses', 'selectedStudents', 'availableStudents']);
            $this->messageType = 'text';

        } catch (\Exception $e) {
            Log::error('Communication Hub Error: ' . $e->getMessage());
            $this->errorMessage = 'Failed to send message: ' . $e->getMessage();
        } finally {
            $this->sending = false;
        }
    }

    protected function getRecipients()
    {
        $query = Student::where('status', 'active')->whereNotNull('phone');

        if ($this->recipientType === 'class') {
            $query->where('class_id', $this->selectedClassId);
        } elseif ($this->recipientType === 'student') {
            $query->where('id', $this->selectedStudentId);
        }
        // 'all_classes' fetches everyone

        return $query->get()->map(function ($s) {
            return [
                'phone' => $s->phone,
                'name' => $s->name,
                'id' => $s->id
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.admin.communication-hub')->layout('components.layouts.admin', ['title' => 'Communication Hub']);
    }
}
