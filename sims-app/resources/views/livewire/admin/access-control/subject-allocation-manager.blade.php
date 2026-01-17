<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Subject Allocation Manager (Gradebook Access)</h2>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>How this works:</strong> Use this to grant <strong>temporary or specific Gradebook access</strong> (e.g., for substitute teachers).
                    Allocations here <em>only</em> affect the Gradebook; they do not grant admin rights.
                    <ul class="list-disc list-inside mt-1 ml-1 text-xs text-blue-600">
                        <li><strong>Allocate:</strong> Grants permission to enter grades for this subject.</li>
                        <li><strong>Lock:</strong> Makes the gradebook "Read-Only" (prevents editing, keeps viewing).</li>
                        <li><strong>Remove:</strong> Completely revokes access (subject disappears from their list).</li>
                    </ul>
                </p>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Layout Container -->
    <div class="space-y-6">
        
        <!-- 1. User Selection (Top Context Bar) -->
        <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500">
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="flex-grow w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select User to Manage</label>
                    <div class="relative">
                        <select wire:model.live="selectedUserId" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 py-2 pl-3 pr-10">
                            <option value="">-- Choose Teacher / Staff --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ ucfirst($user->role) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="hidden md:block text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        @if($selectedUserId)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- 2. Grant Access Form (Vertical Stack) -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow-md h-full">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Grant New Access
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                <select wire:model.live="selectedClassId" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50 focus:bg-white transition-colors">
                                    <option value="">-- Select Class --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <select wire:model="selectedSubjectId" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50 focus:bg-white transition-colors" {{ !$selectedClassId ? 'disabled' : '' }}>
                                    <option value="">-- Select Subject --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button wire:click="allocate" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition-all transform hover:scale-[1.02]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Allocate Subject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Current Allocations List (Table) -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 rounded-lg shadow-md h-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Current Allocations</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Total: {{ count($allocations) }}
                            </span>
                        </div>
                        
                        @if(count($allocations) > 0)
                            <div class="overflow-hidden border rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/4">Class</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/2">Subject</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-1/4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($allocations as $alloc)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $alloc->class_name }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                        <span class="font-medium text-gray-800">{{ $alloc->subject_name }}</span>
                                                        @if(isset($alloc->is_inherent) && $alloc->is_inherent)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                                Class Teacher
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div class="flex items-center justify-end gap-2">
                                                        {{-- Lock Button --}}
                                                        <button 
                                                            wire:click="toggleLock({{ $alloc->class_id }}, {{ $alloc->subject_id }})" 
                                                            class="{{ isset($alloc->is_locked) && $alloc->is_locked ? 'text-red-700 bg-red-100 border border-red-200 hover:bg-red-200' : 'text-gray-600 bg-gray-100 border border-gray-200 hover:bg-gray-200 hover:text-gray-900' }} px-3 py-1 rounded shadow-sm text-xs font-bold transition-all"
                                                            title="{{ isset($alloc->is_locked) && $alloc->is_locked ? 'Unlock Gradebook' : 'Lock Gradebook' }}"
                                                        >
                                                            {{ isset($alloc->is_locked) && $alloc->is_locked ? 'Locked' : 'Lock' }}
                                                        </button>

                                                        @if(!isset($alloc->is_inherent) || !$alloc->is_inherent)
                                                            <button wire:click="deallocate({{ $alloc->id }})" class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded" title="Remove Allocation">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="text-sm">No manual allocations yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center h-64 bg-white rounded-lg border-2 border-dashed border-gray-300">
                <div class="text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="block text-lg font-medium text-gray-600">Select a User to Begin</span>
                    <p class="text-sm mt-1">Choose a teacher from the list above to view and manage their assignments.</p>
                </div>
            </div>
        @endif
    </div>
</div>
</div>
