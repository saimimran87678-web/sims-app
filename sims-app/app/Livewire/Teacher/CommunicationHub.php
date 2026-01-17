<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Classes;
use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommunicationHub extends Component
{
    use WithFileUploads;

    // Recipient Selection
    public $selectedClasses = []; 
    public $selectedStudents = [];
    
    // Message Content
    public $messageType = 'text'; 
    public $messageText = '';
    public $attachment; 
    public $voiceBlob; 

    // Data Loading
    public $classes = [];
    public $availableStudents = []; 

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
        $user = Auth::user();
        
        $classTeacherIds = $user->class_id ? collect([$user->class_id]) : collect();

        $subjectClassIds = DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->pluck('class_id');
            
        $allClassIds = $classTeacherIds->merge($subjectClassIds)->unique();
        
        $this->classes = Classes::whereIn('id', $allClassIds)->get();
    }

    public function updatedSelectedClasses()
    {
        if (!empty($this->selectedClasses)) {
            $students = Student::whereIn('class_id', $this->selectedClasses)
                ->orderBy('class_id')
                ->orderBy('roll_no')
                ->get();
                
            $this->availableStudents = $students->groupBy('class_id')->toArray();
            
            // Sync selection: Keep only students from currently selected classes
            $validStudentIds = $students->pluck('id')->toArray();
            $this->selectedStudents = array_intersect($this->selectedStudents, $validStudentIds);
            
        } else {
            $this->availableStudents = [];
            $this->selectedStudents = [];
        }
    }

    public function toggleClassStudents($classId)
    {
        $classStudents = Student::where('class_id', $classId)->pluck('id')->toArray();
        $intersect = array_intersect($classStudents, $this->selectedStudents);
        $allSelected = count($intersect) === count($classStudents);
        
        if ($allSelected) {
            $this->selectedStudents = array_diff($this->selectedStudents, $classStudents);
        } else {
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
                $this->addError('whatsapp', 'WhatsApp is not connected. Please ask Admin to connect it.');
                $this->sending = false;
                return;
            }

            // 1. Gather Recipients
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
            Log::error('Teacher Communication Hub Error: ' . $e->getMessage());
            $this->errorMessage = 'Failed to send: ' . $e->getMessage();
        } finally {
            $this->sending = false;
        }
    }

    public function render()
    {
        return view('livewire.teacher.communication-hub')->layout('components.layouts.teacher', ['title' => 'Communication Hub']);
    }
}
