<div class="space-y-6">
    <x-slot name="header">Class Management</x-slot>

    <div class="glass-card p-6 rounded-2xl">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Manage Classes</h3>
            <div class="flex flex-col md:flex-row gap-3 items-center w-full md:w-auto">
                @can('classes.view-sessions')
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <select 
                            wire:model.live="selectedSessionId" 
                            class="appearance-none pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm w-40"
                        >
                            @foreach($academicSessions as $session)
                                <option value="{{ $session->id }}">
                                    {{ $session->name }} {{ $session->is_active ? '(Current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                             <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    @can('sessions.manage')
                        <a href="{{ route('admin.academic-sessions') }}" class="p-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors border border-gray-200" title="Manage Sessions">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    @endcan
                </div>
                @endrole

                @can('class.create')
                <div class="flex flex-col gap-1 w-full md:w-auto">
                    <div class="flex gap-2 items-center">
                        <div class="flex items-center">
                            <span class="px-3 py-2 bg-gray-100 text-gray-600 rounded-l-xl border border-r-0 border-gray-200 text-sm font-medium">Class</span>
                            <input
                                wire:model="name"
                                wire:keydown.enter="save"
                                type="text"
                                placeholder="e.g. 11B"
                                class="px-4 py-2 rounded-r-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm w-24"
                            />
                        </div>
                        <button
                            wire:click="save"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2 shadow-lg shadow-blue-200"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Add Class
                        </button>
                    </div>
                    @error('name') <span class="text-red-500 text-xs px-1">{{ $message }}</span> @enderror
                </div>
                @endcan
            </div>
        </div>
        
        @if (session()->has('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($classes as $class)
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col gap-3 group">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 block">{{ $class->name }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $class->subjects_count }} Subjects
                                </span>
                            </div>
                        </div>
                        @can('class.delete')
                        <button
                            wire:click="delete({{ $class->id }})"
                            wire:confirm="Are you sure? This will delete the class and all associated subjects."
                            class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-2 hover:bg-red-50 rounded-lg"
                            title="Delete Class"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                        @endcan
                    </div>

                    @can('subjects.manage')
                    <button
                        wire:click="openSubjectModal({{ $class->id }}, '{{ $class->name }}')"
                        class="w-full py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                    >
                        Manage Subjects
                    </button>
                    @endcan
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-gray-500">
                    No classes found. Add your first class above.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Subject Management Modal --}}
    @if($manageClassId)
    @teleport('body')
        <div class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                 <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                
                 <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Manage Subjects</h3>
                            <p class="text-sm text-gray-500">Class {{ $manageClassName }}</p>
                        </div>
                        <button
                            wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-full"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        @can('subject.create')
                        <div class="flex flex-col gap-1">
                            <div class="flex gap-2">
                                <input
                                    wire:model="newSubjectName"
                                    wire:keydown.enter="addSubject"
                                    type="text"
                                    placeholder="Enter subject name..."
                                    class="flex-1 px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                                />
                                <button
                                    wire:click="addSubject"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50"
                                >
                                    Add
                                </button>
                            </div>
                            @error('newSubjectName') <span class="text-red-500 text-xs px-1">{{ $message }}</span> @enderror
                        </div>
                        @endcan

                        <div class="max-h-64 overflow-y-auto space-y-2">
                            @forelse($classSubjects as $subject)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 block">{{ $subject->name }}</span>
                                        <span class="text-xs text-gray-400">{{ $subject->code }}</span>
                                    </div>
                                    @can('subject.delete')
                                    <button
                                        wire:click="deleteSubject({{ $subject->id }})"
                                        class="text-red-400 hover:text-red-600 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            @empty
                                <p class="text-center text-gray-500 text-sm py-4">No subjects added yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    @endteleport
    @endif
</div>
