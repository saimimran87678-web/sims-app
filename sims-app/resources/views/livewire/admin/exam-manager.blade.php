<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Exam Management</h1>
            <p class="text-gray-500">Create and manage exams</p>
        </div>
        @can('exam.create')
        <button
            wire:click="create"
            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 shadow-lg shadow-blue-200"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Create Exam
        </button>
        @endcan
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 flex items-center gap-2 animate-in fade-in slide-in-from-top-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
        
        {{-- Session Filter --}}
        @can('exams.view-sessions')
        <div class="relative w-40">
             <div class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
             </div>
             <select 
                wire:model.live="selectedSessionId" 
                class="w-full pl-9 pr-8 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20 text-gray-700 text-sm appearance-none cursor-pointer"
            >
                @foreach($this->academicSessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }} @if($session->is_active) (Current) @endif</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
           </div>
        </div>
        @endrole

        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search exams..."
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20 text-gray-700"
            />
        </div>
    </div>

    {{-- Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($exams as $exam)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $exam->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $exam->academicSession->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $exam->start_date }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $status = $exam->status;
                                $statusColor = match($status) {
                                    'Upcoming' => 'bg-yellow-100 text-yellow-800',
                                    'Ongoing' => 'bg-green-100 text-green-800',
                                    'Completed' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                {{ $status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @can('exam.datesheet')
                                @php
                                    $datesheetRoute = request()->is('teacher/*') 
                                        ? route('teacher.shared.datesheet', $exam->id)
                                        : route('admin.datesheet.manage', $exam->id);
                                @endphp
                                <a 
                                    href="{{ $datesheetRoute }}" 
                                    class="text-purple-600 hover:text-purple-900 transition-colors p-2 rounded-lg hover:bg-purple-50" 
                                    title="Manage Datesheet"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </a>
                                @endcan
                                @can('exam.edit')
                                <button 
                                    wire:click="edit({{ $exam->id }})" 
                                    class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50" 
                                    title="Edit Details"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                @endcan
                                @can('exam.delete')
                                <button 
                                    wire:click="delete({{ $exam->id }})" 
                                    wire:confirm="Are you sure you want to delete this exam?" 
                                    class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50" 
                                    title="Delete Exam"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $exams->links() }}
        </div>
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit="store">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            {{ $isEditMode ? 'Edit Exam' : 'Create New Exam' }}
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Exam Name</label>
                                <input type="text" wire:model="name" placeholder="e.g. Final Term 2024" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Academic Session</label>
                                <select wire:model="academic_session_id" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                                    @endforeach
                                </select>
                                @error('academic_session_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Exam Type</label>
                                    <select wire:model="type" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option value="">Select Type</option>
                                        <option value="First-Term">First-Term</option>
                                        <option value="Mid-Term">Mid-Term</option>
                                        <option value="Final-Term">Final-Term</option>
                                        <option value="Quiz">Quiz</option>
                                    </select>
                                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <div class="mt-2 text-sm text-gray-500 italic">
                                        Automatically calculated based on dates.
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea wire:model="description" rows="3" placeholder="Optional exam description" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                    @error('start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                    @error('end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            {{-- Class Selection Section --}}

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign Classes</label>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto p-2 border rounded-xl border-gray-100 bg-gray-50">
                                    @foreach($availableClasses as $class)
                                    @php $isSelected = is_array($selectedClasses) && in_array((string)$class->id, $selectedClasses); @endphp
                                    <div 
                                        wire:click="toggleClass('{{ $class->id }}')" 
                                        class="flex items-center gap-2 p-2 bg-white border cursor-pointer rounded-lg transition-all select-none {{ $isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-400' }}"
                                    >
                                        <div class="w-4 h-4 rounded border flex items-center justify-center {{ $isSelected ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300' }}">
                                            @if($isSelected)
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $class->name }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>


                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $isEditMode ? 'Update Exam' : 'Create Exam' }}
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Configuration Modal --}}
    @if($isConfigModalOpen)
    @teleport('body')
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" wire:click="closeConfigModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block w-full text-left align-bottom transition-all transform bg-white shadow-xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-5xl">
                <div class="px-6 py-6 bg-white sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">
                                Configure Marks: <span class="text-blue-600">{{ $configureExamName }}</span>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Set total marks and passing percentage for each subject</p>
                        </div>
                        <button wire:click="closeConfigModal" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Close</span>
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        {{-- 1. Class Selection --}}
                        <div>
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wider">Select Classes</h4>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 max-h-48 overflow-y-auto p-2 border rounded-xl border-gray-100 bg-gray-50">
                                @foreach($availableClasses as $class)
                                <label class="flex items-center gap-3 p-3 transition-colors bg-white border border-gray-200 cursor-pointer rounded-xl hover:border-blue-400">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="selectedClasses" 
                                        value="{{ $class->id }}" 
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <span class="text-sm font-medium text-gray-700">{{ $class->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                    {{-- 2. Marks Configuration per Class --}}
                        @if(count($selectedClasses) > 0)
                        <div>
                            <div class="max-h-[60vh] overflow-y-auto border border-gray-200 rounded-xl shadow-sm">
                                @foreach($selectedClasses as $classId)
                                @php 
                                    $classObj = $availableClasses->firstWhere('id', $classId); 
                                    $classConfigs = $marksConfigData[$classId] ?? [];
                                @endphp
                                @if($classObj)
                                <div class="bg-gray-100 px-4 py-2 sticky top-0 z-10 border-b border-gray-200 font-bold text-gray-800 flex justify-between items-center">
                                    <span>{{ $classObj->name }}</span>
                                    <button 
                                        wire:click="autoFillSubjects({{ $classId }})"
                                        class="text-xs bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-200 font-medium flex items-center gap-1"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Auto-fill Subjects
                                    </button>
                                </div>
                                
                                @if(empty($classConfigs))
                                    <div class="p-4 bg-white text-gray-400 text-sm italic text-center">
                                        No subjects configured. Click "Auto-fill Subjects" to add subjects from class management.
                                    </div>
                                @else
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Subject</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Total Marks</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Passing %</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($classConfigs as $subjectName => $config)
                                            <tr>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $subjectName }}
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <input 
                                                        type="number" 
                                                        wire:model="marksConfigData.{{ $classId }}.{{ $subjectName }}.total_marks" 
                                                        placeholder="100" 
                                                        min="1"
                                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                                    >
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <input 
                                                            type="number" 
                                                            wire:model="marksConfigData.{{ $classId }}.{{ $subjectName }}.passing_marks" 
                                                            placeholder="33" 
                                                            min="1"
                                                            max="100"
                                                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                                        >
                                                        <span class="text-gray-500 text-sm">%</span>
                                                        @php
                                                            $totalMarks = $config['total_marks'] ?? 100;
                                                            $passingPct = $config['passing_marks'] ?? 33;
                                                            $passingScore = round($totalMarks * $passingPct / 100, 1);
                                                        @endphp
                                                        <span class="text-xs text-gray-400">({{ $passingScore }} marks)</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                    <button wire:click="saveConfig" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-xl shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        Save Configuration
                    </button>
                    <button wire:click="closeConfigModal" type="button" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
    {{-- HUB MODAL / Datesheet Manager --}}
    @if($isManageModalOpen)
    @teleport('body')
    <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeManageModal"></div>

        {{-- Full-screen Scroll Container --}}
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                
                {{-- Modal Panel --}}
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-[95vw] h-[90vh] flex flex-col border border-gray-200">
                    
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Exam Datesheet</h3>
                            <p class="text-sm text-gray-500">{{ $manageExamName }}</p>
                        </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="openConfigModal({{ $manageExamId }})" class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm border border-transparent hover:shadow-md transition-all">
                            Configure Marks
                        </button>
                        <button onclick="window.print()" class="px-3 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg shadow-sm hover:shadow-md transition-all flex items-center gap-2">
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                             Print
                        </button>
                        <button onclick="window.open('{{ route('admin.exams.datesheet', $manageExamId) }}', '_blank')" class="px-3 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 rounded-lg shadow-sm hover:shadow-md transition-all">
                            View Template
                        </button>
                        <button wire:click="closeManageModal" class="ml-2 text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-colors">
                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="px-6 py-4 bg-white border-b border-gray-100 flex flex-wrap gap-4 justify-between items-center shrink-0">
                    {{-- Left: Class Filter (Styled like Filter Chips) --}}
                    <div class="flex-1 min-w-[300px]">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-2 tracking-wide">Include Classes:</label>
                        <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto">
                            @foreach($availableClasses as $class)
                            <label class="group relative flex items-center justify-center gap-2 px-3 py-1.5 border rounded-full cursor-pointer transition-all select-none {{ in_array((string)$class->id, $datesheetFilterClasses) ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm ring-2 ring-indigo-100' : 'bg-white border-gray-200 text-gray-600 hover:border-indigo-300 hover:bg-gray-50' }}">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="datesheetFilterClasses" 
                                    value="{{ $class->id }}" 
                                    class="sr-only" 
                                >
                                <span class="text-xs font-bold">{{ $class->name }}</span>
                                @if(in_array((string)$class->id, $datesheetFilterClasses))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right: Date Actions --}}
                    <div class="flex items-end gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Add Date Range</label>
                            <div class="flex items-center gap-2">
                                <input type="date" wire:model="genStartDate" class="px-2 py-1 text-sm border border-gray-300 rounded-lg">
                                <span class="text-gray-400">to</span>
                                <input type="date" wire:model="genEndDate" class="px-2 py-1 text-sm border border-gray-300 rounded-lg">
                                <button wire:click="addDateRange" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm font-medium">Add</button>
                            </div>
                        </div>
                         <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Add Single Date</label>
                            <div class="flex items-center gap-2">
                                <input type="date" wire:model="genSingleDate" class="px-2 py-1 text-sm border border-gray-300 rounded-lg">
                                <button wire:click="addSingleDate" class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-lg hover:bg-green-600 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pivot Table Content --}}
                <div class="flex-1 overflow-auto bg-gray-50 p-6 min-h-0">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
                        <div class="overflow-auto flex-1">
                        <table class="min-w-full divide-y divide-gray-200 relative">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-12 sticky left-0 z-20 bg-gray-50 shadow-[4px_0_12px_rgba(0,0,0,0.05)]">Date</th>
                                    @foreach($datesheetFilterClasses as $classId)
                                    @php $cls = $availableClasses->firstWhere('id', $classId); @endphp
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider min-w-[200px]">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded shadow-sm border border-indigo-100">{{ $cls->name ?? 'Unknown' }}</span>
                                        </div>
                                    </th>
                                    @endforeach
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider w-16">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($datesheetDates as $dateRow)
                                <tr class="hover:bg-gray-50 group">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 border-r border-gray-200 shadow-sm z-10">
                                        {{ $dateRow }} <br>
                                        <span class="text-xs text-gray-500 font-normal">{{ \Carbon\Carbon::parse($dateRow)->format('l') }}</span>
                                    </td>
                                    {{-- Loop Columns --}}
                                    @foreach($datesheetFilterClasses as $classId)
                                    <td class="px-6 py-4 whitespace-nowrap text-center relative group-hover:bg-indigo-50/20 transition-colors">
                                        @php 
                                            $currentSubjectId = $datesheetData[$dateRow][$classId] ?? null; 
                                            // Get subjects for this class
                                            $subjects = $this->getSubjectsForClass($classId);
                                        @endphp
                                                {{-- Subject Dropdown --}}
                                                <select 
                                                    wire:change="updateSchedule('{{ $dateRow }}', {{ $classId }}, $event.target.value)"
                                                    class="block w-full text-xs border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5 {{ $currentSubjectId ? 'bg-indigo-50 font-semibold text-indigo-700 border-indigo-200' : 'text-gray-400' }}"
                                                >
                                                    <option value="">-</option>
                                                    @foreach($this->getSubjectsForClass($classId) as $sub)
                                                        {{-- Smart Logic: Show if (Not Scheduled OR is Current Value) --}}
                                                        @php
                                                            $isScheduledElsewhere = in_array($sub->id, $this->scheduledSubjects[$classId] ?? []) && $sub->id != $currentSubjectId;
                                                        @endphp
                                                        
                                                        @if(!$isScheduledElsewhere)
                                                            <option value="{{ $sub->id }}" @selected($sub->id == $currentSubjectId)>
                                                                {{ $sub->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="removeDateRow('{{ $dateRow }}')" class="text-red-400 hover:text-red-600 p-1 hover:bg-red-50 rounded">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ count($datesheetFilterClasses) + 2 }}" class="px-6 py-10 text-center text-gray-400 italic">
                                        No dates added yet. Use the "Add Date Range" or "Add Single Date" tools above.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
                {{-- Footer Actions --}}
                <div class="px-6 py-4 bg-white border-t border-gray-100 flex justify-between items-center shrink-0">
                    {{-- Buttons Removed --}}
                    <div>
                        <button wire:click="closeManageModal" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium rounded-lg hover:shadow-lg hover:shadow-indigo-500/30 transition-all transform hover:-translate-y-0.5">
                            Done
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>
