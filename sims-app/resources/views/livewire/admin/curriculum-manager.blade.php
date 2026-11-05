<div class="space-y-6">
    <x-slot name="header">Curriculum & Academic Programs</x-slot>

    {{-- Flash Notifications --}}
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="text-sm font-semibold">{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Top Action Banner & Tabs --}}
    <div class="glass-card p-6 rounded-3xl border border-gray-100 shadow-sm space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </span>
                    Curriculum & Programs Hub
                </h1>
                <p class="text-xs text-gray-500 mt-1 font-medium">
                    Manage the institute-wide Master Subject Catalogue, design academic programs (Matric/FSc/Arts), and assign allowed programs to classes.
                </p>
            </div>

            {{-- Quick Testing Tools Dropdown / Buttons --}}
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    wire:click="clearAllClassOfferings"
                    wire:confirm="Are you sure you want to remove ALL academic program assignments from all classes? This will reset your classes to 0 subjects for clean testing."
                    class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-xl border border-red-200 transition-colors flex items-center gap-1.5"
                    title="Remove all program assignments from classes"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Clear All Class Programs (Clean Slate)
                </button>

                <button
                    wire:click="restoreDefaultOfferings"
                    class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-xl border border-blue-200 transition-colors flex items-center gap-1.5"
                    title="Restore standard program allocations"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                    Restore Standard Presets
                </button>
            </div>
        </div>

        {{-- Tab Navigation Bar --}}
        <div class="flex items-center border-b border-gray-200 gap-2 sm:gap-6 overflow-x-auto">
            <button
                wire:click="setTab('catalogue')"
                class="pb-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all shrink-0 {{ $activeTab === 'catalogue' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Master Subject Catalogue
                <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'catalogue' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ count($subjects) }}</span>
            </button>

            <button
                wire:click="setTab('programs')"
                class="pb-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all shrink-0 {{ $activeTab === 'programs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Academic Programs
                <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'programs' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ count($programs) }}</span>
            </button>

            <button
                wire:click="setTab('offerings')"
                class="pb-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all shrink-0 {{ $activeTab === 'offerings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                Class Offerings
                <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'offerings' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ count($classes) }}</span>
            </button>
        </div>
    </div>

    {{-- TAB 1: MASTER SUBJECT CATALOGUE --}}
    @if($activeTab === 'catalogue')
    <div class="space-y-4">
        {{-- Filters & Add Button --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 max-w-md">
                <div class="relative flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search subjects by name or code..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm"
                    />
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>

                <select
                    wire:model.live="categoryFilter"
                    class="py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <button
                wire:click="openCreateSubjectModal"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-200 transition-colors flex items-center justify-center gap-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Canonical Subject
            </button>
        </div>

        {{-- Subjects Grid / Table --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Subject Name</th>
                        <th class="py-3.5 px-4">Code</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Programs</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($subjects as $sub)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-gray-800 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $sub->abbreviation }}
                                </div>
                                <span>{{ $sub->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-600 text-xs">
                                <span class="px-2 py-0.5 bg-gray-100 rounded-md border border-gray-200">{{ $sub->code ?? 'N/A' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $sub->category ?? 'General' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if(($sub->programs_count ?? 0) > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        {{ $sub->programs_count }} {{ Str::plural('Program', $sub->programs_count) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Unassigned (0)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <button
                                    wire:click="toggleSubjectActive({{ $sub->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold cursor-pointer transition-colors {{ $sub->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sub->is_active ? 'bg-emerald-600' : 'bg-gray-400' }}"></span>
                                    {{ $sub->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        wire:click="editSubject({{ $sub->id }})"
                                        class="px-2.5 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        wire:click="deleteSubject({{ $sub->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $sub->name }}'? If this subject has no linked records anywhere, it will be permanently removed."
                                        class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors"
                                        title="Delete subject if unused"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                No canonical subjects found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- TAB 2: ACADEMIC PROGRAMS (BUNDLER) --}}
    @if($activeTab === 'programs')
    <div class="space-y-4">
        {{-- Filters & Add Button --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 max-w-md">
                <div class="relative flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search programs by name or code..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm"
                    />
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>

                <select
                    wire:model.live="levelFilter"
                    class="py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm"
                >
                    <option value="">All Levels</option>
                    @foreach($levels as $lvl)
                        <option value="{{ $lvl }}">{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>

            <button
                wire:click="openCreateProgramModal"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-200 transition-colors flex items-center justify-center gap-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create Academic Program
            </button>
        </div>

        {{-- Programs Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($programs as $prog)
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between gap-4">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $prog->level ?? 'Standard' }}
                                </span>
                                <h3 class="text-base font-bold text-gray-900 mt-1.5">{{ $prog->name }}</h3>
                                <p class="text-xs font-mono font-bold text-gray-500">{{ $prog->code }}</p>
                            </div>
                            <button
                                wire:click="toggleProgramActive({{ $prog->id }})"
                                class="px-2 py-0.5 rounded-full text-xs font-bold {{ $prog->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}"
                            >
                                {{ $prog->is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </div>

                        @if($prog->description)
                            <p class="text-xs text-gray-500 leading-relaxed line-clamp-2">{{ $prog->description }}</p>
                        @endif

                        {{-- Subjects Preview --}}
                        <div class="pt-2 border-t border-gray-100 space-y-2">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="font-bold">Bundled Subjects ({{ $prog->subjects->count() }})</span>
                                <span class="text-[11px] text-gray-400">
                                    {{ $prog->subjects->where('pivot.subject_type', 'compulsory')->count() }} Compulsory,
                                    {{ $prog->subjects->where('pivot.subject_type', 'elective')->count() }} Elective
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                                @forelse($prog->subjects as $psub)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium border
                                        {{ $psub->pivot->subject_type === 'compulsory' ? 'bg-gray-50 border-gray-200 text-gray-700' : 'bg-purple-50 border-purple-200 text-purple-700' }}">
                                        {{ $psub->name }}
                                        @if($psub->pivot->subject_type === 'elective')
                                            <span class="text-[9px] font-black uppercase bg-purple-200 text-purple-800 px-1 rounded">Elec</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 italic">No subjects bundled yet.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer Actions --}}
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        <button
                            wire:click="editProgram({{ $prog->id }})"
                            class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Edit Program Info
                        </button>
                        <button
                            wire:click="openProgramSubjectsModal({{ $prog->id }})"
                            class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 transition-colors flex items-center gap-1.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Manage Bundle ({{ $prog->subjects->count() }})
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-200">
                    No programs found. Click "Create Academic Program" to create one.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- TAB 3: CLASS OFFERINGS (ASSIGN PROGRAMS TO CLASSES) --}}
    @if($activeTab === 'offerings')
    <div class="space-y-4">
        {{-- Session Switcher Bar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Academic Session:</span>
                <select
                    wire:model.live="selectedSessionId"
                    class="py-1.5 pl-3 pr-8 bg-gray-50 border border-gray-300 rounded-xl text-sm font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500 outline-none"
                >
                    @foreach($academicSessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-xs text-gray-400 font-medium">
                Each class can offer one or multiple academic programs. The default program is automatically selected for new student admissions.
            </p>
        </div>

        {{-- Class Offerings Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($classes as $c)
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col justify-between gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                    {{ $c->numeric_value ?? substr($c->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">{{ $c->name }}</h4>
                                    <span class="text-[11px] text-gray-400">{{ $c->shift_type }} Shift</span>
                                </div>
                            </div>

                            @if($c->programs->count() > 0)
                                 <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                     {{ $c->programs->count() }} {{ \Illuminate\Support\Str::plural('Program', $c->programs->count()) }}
                                 </span>
                             @else
                                 <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                     0 Programs (Unassigned)
                                 </span>
                             @endif
                         </div>

                         {{-- Assigned Programs Badges --}}
                         <div class="space-y-2">
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Offered Programs:</div>
                            <div class="flex flex-wrap gap-1.5 min-h-12 items-center">
                                @forelse($c->programs as $cprog)
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold border
                                        {{ $cprog->pivot->is_default ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-gray-50 border-gray-200 text-gray-700' }}">
                                        <span>{{ $cprog->name }}</span>
                                        @if($cprog->pivot->is_default)
                                            <span class="text-[9px] bg-indigo-600 text-white font-black px-1.5 py-0.5 rounded-md uppercase">Default</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-amber-600 italic">No programs offered to this class yet.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Total Subjects Count Preview --}}
                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                            <span>Total Combined Subjects:</span>
                            <span class="font-bold text-gray-800">{{ $c->offered_subjects->count() }} subjects</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        @if($c->programs->count() > 0)
                            <button
                                wire:click="clearOfferingsForClass({{ $c->id }})"
                                wire:confirm="Remove all program offerings from {{ $c->name }}?"
                                class="text-xs text-red-500 hover:text-red-700 font-semibold"
                            >
                                Clear Programs
                            </button>
                        @else
                            <span></span>
                        @endif

                        <button
                            wire:click="openClassOfferingsModal({{ $c->id }})"
                            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors flex items-center gap-1.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            Configure Programs
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-200">
                    No classes found in the selected academic session.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL 1: ADD / EDIT SUBJECT                                               --}}
    {{-- ========================================================================= --}}
    @if($showSubjectModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-lg font-bold text-gray-900">
                    {{ $editingSubjectId ? 'Edit Canonical Subject' : 'Add Canonical Subject to Catalogue' }}
                </h3>
                <button wire:click="$set('showSubjectModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Subject Name *</label>
                    <input
                        wire:model="subjectName"
                        type="text"
                        placeholder="e.g. Robotics & AI, Persian, Economics"
                        class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    />
                    @error('subjectName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Abbreviation / Code</label>
                        <input
                            wire:model="subjectCode"
                            type="text"
                            placeholder="e.g. RAI, PERS, ECON"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none uppercase"
                        />
                        @error('subjectCode') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Category *</label>
                        <select
                            wire:model="subjectCategory"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white"
                        >
                            <option value="General">General</option>
                            <option value="Language">Language</option>
                            <option value="Science">Science</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Social Studies">Social Studies</option>
                            <option value="Commerce">Commerce</option>
                            <option value="Religion/Ethics">Religion/Ethics</option>
                            <option value="Arts">Arts</option>
                            <option value="Computer">Computer</option>
                        </select>
                        @error('subjectCategory') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer pt-2">
                    <input type="checkbox" wire:model="subjectIsActive" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"/>
                    <span class="text-sm font-medium text-gray-700">Active (Visible across all modules)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button
                    wire:click="$set('showSubjectModal', false)"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="saveSubject"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-md shadow-indigo-200 transition-colors"
                >
                    Save Subject
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL 2: ADD / EDIT PROGRAM                                               --}}
    {{-- ========================================================================= --}}
    @if($showProgramModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-lg font-bold text-gray-900">
                    {{ $editingProgramId ? 'Edit Academic Program' : 'Create Academic Program' }}
                </h3>
                <button wire:click="$set('showProgramModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Program Name *</label>
                    <input
                        wire:model="programName"
                        type="text"
                        placeholder="e.g. SSC - Science (Computer Science Group)"
                        class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    />
                    @error('programName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Program Code *</label>
                        <input
                            wire:model="programCode"
                            type="text"
                            placeholder="e.g. SSC-CS, HSSC-MED"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none uppercase"
                        />
                        @error('programCode') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Education Level *</label>
                        <select
                            wire:model="programLevel"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white"
                        >
                            <option value="Primary">Primary (1–5)</option>
                            <option value="Middle">Middle (6–8)</option>
                            <option value="Matric">Matric (9–10)</option>
                            <option value="Intermediate">Intermediate (11–12)</option>
                            <option value="Cambridge">Cambridge / O-A Levels</option>
                            <option value="General">General / Institute-wide</option>
                        </select>
                        @error('programLevel') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Description</label>
                    <textarea
                        wire:model="programDescription"
                        rows="2"
                        placeholder="Brief summary of program requirements or target curriculum..."
                        class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    ></textarea>
                </div>

                @if(!$editingProgramId)
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pre-bundle Initial Subjects (Optional)</label>
                    <x-searchable-multiselect
                        wire:model="programPreselectedSubjectIds"
                        :options="$allActiveSubjects"
                        placeholder="Search and tag initial subjects (e.g. English, Math)..."
                        nameField="name"
                        badgeField="code"
                        subtextField="category"
                    />
                    <p class="text-[11px] text-gray-400 mt-1">Tagged subjects will automatically be bundled into this program upon creation.</p>
                </div>
                @endif

                <label class="flex items-center gap-2 cursor-pointer pt-2">
                    <input type="checkbox" wire:model="programIsActive" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"/>
                    <span class="text-sm font-medium text-gray-700">Active (Available for Class offerings)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button
                    wire:click="$set('showProgramModal', false)"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="saveProgram"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-md shadow-indigo-200 transition-colors"
                >
                    Save Academic Program
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL 3: PROGRAM SUBJECT BUNDLER DRAWER                                   --}}
    {{-- ========================================================================= --}}
    @if($showProgramSubjectsModal && $activeProgram)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-5 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ $activeProgram->level }} Program
                    </span>
                    <h3 class="text-lg font-bold text-gray-900 mt-1">
                        Manage Bundle: {{ $activeProgram->name }} ({{ $activeProgram->code }})
                    </h3>
                </div>
                <button wire:click="closeProgramSubjectsModal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if (session()->has('bundle_message'))
                <div class="px-3 py-2 bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-semibold rounded-xl">
                    {{ session('bundle_message') }}
                </div>
            @endif

            {{-- Add Subjects to Bundle Row with Searchable Multi-Select Chips --}}
            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-200 space-y-3">
                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                    <span>Search & Select Subjects to Bundle:</span>
                    <span class="text-[11px] text-gray-400 font-medium hidden sm:inline">Add multiple subjects at once</span>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="flex-1">
                        <x-searchable-multiselect
                            wire:model="newSubjectIdsToAdd"
                            :options="$unassignedCatalogueSubjects"
                            placeholder="Type to search subjects (e.g. English, Math, Science)..."
                            nameField="name"
                            badgeField="code"
                            subtextField="category"
                            emptyMessage="All catalogue subjects are already bundled in this program."
                        />
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <select
                            wire:model="newSubjectType"
                            class="py-2 px-3 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none h-[42px]"
                            title="Assign role to selected subjects"
                        >
                            <option value="compulsory">Compulsory</option>
                            <option value="elective">Elective</option>
                        </select>

                        <button
                            wire:click="addSubjectsToProgram"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors shrink-0 h-[42px] flex items-center gap-1.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Add to Bundle</span>
                            @if(count($newSubjectIdsToAdd) > 0)
                                <span class="ml-1 px-1.5 py-0.2 bg-white/20 rounded-full text-[10px] font-black">({{ count($newSubjectIdsToAdd) }})</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            {{-- Bundled Subjects List --}}
            <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                @forelse($activeProgram->subjects as $psub)
                    <div class="p-3 bg-white border border-gray-200 rounded-2xl flex items-center justify-between gap-3 shadow-xs hover:border-indigo-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ $psub->abbreviation }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">{{ $psub->name }}</h4>
                                <span class="text-xs font-mono text-gray-400">{{ $psub->code }} • {{ $psub->category }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                wire:click="toggleSubjectTypeInProgram({{ $psub->id }})"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition-colors cursor-pointer
                                    {{ $psub->pivot->subject_type === 'compulsory' ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100' : 'bg-purple-50 border-purple-200 text-purple-700 hover:bg-purple-100' }}"
                                title="Click to toggle Compulsory/Elective"
                            >
                                {{ ucfirst($psub->pivot->subject_type) }}
                            </button>

                            <button
                                wire:click="removeSubjectFromProgram({{ $psub->id }})"
                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                title="Remove from bundle"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400">
                        No subjects bundled in this program yet. Choose subjects from the Master Catalogue above.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end pt-3 border-t border-gray-100">
                <button
                    wire:click="closeProgramSubjectsModal"
                    class="px-5 py-2 bg-gray-800 text-white text-sm font-bold rounded-xl hover:bg-gray-700 transition-colors"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL 4: CONFIGURE CLASS OFFERINGS                                        --}}
    {{-- ========================================================================= --}}
    @if($showClassOfferingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Configure Class Offerings</h3>
                    <p class="text-xs text-gray-500 font-medium">Select which academic programs students in <strong>{{ $offeringClassName }}</strong> can enroll in.</p>
                </div>
                <button wire:click="$set('showClassOfferingsModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Quick Search & Tag Programs:</label>
                    <x-searchable-multiselect
                        wire:model.live="offeringProgramIds"
                        :options="$allActivePrograms"
                        placeholder="Type to search and tag programs (e.g. Matric, FSc, Science)..."
                        nameField="name"
                        badgeField="code"
                        subtextField="level"
                    />
                </div>

                <div class="pt-2 border-t border-gray-100 space-y-2">
                    <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
                        <span>Offered Programs & Default Selector:</span>
                        <span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                            {{ count($offeringProgramIds) }} Offered
                        </span>
                    </div>

                    <div class="space-y-2 max-h-52 overflow-y-auto pr-1 custom-scrollbar">
                    @forelse($allActivePrograms as $prog)
                        @php $isSelected = in_array($prog->id, $offeringProgramIds); @endphp
                        <div class="p-3 rounded-2xl border transition-all flex items-center justify-between gap-3
                            {{ $isSelected ? 'bg-indigo-50/60 border-indigo-200' : 'bg-gray-50/60 border-gray-200' }}">
                            
                            <label class="flex items-center gap-3 cursor-pointer flex-1">
                                <input
                                    type="checkbox"
                                    wire:model.live="offeringProgramIds"
                                    value="{{ $prog->id }}"
                                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                />
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800">{{ $prog->name }}</h4>
                                    <span class="text-xs font-mono text-gray-400">{{ $prog->code }} • {{ $prog->level }} • {{ $prog->subjects->count() }} subjects</span>
                                </div>
                            </label>

                            @if($isSelected)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-bold px-2 py-1 rounded-lg border
                                    {{ $offeringDefaultProgramId == $prog->id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                    <input
                                        type="radio"
                                        wire:model.live="offeringDefaultProgramId"
                                        value="{{ $prog->id }}"
                                        class="hidden"
                                    />
                                    <span>{{ $offeringDefaultProgramId == $prog->id ? '★ Default Program' : 'Set as Default' }}</span>
                                </label>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">No active programs available. Create one in the Programs tab first.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button
                    wire:click="$set('showClassOfferingsModal', false)"
                    class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="saveClassOfferings"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-md shadow-indigo-200 transition-colors"
                >
                    Save Offerings
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
