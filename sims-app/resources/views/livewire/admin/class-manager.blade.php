<div class="space-y-6"
     x-data
     @focus-class-input.window="() => { let el = document.getElementById('class-name-input'); if(el){ el.scrollIntoView({behavior:'smooth', block:'center'}); el.focus(); } }">
    <x-slot name="header">Class Management</x-slot>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="glass-card p-6 rounded-2xl">
        {{-- Header Row --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-800">
                    {{ $showTrash ? '🗑️ Trash' : 'Manage Classrooms' }}
                </h3>
                @if($showTrash)
                    <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full">Deleted Classrooms</span>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 items-center">
                {{-- Session Selector --}}
                @if(count($academicSessions) > 1)
                    <select wire:model.live="selectedSessionId" 
                            @if(!$canViewSessions) disabled @endif
                            class="pl-3 pr-10 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all {{ !$canViewSessions ? 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed' : '' }}">
                        @foreach($academicSessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Trash Toggle --}}
                <button
                    wire:click="toggleTrash"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center gap-2
                        {{ $showTrash ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    {{ $showTrash ? 'Back to Active' : 'View Trash' }}
                </button>

                {{-- Add Class --}}
                @if(!$showTrash)
                    @can('class.create')
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center">
                                <span class="px-3 py-2 bg-gray-100 text-gray-600 rounded-l-xl border border-r-0 border-gray-200 text-sm font-medium">Class</span>
                                <input
                                    id="class-name-input"
                                    wire:model="name"
                                    wire:keydown.enter="save"
                                    type="text"
                                    placeholder="e.g. 11B"
                                    class="px-4 py-2 rounded-r-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm w-24"
                                />
                            </div>
                            <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2 shadow-lg shadow-blue-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Add
                            </button>
                        </div>
                        @error('name') <span class="text-red-500 text-xs px-1">{{ $message }}</span> @enderror
                    </div>
                    @endcan
                @endif
            </div>
        </div>

        {{-- CLASS GRID --}}
        @if(!$showTrash)
        
        @if($hasFinalExam)
        <div class="mb-6 p-4 bg-gradient-to-r {{ $hasNextSession ? 'from-blue-50 to-indigo-50 border-blue-200/60' : 'from-yellow-50 to-orange-50 border-yellow-200/60' }} border rounded-2xl flex gap-3 shadow-sm">
            <div class="w-10 h-10 rounded-xl {{ $hasNextSession ? 'bg-blue-600 shadow-blue-200' : 'bg-yellow-600 shadow-yellow-200' }} text-white flex items-center justify-center shrink-0 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    @if($hasNextSession)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    @endif
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    {{ $hasNextSession ? '🎓 Class Promotion Setup Active' : '⚠️ Academic Session Required for Promotion' }}
                </h4>
                <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                    @if($hasNextSession)
                        A <strong>Final-Term</strong> exam has been created for this session. You can now define which target class students in each class will advance to when promoted. Setting these pathways ensures the end-of-session promotion wizard executes correctly.
                    @else
                        A <strong>Final-Term</strong> exam is configured, but <strong>no next academic session is created yet</strong>. Please create a new academic session first in Settings > Academic Sessions before configuring class promotion pathways.
                    @endif
                </p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($classes as $class)
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col gap-3">
                    {{-- Class Name / Rename --}}
                    @if($renamingClassId === $class->id)
                        <div class="flex flex-col gap-2 w-full">
                            <input
                                wire:model="renamingClassName"
                                wire:keydown.enter="saveClassName"
                                wire:keydown.escape="cancelRenameClass"
                                type="text"
                                class="w-full px-3 py-2 border-2 border-blue-400 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-300"
                                autofocus
                            />
                            @error('renamingClassName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <div class="flex gap-2">
                                <button wire:click="saveClassName" class="flex-1 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition-colors">Save</button>
                                <button wire:click="cancelRenameClass" class="flex-1 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200 transition-colors">Cancel</button>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg {{ $class->is_active ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium {{ $class->is_active ? 'text-gray-700' : 'text-gray-400 italic' }} block">{{ $class->name }}</span>
                                        @if(!$class->is_active)
                                            <span class="px-1.5 py-0.2 bg-amber-100 text-amber-700 text-[10px] font-semibold rounded">Archived</span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $class->subjects_count }} Subjects</span>
                                    
                                    <div class="mt-2 flex flex-col gap-0.5">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Promotes To</label>
                                        <select
                                            wire:change="updateNextClass({{ $class->id }}, $event.target.value)"
                                            class="w-full text-xs bg-gray-50 border border-gray-200 rounded-lg py-1 px-1.5 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-semibold text-gray-700"
                                        >
                                            <option value="">Passed Out / Graduated</option>
                                            @foreach($allClasses as $optClass)
                                                @if($optClass->id !== $class->id)
                                                    <option value="{{ $optClass->id }}" @selected($class->next_class_id === $optClass->id)>
                                                        {{ $optClass->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons (always visible) --}}
                            <div class="flex items-center gap-1 shrink-0">
                                @can('classes.manage')
                                {{-- Archive / Reactivate Toggle --}}
                                <button
                                    wire:click="toggleActiveClass({{ $class->id }})"
                                    class="p-1.5 {{ $class->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : 'text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50' }} rounded-lg transition-colors"
                                    title="{{ $class->is_active ? 'Archive Classroom (keep inactive without losing history)' : 'Reactivate Classroom' }}"
                                >
                                    @if($class->is_active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                    @endif
                                </button>
                                {{-- Rename --}}
                                <button
                                    wire:click="startRenameClass({{ $class->id }}, '{{ $class->name }}')"
                                    class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Rename Classroom"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                {{-- Delete --}}
                                <button
                                    wire:click="confirmDelete({{ $class->id }})"
                                    class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Move to Trash"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                                @endcan
                            </div>
                        </div>
                    @endif

                    @can('subjects.manage')
                    <button
                        wire:click="openSubjectModal({{ $class->id }}, '{{ $class->name }}')"
                        class="w-full py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors flex items-center justify-center gap-1.5"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        Curriculum & Subjects
                    </button>
                    @endcan
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/></svg>
                    <p>No classes found. Add your first class above.</p>
                </div>
            @endforelse
        </div>

        {{-- TRASH LIST --}}
        @else
        <div class="space-y-3">
            @forelse($classes as $class)
                <div class="flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-red-100 text-red-500 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/></svg>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">{{ $class->name }}</span>
                            <div class="text-xs text-gray-500">{{ $class->subjects_count }} subjects • Deleted {{ $class->deleted_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="restore({{ $class->id }})"
                            class="px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            Restore
                        </button>
                        <button
                            wire:click="permanentDelete({{ $class->id }})"
                            wire:confirm="This will PERMANENTLY delete '{{ $class->name }}' and all its subjects. This cannot be undone. Are you sure?"
                            class="px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            Delete Forever
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <p>Trash is empty.</p>
                </div>
            @endforelse
        </div>
        @endif
    </div>

    {{-- DELETE WARNING MODAL --}}
    @if($showDeleteWarning)
    @teleport('body')
        <div class="fixed inset-0 z-[999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="cancelDelete"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Move to Trash?</h3>
                            <p class="text-sm text-gray-500">{{ $deletingClassName }}</p>
                        </div>
                    </div>

                    <div class="bg-orange-50 border border-orange-100 rounded-xl p-4 mb-5 space-y-1.5">
                        <p class="text-sm font-semibold text-orange-800 mb-2">This class has linked data:</p>
                        @if($deletingStudentCount > 0)
                            <div class="flex items-center gap-2 text-sm text-orange-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <strong>{{ $deletingStudentCount }}</strong> student(s) enrolled
                            </div>
                        @endif
                        @if($deletingTimetableCount > 0)
                            <div class="flex items-center gap-2 text-sm text-orange-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                <strong>{{ $deletingTimetableCount }}</strong> timetable entry(ies)
                            </div>
                        @endif
                        <p class="text-xs text-orange-600 mt-2 pt-2 border-t border-orange-200">Moving to Trash hides this data. You can restore it anytime from the Trash view.</p>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="cancelDelete" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button wire:click="delete" class="flex-1 px-4 py-2 bg-orange-500 text-white hover:bg-orange-600 rounded-xl text-sm font-medium transition-colors">
                            Move to Trash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endteleport
    @endif

    {{-- SUBJECT MANAGEMENT MODAL --}}
    @if($manageClassId)
    @teleport('body')
        <div class="fixed inset-0 z-[999] flex items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl flex flex-col max-h-[90vh]">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-3 border-b border-gray-100 shrink-0">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Curriculum & Subjects</h3>
                            <p class="text-xs font-semibold text-indigo-600 mt-0.5">Class: {{ $manageClassName }}</p>
                        </div>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-100 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    {{-- Tabs --}}
                    <div class="flex items-center gap-3">
                        <button
                            wire:click="$set('modalActiveTab', 'programs')"
                            class="pb-2 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 {{ $modalActiveTab === 'programs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600' }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Offered Programs ({{ count($classAssignedProgramIds) }})
                        </button>
                        <button
                            wire:click="$set('modalActiveTab', 'subjects')"
                            class="pb-2 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 {{ $modalActiveTab === 'subjects' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600' }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            Resolved Subjects ({{ count($classSubjects) }})
                        </button>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

                    @if($modalActiveTab === 'programs')
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Select academic programs offered to students in <strong>{{ $manageClassName }}</strong>:</span>
                            <a href="{{ route('admin.curriculum', ['tab' => 'programs']) }}" class="text-indigo-600 font-bold hover:underline flex items-center gap-1">
                                Curriculum Hub &rarr;
                            </a>
                        </div>

                        <div>
                            <x-searchable-multiselect
                                wire:model.live="classAssignedProgramIds"
                                :options="$availablePrograms"
                                placeholder="Type to search and tag programs (e.g. Matric, Science)..."
                                nameField="name"
                                badgeField="code"
                                subtextField="level"
                            />
                        </div>

                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar pt-1">
                            @forelse($availablePrograms as $prog)
                                @php $isAssigned = in_array($prog->id, $classAssignedProgramIds); @endphp
                                <div class="p-3 rounded-2xl border transition-all flex items-center justify-between gap-3
                                    {{ $isAssigned ? 'bg-indigo-50/60 border-indigo-200 shadow-xs' : 'bg-gray-50/60 border-gray-200' }}">
                                    <label class="flex items-center gap-3 cursor-pointer flex-1">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleClassTrack({{ $prog->id }})"
                                            @checked($isAssigned)
                                            class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                        />
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-800">{{ $prog->name }}</h4>
                                            <span class="text-xs font-mono text-gray-400">{{ $prog->code }} • {{ $prog->level }} • {{ $prog->subjects->count() }} subjects</span>
                                        </div>
                                    </label>

                                    @if($isAssigned)
                                        <button
                                            type="button"
                                            wire:click="setClassDefaultTrack({{ $prog->id }})"
                                            class="text-xs font-bold px-2 py-1 rounded-lg border transition-colors
                                                {{ $classDefaultProgramId == $prog->id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100' }}"
                                        >
                                            {{ $classDefaultProgramId == $prog->id ? '★ Default Program' : 'Set as Default' }}
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 italic">No programs found.</p>
                            @endforelse
                        </div>
                    </div>
                    @else

                    <div class="space-y-3">
                        {{-- Info Hint Bar --}}
                        <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-2xl flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2 text-indigo-900 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <span>These subjects are automatically inherited from the academic programs assigned in the <strong>Offered Programs</strong> tab.</span>
                            </div>
                            <a href="{{ route('admin.curriculum', ['tab' => 'programs']) }}" class="text-indigo-600 font-bold hover:underline shrink-0 flex items-center gap-0.5">
                                Curriculum Hub &rarr;
                            </a>
                        </div>

                        {{-- Read-Only Subject List --}}
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            @forelse($classSubjects as $subject)
                                <div class="flex items-center justify-between p-3 rounded-2xl border border-gray-100 bg-gray-50/70 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-indigo-600 font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                            {{ $subject->abbreviation }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-800 truncate">{{ $subject->name }}</p>
                                            <p class="text-xs font-mono text-gray-400">{{ $subject->code ?? $subject->abbreviation }} • {{ $subject->category ?? 'General' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white text-gray-600 border border-gray-200">
                                            Canonical
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                    <p class="text-sm font-medium">No subjects currently assigned to this class.</p>
                                    <p class="text-xs text-gray-400 mt-1">Switch to the <strong>Offered Programs</strong> tab to assign an academic program.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @endif

                </div>{{-- end scrollable body --}}

                {{-- Modal Footer --}}
                <div class="px-6 py-3.5 bg-gray-50/80 border-t border-gray-100 rounded-b-3xl flex items-center justify-between">
                    <a
                        href="{{ route('admin.curriculum') }}"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Open Full Curriculum Hub
                    </a>

                    <button
                        wire:click="closeModal"
                        class="px-5 py-2 bg-gray-800 text-white rounded-xl text-xs font-bold hover:bg-gray-700 transition-colors"
                    >
                        Done
                    </button>
                </div>
            </div>{{-- end modal panel --}}
        </div>
    @endteleport
    @endif

</div>
