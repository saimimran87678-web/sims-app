<div class="space-y-6">
    <x-slot name="header">Class Management</x-slot>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="glass-card p-6 rounded-2xl">
        {{-- Header Row --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-800">
                    {{ $showTrash ? '🗑️ Trash' : 'Manage Classes' }}
                </h3>
                @if($showTrash)
                    <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded-full">Deleted Classes</span>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 items-center">
                {{-- Session Selector --}}
                @if(count($academicSessions) > 1)
                    <select wire:model.live="selectedSessionId" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
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
                                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 block">{{ $class->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $class->subjects_count }} Subjects</span>
                                </div>
                            </div>

                            {{-- Action Buttons (always visible) --}}
                            <div class="flex items-center gap-1 shrink-0">
                                @can('class.delete')
                                {{-- Rename --}}
                                <button
                                    wire:click="startRenameClass({{ $class->id }}, '{{ $class->name }}')"
                                    class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Rename Class"
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
                        class="w-full py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                    >
                        Manage Subjects
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
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh]">

                {{-- Header --}}
                <div class="flex justify-between items-center px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Manage Subjects</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $manageClassName }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-100 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

                    {{-- Add Subject --}}
                    @can('subject.create')
                    <div>
                        <div class="flex gap-2">
                            <input
                                wire:model="newSubjectName"
                                wire:keydown.enter="addSubject"
                                type="text"
                                placeholder="Enter subject name..."
                                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                            />
                            <button wire:click="addSubject" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold shadow-sm shadow-blue-200 animate-btn">
                                Add
                            </button>
                        </div>
                        @error('newSubjectName') <p class="text-red-500 text-xs mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                    @endcan

                    {{-- Selection Hint Bar --}}
                    @if(count($classSubjects) > 0)
                    <div class="flex items-center justify-between py-1">
                        <p class="text-xs text-gray-400 flex items-center gap-1.5">
                            @if(count($selectedSubjectIds) > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-semibold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ count($selectedSubjectIds) }} selected
                                </span>
                                <span class="text-gray-400">— pick classes below to copy</span>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                                Tick subjects to copy them to other classes
                            @endif
                        </p>
                        @if(count($selectedSubjectIds) > 0)
                        <button wire:click="$set('selectedSubjectIds', [])" class="text-xs text-gray-400 hover:text-gray-600 underline underline-offset-2 transition-colors">Clear all</button>
                        @endif
                    </div>
                    @endif

                    {{-- Subject List --}}
                    <div class="space-y-2">
                        @forelse($classSubjects as $subject)
                            <div class="flex items-center gap-3 p-3 rounded-xl border transition-all
                                {{ in_array($subject->id, $selectedSubjectIds) ? 'bg-blue-50 border-blue-200 shadow-sm' : 'bg-gray-50 border-gray-100 hover:border-gray-200' }}">

                                @if($renamingSubjectId === $subject->id)
                                    <div class="flex-1 flex flex-col gap-2">
                                        <input
                                            wire:model="renamingSubjectName"
                                            wire:keydown.enter="saveSubjectName"
                                            wire:keydown.escape="cancelRenameSubject"
                                            type="text"
                                            class="w-full px-3 py-2 border-2 border-blue-400 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                                            autofocus
                                        />
                                        @error('renamingSubjectName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        <div class="flex gap-2">
                                            <button wire:click="saveSubjectName" class="flex-1 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition-colors">Save</button>
                                            <button wire:click="cancelRenameSubject" class="flex-1 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs hover:bg-gray-200 transition-colors">Cancel</button>
                                        </div>
                                    </div>
                                @else
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedSubjectIds"
                                        value="{{ $subject->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 shrink-0 cursor-pointer"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $subject->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $subject->code }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button
                                            wire:click="startRenameSubject({{ $subject->id }}, '{{ $subject->name }}')"
                                            class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                            title="Rename"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        @can('subject.delete')
                                        <button
                                            wire:click="deleteSubject({{ $subject->id }})"
                                            wire:confirm="Delete '{{ $subject->name }}'? This cannot be undone."
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                        @endcan
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                <p class="text-sm">No subjects yet. Add one above.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- COPY TO CLASSES PANEL --}}
                    @if($showCopyPanel)
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <p class="text-sm font-bold text-blue-700">Copy {{ count($selectedSubjectIds) }} subject(s) to which classes?</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-1">
                            @foreach($classes as $targetClass)
                                @if($targetClass->id !== $manageClassId)
                                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all select-none
                                    {{ in_array($targetClass->id, $copyTargetClassIds) ? 'bg-white border-blue-400 shadow-sm text-blue-700 font-semibold' : 'bg-white border-gray-200 text-gray-600 hover:border-blue-200' }}">
                                    <input
                                        type="checkbox"
                                        wire:model.live="copyTargetClassIds"
                                        value="{{ $targetClass->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 shrink-0"
                                    />
                                    <span class="text-xs font-semibold truncate">{{ $targetClass->name }}</span>
                                </label>
                                @endif
                            @endforeach
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button
                                wire:click="copySubjectsToClasses"
                                @if(empty($copyTargetClassIds)) disabled @endif
                                class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm shadow-blue-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                Copy Now
                            </button>
                            <button wire:click="$set('showCopyPanel', false)" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                    @endif

                </div>{{-- end scrollable body --}}
            </div>{{-- end modal panel --}}
        </div>
    @endteleport
    @endif

</div>
