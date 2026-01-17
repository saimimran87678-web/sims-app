<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Communication Hub</h1>
            <p class="text-gray-500">Send WhatsApp messages to parents</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Configuration --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6 rounded-2xl space-y-6">
                
                {{-- Recipient Selection --}}
                <div class="space-y-6">
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">1. Select Classes</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-40 overflow-y-auto p-2 border rounded-lg bg-gray-50 uppercase text-xs font-semibold">
                            @foreach($classes as $class)
                                <label class="flex items-center space-x-2 cursor-pointer p-2 rounded hover:bg-white transition-colors">
                                    <input type="checkbox" wire:model.live="selectedClasses" value="{{ $class->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="text-gray-700">{{ $class->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if(empty($classes))
                            <p class="text-sm text-gray-500 italic">No classes assigned to you.</p>
                        @endif
                    </div>

                    @if(!empty($selectedClasses))
                        <div class="animate-fade-in-up">
                            <h3 class="font-bold text-gray-700 mb-2">2. Select Students</h3>
                            
                            @if(empty($availableStudents))
                                <div class="p-4 bg-yellow-50 text-yellow-700 rounded-xl border border-yellow-200">
                                    <p class="font-medium">No students found in the selected classes.</p>
                                    <p class="text-sm mt-1">Please check if the students are active and assigned to these classes.</p>
                                </div>
                            @else
                                <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                                    @foreach($availableStudents as $classId => $students)
                                        @php $className = $classes->where('id', $classId)->first()->name ?? 'Unknown Class'; @endphp
                                        <div wire:key="class-group-{{ $classId }}" class="border rounded-xl overflow-hidden">
                                            <div class="bg-gray-100 px-4 py-2 flex justify-between items-center">
                                                <span class="font-bold text-gray-700">{{ $className }}</span>
                                                <button wire:click="toggleClassStudents({{ $classId }})" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    Toggle All
                                                </button>
                                            </div>
                                            <div class="p-4 bg-white grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($students as $student)
                                                    <label wire:key="student-{{ $student['id'] }}" class="flex items-start space-x-2 cursor-pointer p-1 hover:bg-blue-50 rounded transition-colors">
                                                        <input type="checkbox" wire:model="selectedStudents" value="{{ $student['id'] }}" class="mt-1 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                                        <div class="text-sm">
                                                            <span class="font-semibold text-gray-800">{{ $student['roll_no'] }}</span>
                                                            <span class="text-gray-600 block text-xs">{{ $student['name'] }}</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedStudents') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                {{-- Message Type --}}
                <div class="space-y-4 pt-4 border-t">
                    <h3 class="font-bold text-gray-700">3. Message Content</h3>
                    
                    <div class="flex gap-4">
                        <button wire:click="$set('messageType', 'text')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $messageType === 'text' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Text Message
                        </button>
                        <button wire:click="$set('messageType', 'image')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $messageType === 'image' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Image
                        </button>
                        <button wire:click="$set('messageType', 'document')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $messageType === 'document' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Document
                        </button>
                        <button wire:click="$set('messageType', 'voice')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $messageType === 'voice' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Voice Note
                        </button>
                    </div>

                    {{-- Text / Caption Input --}}
                    @if($messageType !== 'voice')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $messageType === 'text' ? 'Message' : 'Caption (Optional)' }}
                            </label>
                            <textarea wire:model="messageText" rows="4" class="input-modern w-full" placeholder="Type your message here..."></textarea>
                            @error('messageText') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- File Upload --}}
                    @if(in_array($messageType, ['image', 'document']))
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center">
                            <input type="file" wire:model="attachment" class="hidden" id="teacher-file-upload" accept="{{ $messageType === 'image' ? 'image/*' : '.pdf,.doc,.docx' }}">
                            <label for="teacher-file-upload" class="cursor-pointer">
                                <div class="mx-auto w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-600">Click to upload {{ $messageType }}</span>
                            </label>
                            @if($attachment)
                                <p class="text-sm text-green-600 mt-2 font-medium">Selected: {{ $attachment->getClientOriginalName() }}</p>
                            @endif
                            @error('attachment') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Voice Recorder --}}
                    @if($messageType === 'voice')
                        <div x-data="voiceRecorder()" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gray-50">
                            
                            <div x-show="!isRecording && !hasRecording">
                                <button @mousedown="startRecording" @mouseup="stopRecording" @mouseleave="stopRecording"
                                    class="w-20 h-20 bg-red-500 rounded-full text-white shadow-lg hover:bg-red-600 active:scale-95 transition-all flex items-center justify-center mx-auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                    </svg>
                                </button>
                                <p class="mt-4 text-gray-600 font-medium">Hold to Record</p>
                            </div>

                            <div x-show="isRecording" class="space-y-4">
                                <div class="w-20 h-20 bg-red-600 rounded-full animate-pulse flex items-center justify-center mx-auto text-white">
                                    <span class="font-bold">REC</span>
                                </div>
                                <p class="text-red-600 font-medium">Recording...</p>
                            </div>

                            <div x-show="hasRecording" class="space-y-4">
                                <div class="mx-auto w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-green-600 font-medium">Voice Note Recorded!</p>
                                <button @click="reset" class="text-sm text-red-500 hover:text-red-700 underline">Record Again</button>
                            </div>

                        </div>
                    @endif

                    <div class="pt-4">
                        <x-primary-button wire:click="sendMessage" wire:loading.attr="disabled" class="w-full justify-center py-3">
                            <span wire:loading.remove>Send Message</span>
                            <span wire:loading>Sending...</span>
                        </x-primary-button>
                    </div>

                    @if($successMessage)
                        <div class="p-4 bg-green-100 text-green-700 rounded-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            {{ $successMessage }}
                        </div>
                    @endif
                    @if($errorMessage)
                        <div class="p-4 bg-red-100 text-red-700 rounded-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                            {{ $errorMessage }}
                        </div>
                    @endif
                    @error('whatsapp') 
                        <div class="p-4 bg-yellow-100 text-yellow-700 rounded-xl flex items-center gap-2">
                            {{ $message }}
                        </div>
                    @enderror

                </div>
            </div>
        </div>

        {{-- Right: Tips & Info --}}
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-2xl bg-blue-50 border border-blue-100">
                <h3 class="font-bold text-blue-800 mb-2">Tips</h3>
                <ul class="text-sm text-blue-700 space-y-2">
                    <li>• Check boxes to select classes and students.</li>
                    <li>• You can select students from multiple classes at once.</li>
                    <li>• Use voice notes for quick personalized feedback.</li>
                    <li>• Messages are sent sequentially from the school's number.</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Voice Recorder JS --}}
    <script>
        function voiceRecorder() {
            return {
                isRecording: false,
                hasRecording: false,
                mediaRecorder: null,
                chunks: [],

                async startRecording() {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.mediaRecorder = new MediaRecorder(stream);
                        this.chunks = [];

                        this.mediaRecorder.ondataavailable = e => this.chunks.push(e.data);
                        
                        this.mediaRecorder.onstop = () => {
                            const blob = new Blob(this.chunks, { type: 'audio/webm' });
                            const file = new File([blob], "voice_note.webm", { type: 'audio/webm' });
                            
                            @this.upload('attachment', file, (uploadedFilename) => {
                                // Success
                            }, () => {
                                // Error
                                alert('Error uploading voice note');
                            });

                            this.hasRecording = true;
                            
                            // Stop all tracks
                            stream.getTracks().forEach(track => track.stop());
                        };

                        this.mediaRecorder.start();
                        this.isRecording = true;

                    } catch (err) {
                        console.error('Error accessing microphone:', err);
                        alert('Could not access microphone. Please allow permissions.');
                    }
                },

                stopRecording() {
                    if (this.isRecording && this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                        this.isRecording = false;
                    }
                },

                reset() {
                    this.hasRecording = false;
                    @this.set('attachment', null);
                }
            }
        }
    </script>
</div>
