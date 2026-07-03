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
            <p class="text-sm text-gray-500 ml-10 font-medium">Manage and configure school academic years and shifts</p>
        </div>
        <div class="flex flex-wrap gap-3 ml-10 md:ml-0">
            <button wire:click="runAutoUpdate" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-xl hover:bg-purple-100 font-semibold text-sm transition-all shadow-sm active:scale-95 duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Auto-Generate Current</span>
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
                <h3 class="text-xl font-bold text-gray-800">
                    {{ $sessions->firstWhere('is_active', true)->name ?? 'None' }}
                </h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Shifts</p>
                <h3 class="text-xl font-bold text-gray-800">
                    {{ $sessions->where('is_active', true)->pluck('shift_type')->unique()->implode(', ') ?: 'Standard' }}
                </h3>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3.5 rounded-xl flex items-center gap-3 text-sm font-semibold animate-fade-in shadow-sm" role="alert">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('message') }}</span>
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
                    @foreach($sessions as $session)
                    <tr class="hover:bg-gray-50/70 transition-colors duration-150">
                        <td class="px-6 py-4.5 whitespace-nowrap text-sm font-semibold text-gray-900">
                            <div class="flex items-center gap-2.5">
                                <span class="text-gray-700 font-bold">{{ $session->name }}</span>
                                @if($session->shift_type === 'Evening')
                                    <span class="px-2 py-0.5 inline-flex text-[10px] leading-5 font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-100">Evening Shift</span>
                                @elseif($session->shift_type === 'Morning')
                                    <span class="px-2 py-0.5 inline-flex text-[10px] leading-5 font-bold rounded-full bg-orange-50 text-orange-700 border border-orange-100">Morning Shift</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4.5 whitespace-nowrap text-sm text-gray-500 font-medium">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ $session->start_date }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4.5 whitespace-nowrap text-sm text-gray-500 font-medium">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ $session->end_date }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4.5 whitespace-nowrap">
                            @if($session->is_active)
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-50 text-green-700 border border-green-200">
                                    Current Active
                                </span>
                            @elseif(\Carbon\Carbon::parse($session->start_date)->isFuture())
                                 <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                    Upcoming
                                </span>
                            @elseif(\Carbon\Carbon::parse($session->end_date)->isPast())
                                 <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-50 text-gray-600 border border-gray-200">
                                    Completed
                                </span>
                            @else
                                 <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4.5 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2.5">
                                @if(is_null($session->parent_id) && !$sessions->contains('parent_id', $session->id))
                                    <button wire:click="generateEveningShift({{ $session->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg transition-colors border border-purple-200 shadow-sm" title="Generate Evening Shift">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        Generate Evening
                                    </button>
                                @endif
                                <button wire:click="edit({{ $session->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 shadow-sm" title="Edit Session">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    Edit
                                </button>
                                @if(!$isTeacherContext)
                                <button wire:click="delete({{ $session->id }})" onclick="return confirm('Are you sure you want to delete this session? This action cannot be undone.') || event.stopImmediatePropagation()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-red-50 text-red-700 hover:bg-red-100 rounded-lg transition-colors border border-red-200 shadow-sm" title="Delete Session">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-600 bg-opacity-40 backdrop-blur-sm" aria-hidden="true" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full text-left align-bottom transition-all transform bg-white shadow-2xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-lg overflow-hidden border border-gray-100">
                <div class="px-6 py-6 bg-white sm:p-8">
                    <h3 class="text-xl font-bold leading-6 text-gray-900 mb-5" id="modal-title">
                        {{ $sessionId ? 'Edit Session' : 'Create Session' }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">Session Name</label>
                            <input type="text" wire:model="name" placeholder="e.g. 2025-2026" class="mt-1.5 block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all placeholder-gray-400 py-2.5 px-3.5 text-sm">
                            @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">Start Date</label>
                                <input type="date" wire:model="start_date" class="mt-1.5 block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3.5 text-sm">
                                @error('start_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">End Date</label>
                                <input type="date" wire:model="end_date" class="mt-1.5 block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3.5 text-sm">
                                @error('end_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="is_active" id="is_active_session" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 w-5 h-5">
                            <label for="is_active_session" class="ml-2.5 text-sm text-gray-700 font-semibold cursor-pointer">Set as Active Session</label>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4.5 bg-gray-50 flex flex-row-reverse gap-3">
                    <button wire:click="store" class="inline-flex justify-center px-4 py-2.5 text-sm font-bold text-white bg-blue-600 border border-transparent rounded-xl shadow-md shadow-blue-100 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all active:scale-95">
                        Save
                    </button>
                    <button wire:click="closeModal" class="inline-flex justify-center px-4 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all active:scale-95">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
