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

    {{-- Historical Session Read-Only Banner --}}
    @if($this->isHistoricalSession)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-2xl flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-900">Historical Academic Session (Sealed Archive)</h4>
                    <p class="text-xs text-amber-700 mt-0.5">Classrooms, additions, renames, and archival states are sealed for this historical period to ensure strict institutional record immutability.</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-amber-200 text-amber-900 text-xs font-bold rounded-xl shrink-0">Read-Only</span>
        </div>
    @endif

    <div class="glass-card p-6 rounded-2xl">
        {{-- Header Row --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    @if($showArchived)
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                        Archived Classrooms
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/></svg>
                        Active Classrooms
                    @endif
                </h3>
                @if($showArchived)
                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Inactive Rooms</span>
                @else
                    <span class="px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">{{ count($classes) }} Rooms</span>
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

                {{-- Archived Rooms Toggle Button --}}
                <button
                    wire:click="toggleArchived"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center gap-2
                        {{ $showArchived ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                    @if($showArchived)
                        Back to Active
                    @else
                        Archived Classrooms
                        @if($this->archived_count > 0)
                            <span class="px-1.5 py-0.2 bg-amber-200 text-amber-800 text-[10px] font-bold rounded-full">{{ $this->archived_count }}</span>
                        @endif
                    @endif
                </button>

                {{-- Add Class --}}
                @if(!$showArchived && !$this->isHistoricalSession)
                    @can('class.create')
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center">
                            {{-- Optional Stage Selector --}}
                            <select wire:model="stage" class="px-2 py-2 bg-gray-100 text-gray-700 rounded-l-xl border border-r-0 border-gray-200 text-xs font-semibold outline-none hover:bg-gray-200 transition-colors">
                                <option value="auto">⚡ Auto Stage</option>
                                <optgroup label="Early Years / Montessori">
                                    <option value="-3">Playgroup</option>
                                    <option value="-2">Nursery</option>
                                    <option value="-1">KG-1 (Junior KG)</option>
                                    <option value="0">KG-2 / Prep</option>
                                </optgroup>
                                <optgroup label="Standard Grades">
                                    @for($i=1; $i<=12; $i++)
                                        <option value="{{ $i }}">Class {{ $i }}</option>
                                    @endfor
                                </optgroup>
                            </select>
                            <input
                                id="class-name-input"
                                wire:model="name"
                                wire:keydown.enter="save"
                                type="text"
                                placeholder="e.g. Class 7 - Iqbal, Nursery - Rose, 10A"
                                class="px-3 py-2 border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm w-44 sm:w-60"
                            />
                            <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded-r-xl hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-1.5 shadow-md shadow-blue-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Add Room
                            </button>
                        </div>
                        @error('name') <span class="text-red-500 text-xs px-1">{{ $message }}</span> @enderror
                    </div>
                    @endcan
                @elseif($this->isHistoricalSession)
                    <div class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-xl text-xs font-semibold flex items-center gap-1.5 border border-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Sealed Session</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ACTIVE CLASSROOMS GRID --}}
        @if(!$showArchived)
        
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
                        A <strong>Final-Term</strong> exam is configured for this session. The "Promotes To" progression pathway is now visible below to configure which class passing students advance to in the new session.
                    @else
                        A <strong>Final-Term</strong> exam is configured, but <strong>no next academic session is created yet</strong>. Please create a new academic session first in Settings > Academic Sessions before configuring class promotion pathways.
                    @endif
                </p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($classes as $class)
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between gap-3">
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
                        <div>
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold text-gray-800 text-sm">{{ $class->grade_name }}</span>
                                            @if($class->section_name)
                                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-md border border-blue-100">{{ $class->section_name }}</span>
                                            @endif
                                            @if($class->is_early_education)
                                                <span class="px-1.5 py-0.2 bg-purple-50 text-purple-700 text-[10px] font-semibold rounded">ECE</span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500 block mt-0.5">{{ $class->subjects_count }} Subjects</span>
                                    </div>
                                </div>

                                {{-- Action Buttons (Archive, Rename, Delete) --}}
                                <div class="flex items-center gap-1 shrink-0">
                                    @if(!$this->isHistoricalSession)
                                        @can('classes.manage')
                                        {{-- Archive Button --}}
                                        <button
                                            wire:click="archiveClass({{ $class->id }})"
                                            class="p-1.5 text-amber-500 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Archive Classroom (safe deactivation, preserving all student and financial history)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                                        </button>
                                        {{-- Rename --}}
                                        <button
                                            wire:click="startRenameClass({{ $class->id }}, '{{ $class->name }}')"
                                            class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Rename Classroom"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        {{-- Delete (Direct permanent delete if 0 records) --}}
                                        <button
                                            wire:click="deleteClassDirectly({{ $class->id }})"
                                            wire:confirm="Permanently delete '{{ $class->name }}'? Note: This is only permitted for mistaken classrooms with 0 student, exam, or fee records."
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete Classroom (only allowed if 0 historical records exist)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                        @endcan
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-400 text-[10px] font-semibold rounded-md flex items-center gap-1" title="Historical Session Sealed">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Locked
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- "Promotes To" Progression Ladder: SHOWN ONLY WHEN FINAL EXAM IS CONFIGURED --}}
                            @if($hasFinalExam)
                            <div class="mt-3 p-2 bg-blue-50/60 border border-blue-100 rounded-lg flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-blue-700 uppercase tracking-wider flex items-center gap-1">
                                    <span>Promotes To</span>
                                    <span class="text-[9px] font-normal text-blue-500">(Final Exam Active)</span>
                                </label>
                                <select
                                    wire:change="updateNextClass({{ $class->id }}, $event.target.value)"
                                    @if($this->isHistoricalSession) disabled @endif
                                    class="w-full text-xs bg-white border border-blue-200 rounded-md py-1 px-1.5 focus:ring-1 focus:ring-blue-500 outline-none font-semibold text-gray-700 {{ $this->isHistoricalSession ? 'bg-gray-50 text-gray-400 cursor-not-allowed' : '' }}"
                                >
                                    <option value="">Passed Out / Graduated</option>
                                    @foreach($allClasses as $optClass)
                                        @if($optClass->id !== $class->id)
                                            <option value="{{ $optClass->id }}" @selected($class->next_class_id === $optClass->id)>
                                                {{ $optClass->display_name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            @endif
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
                    <p>No active classrooms found. Add your first room above.</p>
                </div>
            @endforelse
        </div>

        {{-- ARCHIVED CLASSROOMS LIST --}}
        @else
        <div class="space-y-3">
            @forelse($classes as $class)
                <div class="flex items-center justify-between p-4 bg-amber-50/60 border border-amber-200/70 rounded-xl hover:bg-amber-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-800">{{ $class->grade_name }}</span>
                                @if($class->section_name)
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-xs font-semibold rounded-md">{{ $class->section_name }}</span>
                                @endif
                                <span class="text-xs text-amber-700 italic">Archived (Inactive)</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $class->subjects_count }} subjects • Institutional asset preserved</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!$this->isHistoricalSession)
                            {{-- Activate / Restore Room Button --}}
                            <button
                                wire:click="restoreClass({{ $class->id }})"
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm shadow-emerald-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Activate Room
                            </button>
                            {{-- Permanent Delete (Direct permanent delete if 0 records) --}}
                            <button
                                wire:click="deleteClassDirectly({{ $class->id }})"
                                wire:confirm="Permanently delete '{{ $class->name }}'? Note: This is only permitted for mistaken classrooms with 0 student, exam, or fee records."
                                class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                Delete Forever
                            </button>
                        @else
                            <span class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-xl text-xs font-semibold flex items-center gap-1.5 border border-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Sealed Archive
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-amber-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="4" rx="1"/><path d="M5 7v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7"/><path d="M10 12h4"/></svg>
                    <p class="font-medium text-gray-600">No archived classrooms.</p>
                    <p class="text-xs text-gray-400 mt-1">All institutional classrooms are currently active.</p>
                </div>
            @endforelse
        </div>
        @endif
    </div>

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
                        @if($this->isHistoricalSession)
                        <div class="p-2.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Curriculum tracks are read-only for concluded academic sessions.</span>
                        </div>
                        @endif

                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Select academic programs offered to students in <strong>{{ $manageClassName }}</strong>:</span>
                            <a href="{{ route('admin.curriculum', ['tab' => 'programs']) }}" class="text-indigo-600 font-bold hover:underline flex items-center gap-1">
                                Curriculum Hub &rarr;
                            </a>
                        </div>

                        @if(!$this->isHistoricalSession)
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
                        @endif

                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar pt-1">
                            @forelse($availablePrograms as $prog)
                                @php $isAssigned = in_array($prog->id, $classAssignedProgramIds); @endphp
                                <div class="p-3 rounded-2xl border transition-all flex items-center justify-between gap-3
                                    {{ $isAssigned ? 'bg-indigo-50/60 border-indigo-200 shadow-xs' : 'bg-gray-50/60 border-gray-200' }}">
                                    <label class="flex items-center gap-3 {{ $this->isHistoricalSession ? 'cursor-default' : 'cursor-pointer' }} flex-1">
                                        <input
                                            type="checkbox"
                                            @if(!$this->isHistoricalSession) wire:click="toggleClassTrack({{ $prog->id }})" @else disabled @endif
                                            @checked($isAssigned)
                                            class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 {{ $this->isHistoricalSession ? 'opacity-60 cursor-not-allowed' : '' }}"
                                        />
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-800">{{ $prog->name }}</h4>
                                            <span class="text-xs font-mono text-gray-400">{{ $prog->code }} • {{ $prog->level }} • {{ $prog->subjects->count() }} subjects</span>
                                        </div>
                                    </label>

                                    @if($isAssigned)
                                        <button
                                            type="button"
                                            @if(!$this->isHistoricalSession) wire:click="setClassDefaultTrack({{ $prog->id }})" @endif
                                            class="text-xs font-bold px-2 py-1 rounded-lg border transition-colors
                                                {{ $classDefaultProgramId == $prog->id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100' }} {{ $this->isHistoricalSession ? 'cursor-default' : '' }}"
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
