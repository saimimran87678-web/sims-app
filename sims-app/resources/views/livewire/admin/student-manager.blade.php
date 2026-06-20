<div class="space-y-6">
    <x-slot name="header">Student Management</x-slot>

    <div class="glass-card p-6 rounded-2xl">
        {{-- Advanced Filter Section --}}
        <div x-data="{ showFilters: false }" class="mb-6">
            <button @click="showFilters = !showFilters" class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span x-text="showFilters ? 'Hide Filters' : 'Show Advanced Filters'"></span>
            </button>

            <div x-show="showFilters" x-collapse class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Session</label>
                    <select wire:model.live="selectedSessionId" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        @foreach($academicSessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }} @if($session->is_active) (Current) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Class</label>
                    <select wire:model.live="selectedClassId" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        @can('students.view-all-classes') <option value="">All Classes</option> @endcan
                        @foreach($classes as $cls) <option value="{{ $cls->id }}">{{ $cls->name }}</option> @endforeach
                    </select>
                </div>
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
                            @foreach(App\Livewire\Admin\StudentManager::TRANSPORT_OPTIONS as $k => $l) <option value="{{ $k }}">{{ $l }}</option> @endforeach
                        </select>
                        <template x-if="$wire.filterTransport === 'school_bus'">
                            <select wire:model.live="filterBus" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none animate-fade-in">
                                <option value="">Any Bus</option>
                                @foreach(App\Livewire\Admin\StudentManager::BUS_OPTIONS as $bus) <option value="{{ $bus }}">Bus {{ $bus }}</option> @endforeach
                            </select>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sort By</label>
                    <select wire:model.live="sortBy" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="roll_no">Roll Number</option>
                        <option value="name">Name</option>
                        <option value="admission_no">Admission No</option>
                        <option value="id">Registration Date (ID)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sort Order</label>
                    <select wire:model.live="sortDir" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-bold text-gray-800">Student Directory</h3>
            <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto items-center">
                <div class="relative w-full md:w-64">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, roll, adm..." class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                {{-- View Toggle --}}
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

                @can('student.create')
                <button
                    wire:click="openModal"
                    class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2 whitespace-nowrap"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Add Student
                </button>
                @endcan
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        @if(count($selectedStudentIds) > 0)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-2xl flex flex-col md:flex-row justify-between items-center gap-4 transition-all duration-300">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                    {{ count($selectedStudentIds) }}
                </div>
                <span class="text-sm font-semibold text-blue-900">students selected for bulk action</span>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if(count($this->bulkSubjects) > 0)
                    <div class="flex items-center gap-2">
                        <select wire:model="bulkSubjectId" class="px-3 py-1.5 rounded-xl border border-blue-200 text-sm focus:ring-2 focus:ring-blue-500 bg-white text-gray-700 font-medium">
                            <option value="">Choose Elective...</option>
                            @foreach($this->bulkSubjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                        <button wire:click="bulkAssignSubject" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl shadow-sm transition-colors">
                            Assign Group
                        </button>
                        <button wire:click="bulkUnassignSubject" class="px-4 py-1.5 bg-white border border-red-200 hover:bg-red-50 text-red-600 font-medium text-sm rounded-xl transition-colors">
                            Remove Group
                        </button>
                    </div>
                @else
                    <span class="text-xs text-blue-700 bg-blue-100/50 px-3 py-1.5 rounded-lg border border-blue-200/50">
                        Please filter by a class with divided/elective subjects to assign groups.
                    </span>
                @endif
                <button wire:click="$set('selectedStudentIds', [])" class="text-sm text-gray-500 hover:text-gray-700 underline font-medium">
                    Deselect All
                </button>
            </div>
        </div>
        @endif

        {{-- Content --}}
        @if($viewMode === 'list')
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer" />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('admission_no')">
                            <div class="flex items-center gap-1">
                                Adm No
                                @if($sortBy === 'admission_no')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('roll_no')">
                            <div class="flex items-center gap-1">
                                Roll No
                                @if($sortBy === 'roll_no')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('name')">
                            <div class="flex items-center gap-1">
                                Name
                                @if($sortBy === 'name')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Father's Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50 transition-colors {{ in_array($student->id, $selectedStudentIds) ? 'bg-blue-50/20' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 w-10">
                                <input type="checkbox" wire:model.live="selectedStudentIds" value="{{ $student->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->admission_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $student->roll_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $student->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 bg-blue-50/50">
                                <span class="px-2 py-1 rounded-md">{{ $student->class_name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->father_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->phone }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                <div class="flex gap-2 justify-end">
                                    <button
                                        wire:click="view({{ $student->id }})"
                                        class="text-gray-600 hover:text-gray-900 p-1 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="View Profile"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    @can('student.edit')
                                    <button
                                        wire:click="edit({{ $student->id }})"
                                        class="text-blue-600 hover:text-blue-900 p-1 hover:bg-blue-50 rounded-lg transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    @endcan
                                    @can('student.delete')
                                    <button
                                        wire:click="delete({{ $student->id }})"
                                        wire:confirm="Are you sure you want to delete {{ $student->name }}?"
                                        class="text-red-600 hover:text-red-900 p-1 hover:bg-red-50 rounded-lg transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colSpan="7" class="p-8 text-center text-gray-500">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @else
        {{-- Grid View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($students as $student)
                <div class="bg-white rounded-2xl p-6 shadow-sm border {{ in_array($student->id, $selectedStudentIds) ? 'border-blue-400 ring-2 ring-blue-500/20' : 'border-gray-100' }} hover:shadow-md transition-all relative overflow-hidden group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex gap-3 items-center">
                            {{-- Checkbox --}}
                            <input type="checkbox" wire:model.live="selectedStudentIds" value="{{ $student->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer w-4 h-4 shadow-sm shrink-0" />
                            
                            {{-- Avatar --}}
                            <div class="shrink-0">
                                @if($student->profile_photo_path)
                                    <img src="{{ asset('storage/' . $student->profile_photo_path) }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm">
                                @else
                                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold border-2 border-white shadow-sm">
                                        {{ substr($student->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Name & Details --}}
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1">{{ $student->name }}</h3>
                                <p class="text-xs text-gray-500 font-medium mb-1">Roll No: {{ $student->roll_no }}</p>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $student->class_name }}
                                </span>
                            </div>
                        </div>

                        {{-- Details Badges --}}
                         <div class="flex flex-wrap gap-2 mb-2">
                             <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-md border border-gray-200">Adm: {{ $student->admission_no }}</span>
                         </div>
                    </div>

                    {{-- Info Grid --}}
                    <div class="space-y-2 mb-4">
                        @if($student->father_name)
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="truncate" title="{{ $student->father_name }}">{{ $student->father_name }}</span>
                        </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>{{ $student->phone ?? 'No phone' }}</span>
                        </div>

                        <div class="flex items-start gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 mt-0.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="truncate line-clamp-1" title="{{ $student->address }}">{{ $student->address ?? 'No address' }}</span>
                        </div>
                    </div>
                
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                         <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            Active
                         </div>
                         <div class="flex gap-2">
                             <button wire:click="view({{ $student->id }})" class="text-gray-400 hover:text-blue-600 p-1" title="View Bio-Data">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                             </button>
                             @can('student.edit')
                             <button wire:click="edit({{ $student->id }})" class="text-gray-400 hover:text-blue-600 p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                             </button>
                             @endcan
                             @can('student.delete')
                             <button wire:click="delete({{ $student->id }})" wire:confirm="Are you sure?" class="text-gray-400 hover:text-red-600 p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                             </button>
                             @endcan
                         </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center text-gray-500 bg-white rounded-xl border border-gray-100">
                    No students found using these filters.
                </div>
            @endforelse
        </div>
        @endif
        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center p-4 text-center sm:items-center sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>

                {{-- Modal Panel --}}
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">{{ $isEditing ? 'Edit Student' : 'Add New Student' }}</h3>
                            <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>

                <form wire:submit="save" class="space-y-4">
                    {{-- Profile Photo --}}
                    {{-- Profile Photo --}}
                    {{-- Profile Photo --}}
                    <div class="flex flex-col items-center justify-center mb-6">
                        <div class="relative group">
                            @if ($photo) 
                                <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-100 shadow-lg">
                            @elseif ($isEditing && \App\Models\Student::find($editingStudentId)->profile_photo_path)
                                <img src="{{ asset('storage/' . \App\Models\Student::find($editingStudentId)->profile_photo_path) }}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-100 shadow-lg">
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
                                {{ $isEditing && \App\Models\Student::find($editingStudentId)->profile_photo_path ? 'Change Photo' : 'Upload Photo' }}
                            </label>
                            @error('photo') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                         </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input wire:model="name" type="text" required class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Student Name" />
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission No *</label>
                            <input wire:model="admission_no" type="text" required class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ADM-001" />
                            @error('admission_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Roll No *</label>
                            <input wire:model="roll_no" type="text" required class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="001" />
                            @error('roll_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input wire:model="dob" type="date" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" />
                            @error('dob') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                            <input wire:model="admission_date" type="date" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" />
                            @error('admission_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                        <select wire:model.live="class_id" required class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                            <option value="">Select Class</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                        @error('class_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                        @if($class_id)
                            @php
                                $dividedSubIds = DB::table('timetables')
                                    ->where('class_id', $class_id)
                                    ->where('is_divided', true)
                                    ->pluck('subject_id')
                                    ->unique();
                                $divSubjects = \App\Models\Subject::whereIn('id', $dividedSubIds)->orderBy('name')->get();
                            @endphp
                            @if($divSubjects->isNotEmpty())
                                <div class="mt-3 bg-gray-50 border border-gray-200 rounded-2xl p-4">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Subject Enrollments (Electives/Groups)</label>
                                    <p class="text-xs text-gray-400 mb-3">If unchecked, the student is enrolled in all subjects. Check specific subjects to restrict them to an elective group (e.g. Computer Science vs Biology).</p>
                                    <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto">
                                        @foreach($divSubjects as $sub)
                                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-200/30 p-1 rounded transition-colors">
                                                <input type="checkbox" wire:model="studentSubjects" value="{{ $sub->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                                <span class="text-sm text-gray-700 font-medium">{{ $sub->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                            <input wire:model="father_name" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Father's Name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                            <select wire:model="gender" required class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transport Mode</label>
                            <select wire:model.live="transport_mode" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                @foreach(App\Livewire\Admin\StudentManager::TRANSPORT_OPTIONS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transport_mode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if($transport_mode === 'school_bus')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Bus</label>
                            <select wire:model="vehicle_number" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                <option value="">Select Bus...</option>
                                @foreach(App\Livewire\Admin\StudentManager::BUS_OPTIONS as $bus)
                                    <option value="{{ $bus }}">{{ $bus }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @elseif($transport_mode === 'private_van' || $transport_mode === 'car' || $transport_mode === 'bike')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Details / Van No.</label>
                            <input wire:model="vehicle_number" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. Honda City LE-123" />
                            @error('vehicle_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input wire:model="phone" type="tel" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="03001234567" />
                            <p class="text-xs text-gray-400 mt-1">Pakistani format: 03XX-XXXXXXX</p>
                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address (Optional)</label>
                            <input wire:model="email" type="email" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="student@example.com" />
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea wire:model="address" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none resize-none h-20" placeholder="Street Address, Area, City"></textarea>
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

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
                            <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Update Student' : 'Add Student' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
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
