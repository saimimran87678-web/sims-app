<div class="space-y-6">
    {{-- Header --}}
    <div class="glass-card p-6 rounded-2xl mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">My Students</h1>
                <p class="text-gray-500">{{ $className }} • {{ count($students) }} Students</p>
            </div>
            
             <div class="flex gap-3 w-full md:w-auto items-center">
                {{-- View Toggle & Sort --}}
                <div class="flex items-center gap-2">
                    <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
                        <button 
                            wire:click="$set('sortOrder', 'asc')" 
                            class="p-1.5 rounded-md transition-all {{ $sortOrder === 'asc' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400 hover:text-gray-600' }}"
                            title="Sort Ascending"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/><path d="M11 12h10"/><path d="M11 16h10"/><path d="M11 20h10"/></svg>
                        </button>
                        <button 
                            wire:click="$set('sortOrder', 'desc')" 
                            class="p-1.5 rounded-md transition-all {{ $sortOrder === 'desc' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400 hover:text-gray-600' }}"
                            title="Sort Descending"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="M11 4h10"/><path d="M11 8h10"/><path d="M11 12h10"/></svg>
                        </button>
                    </div>

                    <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
                        <button 
                            wire:click="$set('viewMode', 'grid')" 
                            class="p-1.5 rounded-md transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400 hover:text-gray-600' }}"
                            title="Grid View"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        </button>
                        <button 
                            wire:click="$set('viewMode', 'list')" 
                            class="p-1.5 rounded-md transition-all {{ $viewMode === 'list' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400 hover:text-gray-600' }}"
                            title="List View"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Search --}}
                 <div class="relative flex-1 md:w-64">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search students..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                    />
                </div>
                {{-- Add Button --}}
                @if($classId)
                <button
                    wire:click="create"
                    class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 whitespace-nowrap shadow-lg shadow-blue-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Add Student
                </button>
                @endif
            </div>
        </div>

        {{-- Advanced Filter Section --}}
        <div x-data="{ showFilters: false }">
            <button @click="showFilters = !showFilters" class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span x-text="showFilters ? 'Hide Filters' : 'Show Advanced Filters'"></span>
            </button>

            <div x-show="showFilters" x-collapse class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Details</label>
                    <div class="flex gap-2">
                         <select wire:model.live="filterSport" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm outline-none"><option value="">Sport</option>@foreach($sportsOptions as $s)<option value="{{$s->name}}">{{$s->name}}</option>@endforeach</select>
                         <select wire:model.live="filterActivity" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm outline-none"><option value="">Activity</option>@foreach($activityOptions as $a)<option value="{{$a->name}}">{{$a->name}}</option>@endforeach</select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Transport</label>
                    <div class="flex gap-2">
                        <select wire:model.live="filterTransport" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Any Transport</option>
                            @foreach(App\Models\Student::TRANSPORT_OPTIONS as $k => $l) <option value="{{ $k }}">{{ $l }}</option> @endforeach
                        </select>
                        <template x-if="$wire.filterTransport === 'school_bus'">
                            <select wire:model.live="filterBus" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none animate-fade-in">
                                <option value="">Any Bus</option>
                                @foreach(App\Models\Student::BUS_OPTIONS as $bus) <option value="{{ $bus }}">Bus {{ $bus }}</option> @endforeach
                            </select>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Status</label>
                    <select wire:model.live="filterStatus" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                        <option value="">All Statuses</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @if($students && count($students) > 0)
        @if($viewMode === 'list')
        <div class="glass-card rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Roll No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Father's Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($students as $student)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ $student->roll_no }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                   @if($student->profile_photo_path)
                                        <img src="{{ asset('storage/' . $student->profile_photo_path) }}" class="w-8 h-8 rounded-full object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                            {{ substr($student->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $student->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $student->admission_no }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $student->father_name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $student->phone ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ strtolower($student->gender) === 'male' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' }}">
                                    {{ ucfirst($student->gender ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $student->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($student->status ?? 'Active') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button
                                        wire:click="view({{ $student->id }})"
                                        class="text-gray-600 hover:text-gray-900 p-1 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="View Profile"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button wire:click="edit({{ $student->id }})" class="text-blue-600 hover:text-blue-900 p-1 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button 
                                        wire:click="delete({{ $student->id }})" 
                                        wire:confirm="Are you sure you want to delete this student?"
                                        class="text-red-600 hover:text-red-900 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        {{-- Grid View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($students as $student)
                <div class="glass-card rounded-2xl p-6 hover:shadow-lg transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-600 transform -translate-x-full group-hover:translate-x-0 transition-transform"></div>
                    
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex gap-4">
                            {{-- Avatar --}}
                            <div class="shrink-0 relative">
                                @if($student->profile_photo_path)
                                    <img src="{{ asset('storage/' . $student->profile_photo_path) }}" class="w-16 h-16 rounded-2xl object-cover shadow-sm">
                                @else
                                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold shadow-sm shadow-blue-200">
                                        {{ substr($student->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center text-[10px] text-white font-bold {{ strtolower($student->gender) === 'male' ? 'bg-blue-400' : 'bg-pink-400' }}" title="{{ ucfirst($student->gender) }}">
                                    {{ substr(ucfirst($student->gender ?? 'N'), 0, 1) }}
                                </div>
                            </div>
                            
                            {{-- Name --}}
                            <div class="flex flex-col">
                                <h3 class="font-bold text-gray-900 line-clamp-1 text-lg">{{ $student->name }}</h3>
                                <p class="text-xs text-gray-500 font-medium mb-1 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    {{ $student->father_name ?? 'Father Info Missing' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Badges --}}
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide bg-blue-50 text-blue-700 border border-blue-100">
                            Roll No: {{ $student->roll_no }}
                        </span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide bg-gray-50 text-gray-600 border border-gray-200">
                            Adm: {{ $student->admission_no }}
                        </span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide {{ $student->status === 'active' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100' }}">
                            {{ ucfirst($student->status ?? 'Active') }}
                        </span>
                    </div>

                    {{-- Info Grid --}}
                    <div class="space-y-2 mb-5 pt-4 border-t border-gray-50">
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <span class="truncate">{{ $student->phone ?? 'No phone' }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                             <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <span class="truncate line-clamp-1">{{ $student->address ?? 'No address' }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 mt-4">
                        <button wire:click="view({{ $student->id }})" class="flex-1 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-100 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                            View Profile
                        </button>
                        <button wire:click="edit({{ $student->id }})" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors border border-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        </button>
                        <button wire:click="delete({{ $student->id }})" wire:confirm="Delete this student?" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors border border-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    @else
        <div class="glass-card p-12 text-center rounded-2xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900">No Students Found</h3>
            <p class="mt-2 text-gray-500">Click "Add Student" to add students to your class.</p>
        </div>
    @endif

    {{-- Add/Edit Student Modal --}}
    @if($isModalOpen)
    @teleport('body')
    <div class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
             {{-- Modal Panel --}}
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <form wire:submit="store" class="flex flex-col max-h-[90vh]">
                     <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center rounded-t-2xl">
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                            {{ $isEditMode ? 'Edit Student' : 'Add New Student' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-4 py-5 sm:p-6 overflow-y-auto custom-scrollbar">
                         {{-- Profile Photo --}}
                        <div class="flex flex-col items-center justify-center mb-6">
                            <div class="relative group">
                                @if ($photo) 
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-100 shadow-lg">
                                @elseif ($isEditMode && \App\Models\Student::find($editStudentId)->profile_photo_path)
                                    <img src="{{ asset('storage/' . \App\Models\Student::find($editStudentId)->profile_photo_path) }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-100 shadow-lg">
                                @else
                                    <div class="w-24 h-24 rounded-full bg-blue-50 flex items-center justify-center border-4 border-blue-100 shadow-lg text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    </div>
                                @endif
                                
                                {{-- Overlay Button --}}
                                <label for="photoInput" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <span class="text-white text-xs font-bold bg-black/50 px-2 py-1 rounded-md mb-1">Upload</span>
                                </label>
                            </div>
                            
                            {{-- Actual Input --}}
                             <input wire:model="photo" id="photoInput" type="file" class="hidden" accept="image/*">

                             <div class="mt-2 text-center">
                                <label for="photoInput" class="text-sm text-blue-600 font-medium cursor-pointer hover:underline">
                                    {{ $isEditMode && \App\Models\Student::find($editStudentId)->profile_photo_path ? 'Change Photo' : 'Upload Photo' }}
                                </label>
                                @error('photo') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                             </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="Student Name" />
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Admission No *</label>
                                    <input type="text" wire:model="admission_no" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="ADM-2025-001" />
                                    @error('admission_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Roll No *</label>
                                    <input type="text" wire:model="roll_no" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="001" />
                                    @error('roll_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                    <input type="date" wire:model="dob" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                     @error('dob') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Admission Date</label>
                                    <input type="date" wire:model="admission_date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                     @error('admission_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700">Gender *</label>
                                    <select wire:model="gender" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700">Father's Name</label>
                                    <input type="text" wire:model="father_name" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="Father's Name" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Transport Mode</label>
                                    <select wire:model.live="transport_mode" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        @foreach(App\Models\Student::TRANSPORT_OPTIONS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('transport_mode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                @if($transport_mode === 'school_bus')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Bus</label>
                                    <select wire:model="vehicle_number" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option value="">Select Bus...</option>
                                        @foreach(App\Models\Student::BUS_OPTIONS as $bus)
                                            <option value="{{ $bus }}">{{ $bus }}</option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                @elseif($transport_mode === 'private_van' || $transport_mode === 'car' || $transport_mode === 'bike')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle/Van No</label>
                                    <input wire:model="vehicle_number" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="e.g. LE-123" />
                                    @error('vehicle_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                @endif
                                
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input wire:model="phone" type="tel" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="+1234567890" />
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address (Optional)</label>
                                    <input wire:model="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" placeholder="student@example.com" />
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea wire:model="address" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 resize-none h-20" placeholder="Street Address, Area, City"></textarea>
                            </div>

                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sports</label>
                            <div class="space-y-1 h-32 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 mb-2">
                                @foreach($sportsOptions as $sport)
                                    <div class="flex items-center justify-between hover:bg-gray-100 p-1 rounded group">
                                        @if($editingOptionId === $sport->id)
                                            <div class="flex items-center gap-2 w-full">
                                                <input type="text" wire:model="editingOptionName" class="flex-1 text-sm border-gray-300 rounded px-2 py-1" @keydown.enter.prevent="$wire.renameOption()">
                                                <button type="button" wire:click="renameOption" class="text-green-600 hover:text-green-700 p-1">✓</button>
                                                <button type="button" wire:click="$set('editingOptionId', null)" class="text-gray-500 hover:text-gray-700 p-1">✕</button>
                                            </div>
                                        @else
                                            <label class="flex items-center space-x-2 cursor-pointer flex-1">
                                                <input type="checkbox" wire:model="sports" value="{{ $sport->name }}" class="rounded text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700">{{ $sport->name }}</span>
                                            </label>
                                            <div class="hidden group-hover:flex items-center gap-1">
                                                <button type="button" wire:click="startEditOption({{ $sport->id }}, '{{ addslashes($sport->name) }}')" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-1 rounded transition-colors">Edit</button>
                                                <button type="button" wire:click="deleteOption({{ $sport->id }})" class="text-xs font-medium text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors">Delete</button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input type="text" wire:model="newSportName" placeholder="Add new sport..." class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-2 py-1" @keydown.enter.prevent="$wire.addOption('sport')">
                                <button type="button" wire:click="addOption('sport')" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition-colors">+</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Extra-Curricular Activities</label>
                            <div class="space-y-1 h-32 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 mb-2">
                                @foreach($activityOptions as $activity)
                                    <div class="flex items-center justify-between hover:bg-gray-100 p-1 rounded group">
                                        @if($editingOptionId === $activity->id)
                                            <div class="flex items-center gap-2 w-full">
                                                <input type="text" wire:model="editingOptionName" class="flex-1 text-sm border-gray-300 rounded px-2 py-1" @keydown.enter.prevent="$wire.renameOption()">
                                                <button type="button" wire:click="renameOption" class="text-green-600 hover:text-green-700 p-1">✓</button>
                                                <button type="button" wire:click="$set('editingOptionId', null)" class="text-gray-500 hover:text-gray-700 p-1">✕</button>
                                            </div>
                                        @else
                                            <label class="flex items-center space-x-2 cursor-pointer flex-1">
                                                <input type="checkbox" wire:model="extra_curriculars" value="{{ $activity->name }}" class="rounded text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700">{{ $activity->name }}</span>
                                            </label>
                                            <div class="hidden group-hover:flex items-center gap-1">
                                                <button type="button" wire:click="startEditOption({{ $activity->id }}, '{{ addslashes($activity->name) }}')" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-1 rounded transition-colors">Edit</button>
                                                <button type="button" wire:click="deleteOption({{ $activity->id }})" class="text-xs font-medium text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors">Delete</button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input type="text" wire:model="newActivityName" placeholder="Add new activity..." class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-2 py-1" @keydown.enter.prevent="$wire.addOption('activity')">
                                <button type="button" wire:click="addOption('activity')" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition-colors">+</button>
                            </div>
                        </div>
                            </div>

                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-gray-100">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <span wire:loading.remove wire:target="store">{{ $isEditMode ? 'Update Student' : 'Add Student' }}</span>
                            <span wire:loading wire:target="store">Saving...</span>
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif
    
    {{-- View Profile Modal --}}
    @if($showViewModal && $viewingStudent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto" x-transition>
            <div class="bg-gray-100 rounded-2xl w-full max-w-5xl shadow-2xl relative flex flex-col h-[90vh]" @click.away="$set('showViewModal', false)">
                
                {{-- Modal Header --}}
                <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-white rounded-t-2xl z-10 sticky top-0 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Student Bio-Data
                    </h3>
                    <div class="flex items-center gap-3">
                         <button onclick="printDiv('print-area')" class="text-gray-500 hover:text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                            Print
                        </button>
                        <button type="button" wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-2 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body (A4 Style Viewer) --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-gray-100 p-8 flex justify-center">
                     {{-- 'Paper' Container - Resume Style --}}
                    <div id="print-area" class="bg-white w-[210mm] min-h-[297mm] shadow-2xl mx-auto text-gray-800 relative" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;" x-data="{ showPhotoModal: false }">
                        
                        {{-- Print Styles --}}
                        <style>
                            @media print {
                                body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                                #print-area { box-shadow: none; margin: 0; width: 100%; }
                                .no-print { display: none !important; }
                            }
                            .profile-section-title { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #111; }
                            .profile-row { display: flex; margin-bottom: 8px; font-size: 13px; }
                            .profile-label { width: 120px; color: #666; font-weight: 500; }
                            .profile-value { flex: 1; color: #111; font-weight: 400; }
                        </style>

                        {{-- School Header --}}
                        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); color: #fff; padding: 20px 30px; text-align: center;">
                            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 3px;">Islamabad Model College for Boys</h1>
                            <p style="font-size: 12px; margin: 0; opacity: 0.9;">G-6/2, Islamabad</p>
                        </div>

                        {{-- Photo Lightbox Modal --}}
                        <div x-show="showPhotoModal" x-cloak @click="showPhotoModal = false" 
                             style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: zoom-out;"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100">
                            @if($viewingStudent->profile_photo_path)
                                <img src="{{ asset('storage/' . $viewingStudent->profile_photo_path) }}" 
                                     style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                            @endif
                            <button @click="showPhotoModal = false" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px;">✕</button>
                        </div>

                        {{-- Two Column Layout --}}
                        <div style="display: flex;">
                            
                            {{-- LEFT SIDEBAR --}}
                            <div style="width: 35%; background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%); padding: 30px 20px; border-right: 1px solid #dee2e6;">
                                
                                {{-- Profile Photo - Clickable --}}
                                <div style="text-align: center; margin-bottom: 25px;">
                                    @if($viewingStudent->profile_photo_path)
                                        <div @click="showPhotoModal = true" style="cursor: zoom-in; position: relative; display: inline-block;">
                                            <img src="{{ asset('storage/' . $viewingStudent->profile_photo_path) }}" 
                                                 style="width: 140px; height: 170px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border-radius: 8px;">
                                            <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.6); color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 9px;">
                                                🔍 Click to enlarge
                                            </div>
                                        </div>
                                    @else
                                        <div style="width: 140px; height: 170px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 56px; font-weight: 700; text-transform: uppercase; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border-radius: 8px;">
                                            {{ strtoupper(substr($viewingStudent->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Contact Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                                        Contact
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Phone</span>
                                        <span class="profile-value">{{ $viewingStudent->phone ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Email</span>
                                        <span class="profile-value">{{ $viewingStudent->email ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Address</span>
                                        <span class="profile-value" style="line-height: 1.4;">{{ $viewingStudent->address ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Personal Info Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                                        Personal Info
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Father's Name</span>
                                        <span class="profile-value" style="font-weight: 600;">{{ $viewingStudent->father_name ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Date of Birth</span>
                                        <span class="profile-value">{{ $viewingStudent->dob ? \Carbon\Carbon::parse($viewingStudent->dob)->format('d-m-Y') : '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Gender</span>
                                        <span class="profile-value" style="text-transform: capitalize;">{{ $viewingStudent->gender ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Transport Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/></svg>
                                        Transport
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Mode</span>
                                        <span class="profile-value" style="text-transform: capitalize;">{{ str_replace('_', ' ', $viewingStudent->transport_mode ?? 'None') }}</span>
                                    </div>
                                    @if($viewingStudent->vehicle_number)
                                    <div class="profile-row">
                                        <span class="profile-label">Vehicle No</span>
                                        <span class="profile-value" style="font-family: monospace; font-weight: 600;">{{ $viewingStudent->vehicle_number }}</span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Activities Section --}}
                                <div>
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0zM2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6.5a.5.5 0 0 1-1 0V1H3v14h3.5a.5.5 0 0 1 0 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/><path d="M4.5 3a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                                        Activities
                                    </div>
                                    @php
                                        $activities = array_filter(array_merge(
                                            explode(',', $viewingStudent->sports ?? ''),
                                            explode(',', $viewingStudent->extra_curriculars ?? '')
                                        ));
                                    @endphp
                                    @if(count($activities) > 0)
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            @foreach($activities as $activity)
                                                <span style="background: #fff; border: 1px solid #dee2e6; padding: 3px 10px; border-radius: 15px; font-size: 11px;">{{ trim($activity) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #999; font-size: 12px;">None specified</span>
                                    @endif
                                </div>
                            </div>

                            {{-- RIGHT MAIN CONTENT --}}
                            <div style="width: 65%; padding: 30px; background: #fff;">
                                
                                {{-- Name Header --}}
                                <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 3px solid #111;">
                                    <h1 style="font-size: 32px; font-weight: 700; color: #111; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 2px;">{{ $viewingStudent->name }}</h1>
                                    <p style="font-size: 14px; color: #666; margin: 0;">Student Profile</p>
                                </div>

                                {{-- Academic Details Section --}}
                                <div style="margin-bottom: 30px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5Z"/><path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032Z"/></svg>
                                        Academic Details
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                        @php
                                            // Parse class name like "Class 10B" to extract number and section
                                            $className = $viewingStudent->class->name ?? '';
                                            preg_match('/(\d+)([A-Za-z])?/', $className, $matches);
                                            $classNumber = isset($matches[1]) ? $matches[1] : $className;
                                            $sectionLetter = isset($matches[2]) ? strtoupper($matches[2]) : ($viewingStudent->section->name ?? '-');
                                        @endphp
                                        <div class="profile-row">
                                            <span class="profile-label">Class</span>
                                            <span class="profile-value" style="font-weight: 600;">{{ $classNumber ? $classNumber . 'th' : 'N/A' }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Section</span>
                                            <span class="profile-value">{{ $sectionLetter }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Admission No</span>
                                            <span class="profile-value" style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $viewingStudent->admission_no }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Roll No</span>
                                            <span class="profile-value" style="font-family: monospace; font-weight: 700;">{{ $viewingStudent->roll_no }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Admission Date</span>
                                            <span class="profile-value">{{ $viewingStudent->admission_date ? \Carbon\Carbon::parse($viewingStudent->admission_date)->format('d M Y') : '-' }}</span>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>


                {{-- Print Helper --}}
                <script>
                    function printDiv(divId) {
                        var printContents = document.getElementById(divId).innerHTML;
                        var originalContents = document.body.innerHTML;
                        document.body.innerHTML = printContents;
                        window.print();
                        document.body.innerHTML = originalContents;
                        location.reload(); 
                    }
                </script>

            </div>
        </div>
    @endif
</div>

