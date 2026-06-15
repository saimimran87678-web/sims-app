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
        $this->classes = Classes::where('academic_session_id', $activeSessionId)->get();
    }

    // When classes are checked/unchecked
    public function updatedSelectedClasses()
    {
        // 1. Fetch students for selected classes
        if (!empty($this->selectedClasses)) {
            $students = Student::whereIn('class_id', $this->selectedClasses)
                ->orderBy('class_id')
                ->orderByRaw('CAST(roll_no AS INTEGER) ASC')
                ->get();
                
            $this->availableStudents = $students->groupBy('class_id')->toArray();
            
            // 2. Intelligent Selection Logic
            // If we just added a class, select its students by default
            // If we removed a class, remove its students from selectedStudents
            
            // Current valid student IDs based on selected classes
            $validStudentIds = $students->pluck('id')->toArray();
            
            // Filter selectedStudents to only include valid ones (removes students from unchecked classes)
            $this->selectedStudents = array_intersect($this->selectedStudents, $validStudentIds);
            
            // Add new students (if logic dictates auto-select). 
            // Let's implement: If a class is in selectedClasses, ensure its students are selected 
            // UNLESS explicitly unchecked? That's hard to track.
            // Simple approach: When a class is checked, add all its students. User can uncheck.
            // Problem: updatedSelectedClasses runs on every toggle. 
            
            // Better Approach for UX:
            // Just load availableStudents. Let user check "Select All" for a class if they want, 
            // or we pre-fill.
            
            // Let's pre-fill for now to match "Select Class -> Send to Class" ease of use.
            // But we shouldn't re-select students if user manually unchecked them.
            // Complexity: Livewire doesn't give us "old" value easily here.
            
            // simplified: We will rely on the View to have "Select All" buttons for each class group.
            // But to make it "appear for selection", simply populate availableStudents is enough.
            // The User requested: "which class got selected box checked its students will appear for selection"
            
        } else {
            $this->availableStudents = [];
            $this->selectedStudents = [];
        }
    }

    public function toggleClassStudents($classId)
    {
        // Helper to select/deselect all students of a specific class
        $classStudents = Student::where('class_id', $classId)->pluck('id')->toArray();
        
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
            $recipients = Student::whereIn('id', $this->selectedStudents)
                ->whereNotNull('phone')
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
        $query = Student::where('is_active', true)->whereNotNull('phone');

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
