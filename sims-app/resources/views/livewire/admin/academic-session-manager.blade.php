<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                @if($isTeacherContext)
                    <a href="{{ route('teacher.dashboard') }}" class="p-2.5 -ml-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all" title="Back to Dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                @else
                    <a href="{{ route('admin.settings') }}" class="p-2.5 -ml-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all" title="Back to Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                @endif
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Academic Sessions</h1>
            </div>
            <p class="text-sm text-gray-500 ml-10 font-medium">Manage school academic years and which classes are offered each year</p>
        </div>
        <div class="flex flex-wrap gap-3 ml-10 md:ml-0">
            <button wire:click="runAutoUpdate" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-xl hover:bg-purple-100 font-semibold text-sm transition-all shadow-sm active:scale-95 duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Auto-Generate Current Year</span>
            </button>
            <button wire:click="create" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm transition-all shadow-md shadow-blue-200 hover:shadow-lg active:scale-95 duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <span>New Session</span>
            </button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Sessions</p>
                <h3 class="text-xl font-bold text-gray-800">{{ $sessions->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Session</p>
                <h3 class="text-xl font-bold text-gray-800">{{ $sessions->firstWhere('is_active', true)?->name ?? 'None' }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Classes (Active Session)</p>
                <h3 class="text-xl font-bold text-gray-800">{{ $activeClassesCount }}</h3>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl flex items-center gap-3 text-sm font-semibold shadow-sm" role="alert">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl flex items-center gap-3 text-sm font-semibold shadow-sm" role="alert">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Sessions Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/70">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Session Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">End Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($sessions as $session)
                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm font-bold text-gray-800">{{ $session->name }}</span>
                                @if($session->is_active)
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ $session->start_date }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ $session->end_date }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($session->is_active)
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-50 text-green-700 border border-green-200">Active</span>
                            @elseif(\Carbon\Carbon::parse($session->start_date)->isFuture())
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">Upcoming</span>
                            @elseif(\Carbon\Carbon::parse($session->end_date)->isPast())
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-100 text-gray-600 border border-gray-200">Completed</span>
                            @else
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $session->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    Edit
                                </button>

                                @if(!$isTeacherContext)
                                <button
                                    wire:click="delete({{ $session->id }})"
                                    wire:confirm="Are you sure you want to delete '{{ $session->name }}'? This cannot be undone."
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-red-50 text-red-700 hover:bg-red-100 rounded-lg transition-colors border border-red-200 shadow-sm"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm font-medium">
                            No sessions created yet. Click "New Session" to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-600 bg-opacity-40 backdrop-blur-sm" aria-hidden="true" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full text-left align-bottom transition-all transform bg-white shadow-2xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-lg overflow-hidden border border-gray-100">
                <div class="px-6 py-6 bg-white sm:p-8">
                    <h3 class="text-xl font-bold leading-6 text-gray-900 mb-5" id="modal-title">
                        {{ $sessionId ? 'Edit Session' : 'Create New Session' }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Session Name</label>
                            <input type="text" wire:model="name" placeholder="e.g. 2026-2027"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all placeholder-gray-400 py-2.5 px-3.5 text-sm">
                            @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Start Date</label>
                                <input type="date" wire:model="start_date"
                                    class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3.5 text-sm">
                                @error('start_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">End Date</label>
                                <input type="date" wire:model="end_date"
                                    class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3.5 text-sm">
                                @error('end_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Shift Configuration</label>
                            <select wire:model="shift_type"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3.5 text-sm">
                                <option value="Regular">Regular (Single Shift)</option>
                                <option value="Dual">Dual Shift (Morning & Evening)</option>
                            </select>
                            @error('shift_type') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center gap-3 pt-1 p-3.5 bg-green-50 rounded-xl border border-green-100">
                            <input type="checkbox" wire:model="is_active" id="is_active_session"
                                class="rounded text-green-600 focus:ring-green-500 border-gray-300 w-5 h-5">
                            <div>
                                <label for="is_active_session" class="text-sm text-gray-700 font-semibold cursor-pointer">Set as Active Session</label>
                                <p class="text-xs text-gray-400 mt-0.5">This will deactivate all other sessions automatically.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex flex-row-reverse gap-3 border-t border-gray-100">
                    <button wire:click="store" class="inline-flex justify-center px-5 py-2.5 text-sm font-bold text-white bg-blue-600 border border-transparent rounded-xl shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all active:scale-95">
                        Save Session
                    </button>
                    <button wire:click="closeModal" class="inline-flex justify-center px-4 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm hover:bg-gray-50 transition-all active:scale-95">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Promotion Workflow Section --}}
    @if(!$isTeacherContext)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Bulk Student Promotion</h2>
            <p class="text-xs text-gray-500 mt-1">Promote students from one academic session to another. This will create new enrollments in the target session for selected students.</p>
        </div>

        @if(count($sessions) <= 1)
        <div class="p-4 bg-yellow-50 border border-yellow-200/60 rounded-2xl flex gap-3 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-yellow-600 shadow-yellow-200 text-white flex items-center justify-center shrink-0 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    ⚠️ New Academic Session Required
                </h4>
                <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                    Please create the next academic session first in the session table above before running the student promotion wizard.
                </p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">From Session (Source)</label>
                <select @disabled(count($sessions) <= 1) wire:model.live="fromSessionId" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3 text-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <option value="">Select Source Session</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">To Session (Target)</label>
                <select @disabled(count($sessions) <= 1) wire:model.live="toSessionId" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3 text-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <option value="">Select Target Session</option>
                    @foreach($sessions as $s)
                        @if($s->id != $fromSessionId)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <button
                    @disabled(count($sessions) <= 1)
                    wire:click="previewPromotion"
                    class="w-full inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl transition-all shadow-md active:scale-95 duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    Preview Promotion List
                </button>
            </div>
        </div>

        {{-- Preview Panel --}}
        @if($showPromotionPreview)
        <div class="border-t border-gray-100 pt-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Promotion Preview ({{ count($promotionPreview) }} Student(s) requiring review)</h3>
                    @if(count($autoPromoteList) > 0)
                        <p class="text-xs text-green-600 mt-1 font-semibold flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ count($autoPromoteList) }} student(s) passed all subjects in the Final-Term and will be promoted automatically.</span>
                        </p>
                    @else
                        <p class="text-xs text-gray-500">Configure target class, action status, and shift details for each student below.</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button
                        wire:click="savePromotion"
                        wire:confirm="Are you sure you want to process this promotion? This will create new enrollments in the target session."
                        class="inline-flex justify-center items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all"
                    >
                        Confirm & Process
                    </button>
                    <button wire:click="cancelPromotion" class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- Bulk Actions & Search Filter --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                {{-- Search Filter --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Search Students</label>
                    <div class="relative">
                        <input type="text" wire:model.live="searchQuery" placeholder="Search by name or class..." 
                            class="w-full text-xs rounded-lg border border-gray-200 pl-8 pr-3 py-2 focus:ring-1 focus:ring-blue-500 outline-none placeholder-gray-400">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                {{-- Mass Status Update --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mass Update Status</label>
                    <div class="flex gap-2">
                        <select wire:model="massStatus" class="w-full text-xs rounded-lg border border-gray-200 py-2 px-3 focus:ring-1 focus:ring-blue-500 outline-none bg-white">
                            <option value="promote">Promote</option>
                            <option value="passed_out">Passed Out / Graduate</option>
                            <option value="repeater">Repeater (Same Class)</option>
                            <option value="left_school">Left School / Transferred</option>
                        </select>
                        <button type="button" wire:click="applyMassStatus" class="px-3.5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all whitespace-nowrap">
                            Apply
                        </button>
                    </div>
                </div>

                {{-- Mass Shift Update --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mass Update Target Shift</label>
                    <div class="flex gap-2">
                        <select wire:model="massShift" class="w-full text-xs rounded-lg border border-gray-200 py-2 px-3 focus:ring-1 focus:ring-blue-500 outline-none bg-white">
                            <option value="">Select Shift</option>
                            @if($toSessionIsRegular)
                                <option value="regular">Regular</option>
                            @else
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                            @endif
                        </select>
                        <button type="button" wire:click="applyMassShift" class="px-3.5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all whitespace-nowrap">
                            Apply
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Student Name</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Current Class / Shift</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Target Class</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Target Shift</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Roll No</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($promotionPreview as $index => $item)
                        @if(empty($searchQuery) || stripos($item['student_name'], $searchQuery) !== false || stripos($item['current_class_name'], $searchQuery) !== false)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $item['student_name'] }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 font-medium">
                                {{ $item['current_class_name'] }} 
                                <span class="capitalize">({{ $item['current_shift'] }})</span>
                            </td>
                            <td class="px-4 py-3">
                                <select wire:model.live="promotionPreview.{{ $index }}.status" class="text-xs rounded-lg border-gray-200 py-1 px-2 focus:ring-1 focus:ring-blue-500 outline-none">
                                    <option value="promote">Promote</option>
                                    <option value="passed_out">Passed Out / Graduate</option>
                                    <option value="repeater">Repeater (Same Class)</option>
                                    <option value="left_school">Left School / Transferred</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                @if($promotionPreview[$index]['status'] === 'promote')
                                    <select wire:model.live="promotionPreview.{{ $index }}.target_class_id" class="text-xs rounded-lg border-gray-200 py-1 px-2 focus:ring-1 focus:ring-blue-500 outline-none">
                                        <option value="">Select Target Class</option>
                                        @foreach($targetClasses as $gc)
                                            <option value="{{ $gc->id }}">{{ $gc->name }}</option>
                                        @endforeach
                                    </select>
                                @elseif($promotionPreview[$index]['status'] === 'repeater')
                                    <select wire:model.live="promotionPreview.{{ $index }}.target_class_id" class="text-xs rounded-lg border-gray-200 py-1 px-2 focus:ring-1 focus:ring-blue-500 outline-none">
                                        <option value="">Select Target Class</option>
                                        @foreach($targetClasses->where('numeric_value', $item['current_class_numeric_value'] ?? null) as $gc)
                                            <option value="{{ $gc->id }}">{{ $gc->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-xs text-gray-400 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($promotionPreview[$index]['status'] === 'promote' || $promotionPreview[$index]['status'] === 'repeater')
                                    <select wire:model.live="promotionPreview.{{ $index }}.target_shift" class="text-xs rounded-lg border-gray-200 py-1 px-2 focus:ring-1 focus:ring-blue-500 outline-none">
                                        @if($toSessionIsRegular)
                                            <option value="regular">Regular</option>
                                        @else
                                            <option value="morning">Morning</option>
                                            <option value="evening">Evening</option>
                                        @endif
                                    </select>
                                @else
                                    <span class="text-xs text-gray-400 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($promotionPreview[$index]['status'] === 'promote' || $promotionPreview[$index]['status'] === 'repeater')
                                    <input type="text" wire:model.live="promotionPreview.{{ $index }}.roll_number" class="w-16 text-xs rounded-lg border-gray-200 py-1 px-2 focus:ring-1 focus:ring-blue-500 outline-none">
                                @else
                                    <span class="text-xs text-gray-400 italic">—</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
