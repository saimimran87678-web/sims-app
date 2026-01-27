<div class="space-y-6">
    {{-- Schedule Configuration Bar --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <x-schedule-menu />
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Schedule Management</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <select wire:model.live="selectedTemplateId" class="text-sm border-none bg-gray-50 rounded-lg py-1 pl-2 pr-8 focus:ring-2 focus:ring-blue-500 font-medium text-gray-700">
                            @foreach($scheduleTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }} {{ $tpl->is_active ? '(Active)' : '' }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('admin.period-config') }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded transition flex items-center gap-1">
                             <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                             Configs
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Date Picker for Substitutions --}}
                <div class="relative">
                    <input type="date" wire:model.live="specificDate" class="py-1.5 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" placeholder="View Specific Date" />
                    @if($specificDate)
                        <button wire:click="$set('specificDate', null)" class="absolute right-8 top-1.5 text-gray-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                {{-- Template Actions --}}
                @if($selectedTemplateId != $activeTemplateId)
                    <div class="flex gap-2">
                         <button wire:click="activateTemplate({{ $selectedTemplateId }})" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-green-700 transition shadow-sm">
                            Activate
                        </button>
                        <button wire:click="deleteTemplate({{ $selectedTemplateId }})" wire:confirm="Are you sure you want to delete this schedule? All assignments and period settings for this schedule will be lost." class="bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-red-200 transition shadow-sm">
                            Delete
                        </button>
                    </div>
                @else
                    <span class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1 border border-green-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Active Live
                    </span>
                @endif
                
                <div class="border-l border-gray-200 h-8 mx-1"></div>

                <div class="flex gap-2">
                    <input type="text" wire:model="newTemplateName" placeholder="New Template Name" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 w-40 focus:ring-2 focus:ring-blue-500">
                    <button wire:click="createTemplate" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition shadow-sm whitespace-nowrap">
                        + New
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700">{{ session('message') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700">{{ session('error') }}</div>
    @endif

    {{-- View Mode Toggle --}}
    <div class="flex justify-center mb-6">
        <div class="bg-gray-100 p-1 rounded-xl inline-flex shadow-inner">
            <button 
                wire:click="$set('viewMode', 'class')" 
                class="px-6 py-2 rounded-lg text-sm font-bold transition-all {{ $viewMode === 'class' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                By Class
            </button>
            <button 
                wire:click="$set('viewMode', 'teacher')" 
                class="px-6 py-2 rounded-lg text-sm font-bold transition-all {{ $viewMode === 'teacher' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                By Teacher
            </button>
            <button 
                wire:click="$set('viewMode', 'room')" 
                class="px-6 py-2 rounded-lg text-sm font-bold transition-all {{ $viewMode === 'room' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                By Room
            </button>
        </div>
    </div>

    {{-- Day Tabs --}}
    <div class="flex flex-wrap gap-2 justify-between items-center border-b border-gray-200 pb-3">
        <div class="flex gap-2 items-center">
            <button
                wire:click="$set('selectedDay', 'Everyday')"
                class="px-4 py-2 rounded-t-lg font-medium transition-colors {{ $selectedDay === 'Everyday' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
            >
                Everyday
            </button>
            <span class="text-gray-300">|</span>
            @foreach($days as $day)
                <button
                    wire:click="$set('selectedDay', '{{ $day }}')"
                    class="px-4 py-2 rounded-t-lg font-medium transition-colors {{ $selectedDay === $day ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ substr($day, 0, 3) }}
                </button>
            @endforeach
            <label class="flex items-center gap-1.5 ml-2 cursor-pointer">
                <input type="checkbox" wire:model.live="includeSaturday" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="text-sm text-gray-600">+Sat</span>
            </label>
        </div>
        <div class="flex gap-2">
            <button
                wire:click="copyToAllDays"
                wire:confirm="Copy {{ $selectedDay }}'s schedule to all other weekdays? This will replace existing schedules."
                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                Copy to All
            </button>
            <button
                wire:click="clearDay"
                wire:confirm="Clear all schedule entries for {{ $selectedDay }}?"
                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Clear Day
            </button>
            {{-- View Schedule button removed (moved to sidebar) --}}
        </div>
    </div>

    {{-- Schedule Grid --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                {{-- Hide main grid header in Room/Teacher mode since we show vertical schedule --}}
                @if($viewMode !== 'room' && $viewMode !== 'teacher')
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-32 sticky left-0 bg-gray-50">
                            {{ $viewMode === 'teacher' ? 'Teacher' : 'Class' }}
                        </th>
                        @foreach($periods as $period)
                            <th class="px-2 py-3 text-center text-xs font-medium {{ $period->is_break ? 'bg-yellow-50 text-yellow-700' : ($period->is_assembly ? 'bg-purple-50 text-purple-700' : 'text-gray-500') }} min-w-[140px]">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                @endif
                <tbody class="bg-white divide-y divide-gray-100">
                    @if($viewMode === 'class')
                        @foreach($classes as $class)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-sm font-bold text-gray-800 sticky left-0 bg-white">{{ $class->name }}</td>
                                @foreach($periods as $period)
                                    @if($period->is_break)
                                        <td class="px-2 py-3 bg-yellow-50/50 text-center">
                                            <span class="text-yellow-600 text-xs">Break</span>
                                        </td>
                                    @elseif($period->is_assembly)
                                        <td class="px-2 py-3 bg-purple-50/50 text-center">
                                            <span class="text-purple-600 text-xs">Assembly</span>
                                        </td>
                                    @else
                                        @php $schedule = $this->getSchedule($class->id, $period->period_no); @endphp
                                        <td
                                            wire:click="openModal({{ $class->id }}, {{ $period->period_no }})"
                                            class="px-2 py-2 cursor-pointer hover:bg-blue-50 transition-colors border-l border-gray-100"
                                        >
                                            @if($schedule)
                                                @php
                                                    $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                    $subject = $subjects[$schedule->subject_id] ?? null;
                                                @endphp
                                                <div class="text-xs space-y-0.5">
                                                    <div class="font-bold text-blue-700 truncate">
                                                        {{ $subject->name ?? '-' }}
                                                        @if($schedule->subject_id_2)
                                                            <span class="text-pink-600">+ {{ ($subjects[$schedule->subject_id_2] ?? null)?->name ?? '' }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-gray-500 truncate">
                                                        {{ $teacher->name ?? '-' }}
                                                        @if($schedule->is_divided && isset($dividedTeachers[$schedule->id]))
                                                            @php
                                                                $teacher2 = collect($teachers)->firstWhere('id', $dividedTeachers[$schedule->id]);
                                                            @endphp
                                                            @if($teacher2)
                                                                <span class="text-purple-500 mx-1">|</span>
                                                                <span class="text-purple-600">{{ $teacher2->name }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @if($schedule->is_divided)
                                                        <span class="text-[10px] text-purple-600 bg-purple-50 px-1 rounded">Divided</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-center text-gray-300 text-xs py-2">+ Assign</div>
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @elseif($viewMode === 'teacher')
                        {{-- Teacher View - Searchable Selector + Vertical Schedule --}}
                        <tr>
                            <td colspan="{{ count($periods) + 1 }}" class="p-0 bg-gradient-to-r from-indigo-50 to-blue-50">
                                <div class="p-4">
                                    <div class="flex items-center gap-4 mb-4">
                                        <label class="text-sm font-bold text-gray-700">Select Teacher:</label>
                                        <div class="relative min-w-[300px]" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                            {{-- Selected display / Search input --}}
                                            @if($selectedTeacherViewId)
                                                {{-- Show selected teacher with change button --}}
                                                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-blue-300 rounded-xl">
                                                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                                        {{ substr(collect($teachers)->firstWhere('id', $selectedTeacherViewId)?->name ?? 'T', 0, 1) }}
                                                    </span>
                                                    <span class="font-medium text-gray-800 flex-1">{{ collect($teachers)->firstWhere('id', $selectedTeacherViewId)?->name }}</span>
                                                    <button wire:click="clearTeacherView" class="text-gray-400 hover:text-red-500 transition">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @else
                                                {{-- Searchable input with dropdown toggle --}}
                                                <div class="relative">
                                                    <input 
                                                        type="text" 
                                                        wire:model.live="searchTeacher" 
                                                        @focus="open = true"
                                                        @click="open = true"
                                                        placeholder="Click to select or type to search..." 
                                                        class="w-full px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-medium"
                                                        autocomplete="off"
                                                    />
                                                    <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                        <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                
                                                {{-- Dropdown list --}}
                                                <div 
                                                    x-show="open" 
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-64 overflow-y-auto"
                                                >
                                                    @php
                                                        $searchTerm = $searchTeacher ?? '';
                                                        $filteredTeachers = empty($searchTerm) 
                                                            ? collect($teachers) 
                                                            : collect($teachers)->filter(fn($t) => str_contains(strtolower($t->name), strtolower($searchTerm)));
                                                    @endphp
                                                    
                                                    @if($filteredTeachers->isEmpty())
                                                        <div class="px-4 py-3 text-gray-400 text-sm text-center">No teachers found</div>
                                                    @else
                                                        @foreach($filteredTeachers as $t)
                                                            <div 
                                                                wire:click="selectTeacherView({{ $t->id }})"
                                                                @click="open = false"
                                                                class="flex items-center gap-3 px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                                                            >
                                                                <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm">
                                                                    {{ substr($t->name, 0, 1) }}
                                                                </span>
                                                                <span class="text-sm font-medium text-gray-700">{{ $t->name }}</span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($selectedTeacherViewId)
                                        @php $selectedTeacher = collect($teachers)->firstWhere('id', $selectedTeacherViewId); @endphp
                                        <div class="bg-white rounded-2xl shadow-sm p-4 max-w-3xl">
                                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">
                                                    {{ substr($selectedTeacher->name ?? 'T', 0, 1) }}
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-lg text-gray-800">{{ $selectedTeacher->name ?? 'Teacher' }}</h3>
                                                    <p class="text-xs text-gray-500">{{ $selectedDay === 'Everyday' ? 'All Days' : $selectedDay }} Schedule</p>
                                                </div>
                                            </div>

                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50/50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-40">Period</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Class & Subject</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-32">Room</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    @foreach($periods as $period)
                                                        @if($period->is_break)
                                                            <tr class="bg-yellow-50">
                                                                <td class="px-4 py-2 text-sm font-medium text-yellow-700">{{ $period->label }}</td>
                                                                <td colspan="2" class="px-4 py-2 text-xs text-yellow-600">Break</td>
                                                            </tr>
                                                        @elseif($period->is_assembly)
                                                            <tr class="bg-purple-50">
                                                                <td class="px-4 py-2 text-sm font-medium text-purple-700">{{ $period->label }}</td>
                                                                <td colspan="2" class="px-4 py-2 text-xs text-purple-600">Assembly</td>
                                                            </tr>
                                                        @else
                                                            @php $schedule = $this->getSchedule($selectedTeacherViewId, $period->period_no); @endphp
                                                            <tr 
                                                                wire:click="openModal({{ $selectedTeacherViewId }}, {{ $period->period_no }})"
                                                                class="hover:bg-blue-50 cursor-pointer transition-colors"
                                                            >
                                                                <td class="px-4 py-3">
                                                                    <div class="font-bold text-gray-800 text-sm">{{ $period->label }}</div>
                                                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</div>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if($schedule)
                                                                        @php
                                                                            $className = $classes->firstWhere('id', $schedule->class_id)->name ?? '-';
                                                                            $subject = $subjects[$schedule->subject_id] ?? null;
                                                                        @endphp
                                                                        <span class="font-bold text-indigo-700">
                                                                            {{ $className }}
                                                                        </span>
                                                                        <span class="text-gray-400 mx-1">|</span>
                                                                        <span class="text-sm text-gray-600">
                                                                            {{ $subject->name ?? '-' }}
                                                                            @if($schedule->subject_id_2)
                                                                                <span class="text-pink-600">+ {{ ($subjects[$schedule->subject_id_2] ?? null)?->name ?? '' }}</span>
                                                                            @endif
                                                                        </span>
                                                                        @if($schedule->is_divided && isset($dividedTeachers[$schedule->id]))
                                                                            @php
                                                                                $otherTeacher = collect($teachers)->firstWhere('id', $dividedTeachers[$schedule->id]);
                                                                            @endphp
                                                                            <div class="text-[10px] text-purple-600 mt-1">
                                                                                Divided @if($otherTeacher)<span class="text-purple-500">| {{ $otherTeacher->name }}</span>@endif
                                                                            </div>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-gray-300 text-sm hover:text-blue-500">+ Click to Assign</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if($schedule && $schedule->room)
                                                                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">{{ $schedule->room }}</span>
                                                                    @else
                                                                        <span class="text-gray-300 text-xs">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="bg-white rounded-2xl p-8 text-center text-gray-400">
                                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <p class="font-medium">Select a teacher from the dropdown above</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @else
                        {{-- Room View - Class Selector + Vertical Schedule --}}
                        <tr>
                            <td colspan="{{ count($periods) + 1 }}" class="p-0 bg-gradient-to-r from-indigo-50 to-purple-50">
                                <div class="p-4">
                                    <div class="flex items-center gap-4 mb-4">
                                        <label class="text-sm font-bold text-gray-700">Select Class:</label>
                                        <div class="relative min-w-[300px]" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                            {{-- Selected display / Search input --}}
                                            @if($selectedRoomClass)
                                                {{-- Show selected class with change button --}}
                                                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-green-300 rounded-xl">
                                                    <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                                        {{ substr($classes->firstWhere('id', $selectedRoomClass)?->name ?? 'C', 0, 1) }}
                                                    </span>
                                                    <span class="font-medium text-gray-800 flex-1">{{ $classes->firstWhere('id', $selectedRoomClass)?->name }}</span>
                                                    <button wire:click="clearRoomClass" class="text-gray-400 hover:text-red-500 transition">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @else
                                                {{-- Searchable input with dropdown toggle --}}
                                                <div class="relative">
                                                    <input 
                                                        type="text" 
                                                        wire:model.live="searchClass" 
                                                        @focus="open = true"
                                                        @click="open = true"
                                                        placeholder="Click to select or type to search..." 
                                                        class="w-full px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none bg-white font-medium"
                                                        autocomplete="off"
                                                    />
                                                    <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                        <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                
                                                {{-- Dropdown list --}}
                                                <div 
                                                    x-show="open" 
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-64 overflow-y-auto"
                                                >
                                                    @php
                                                        $searchTerm = $searchClass ?? '';
                                                        $filteredClasses = empty($searchTerm) 
                                                            ? $classes 
                                                            : $classes->filter(fn($c) => str_contains(strtolower($c->name), strtolower($searchTerm)));
                                                    @endphp
                                                    
                                                    @if($filteredClasses->isEmpty())
                                                        <div class="px-4 py-3 text-gray-400 text-sm text-center">No classes found</div>
                                                    @else
                                                        @foreach($filteredClasses as $c)
                                                            <div 
                                                                wire:click="selectRoomClass({{ $c->id }})"
                                                                @click="open = false"
                                                                class="flex items-center gap-3 px-4 py-2 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                                                            >
                                                                <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm">
                                                                    {{ substr($c->name, 0, 1) }}
                                                                </span>
                                                                <span class="text-sm font-medium text-gray-700">{{ $c->name }}</span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($selectedRoomClass)
                                        @php $selectedClass = $classes->firstWhere('id', $selectedRoomClass); @endphp
                                        <div class="bg-white rounded-2xl shadow-sm p-4 max-w-3xl">
                                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
                                                    {{ substr($selectedClass->name ?? 'C', 0, 1) }}
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-lg text-gray-800">{{ $selectedClass->name ?? 'Class' }}</h3>
                                                    <p class="text-xs text-gray-500">{{ $selectedDay === 'Everyday' ? 'All Days' : $selectedDay }} Schedule</p>
                                                </div>
                                            </div>

                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50/50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-40">Period</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Subject & Teacher</th>
                                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-32">Room</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    @foreach($periods as $period)
                                                        @if($period->is_break)
                                                            <tr class="bg-yellow-50">
                                                                <td class="px-4 py-2 text-sm font-medium text-yellow-700">{{ $period->label }}</td>
                                                                <td colspan="2" class="px-4 py-2 text-xs text-yellow-600">Break</td>
                                                            </tr>
                                                        @elseif($period->is_assembly)
                                                            <tr class="bg-purple-50">
                                                                <td class="px-4 py-2 text-sm font-medium text-purple-700">{{ $period->label }}</td>
                                                                <td colspan="2" class="px-4 py-2 text-xs text-purple-600">Assembly</td>
                                                            </tr>
                                                        @else
                                                            @php $schedule = $this->getSchedule($selectedRoomClass, $period->period_no); @endphp
                                                            <tr 
                                                                wire:click="openModal({{ $selectedRoomClass }}, {{ $period->period_no }})"
                                                                class="hover:bg-blue-50 cursor-pointer transition-colors"
                                                            >
                                                                <td class="px-4 py-3">
                                                                    <div class="font-bold text-gray-800 text-sm">{{ $period->label }}</div>
                                                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</div>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if($schedule)
                                                                        @php
                                                                            $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                                            $subject = $subjects[$schedule->subject_id] ?? null;
                                                                        @endphp
                                                                        <span class="font-bold text-blue-700">
                                                                            {{ $subject->name ?? '-' }}
                                                                            @if($schedule->subject_id_2)
                                                                                <span class="text-pink-600">+ {{ ($subjects[$schedule->subject_id_2] ?? null)?->name ?? '' }}</span>
                                                                            @endif
                                                                        </span>
                                                                        <span class="text-gray-400 mx-1">|</span>
                                                                        <span class="text-sm text-gray-600">
                                                                            {{ $teacher->name ?? '-' }}
                                                                            @if($schedule->is_divided && isset($dividedTeachers[$schedule->id]))
                                                                                @php
                                                                                    $teacher2 = collect($teachers)->firstWhere('id', $dividedTeachers[$schedule->id]);
                                                                                @endphp
                                                                                @if($teacher2)
                                                                                    <span class="text-purple-500 mx-1">|</span>
                                                                                    <span class="text-purple-600">{{ $teacher2->name }}</span>
                                                                                @endif
                                                                            @endif
                                                                        </span>
                                                                    @else
                                                                        <span class="text-gray-300 text-sm hover:text-blue-500">+ Click to Assign</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if($schedule && $schedule->room)
                                                                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">{{ $schedule->room }}</span>
                                                                    @else
                                                                        <span class="text-gray-300 text-xs">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="bg-white rounded-2xl p-8 text-center text-gray-400">
                                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <p class="font-medium">Select a class from the dropdown above</p>
                                            <p class="text-sm">to view and edit its schedule.</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Assignment Modal (Existing) --}}
    @if($showModal)
    {{-- ... (existing assignment modal code) ... --}}
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Assign Period</h2>
                        <div class="text-sm text-gray-500 mb-6">
                            @if($viewMode === 'teacher')
                                <span class="font-bold text-gray-800">{{ $teachers->firstWhere('id', $modalTeacherId)?->name }}</span> •
                            @elseif($viewMode === 'room')
                                <span class="font-bold text-gray-800">Room: {{ $room }}</span> •
                            @else
                                <span class="font-bold text-gray-800">{{ $classes->firstWhere('id', $modalClassId)?->name }}</span> •
                            @endif 
                            @if($selectedDay === 'Everyday')
                                <span class="text-green-600 font-medium">All Days</span>
                            @else
                                {{ $selectedDay }}
                            @endif
                            • {{ $modalPeriodLabel }}
                        </div>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Main Assignment --}}
                    @if($viewMode === 'class')
                    <div>
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-sm font-medium text-gray-700">Teacher</label>
                            <label class="flex items-center gap-1.5 cursor-pointer hover:bg-blue-50 px-2 py-0.5 rounded transition">
                                <input type="checkbox" wire:model.live="showAllTeachers" class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-xs text-blue-600 font-medium select-none">Show All</span>
                            </label>
                        </div>
                        @error('selectedTeacherId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror

                        @if($selectedTeacherId)
                            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-blue-300 rounded-xl cursor-pointer" @click="open = !open">
                                 @php $t = collect($teachers)->firstWhere('id', $selectedTeacherId); @endphp
                                 <span class="font-medium text-gray-800 flex-1">{{ $t->name ?? 'Unknown Teacher' }}</span>
                                 <button wire:click.stop="$set('selectedTeacherId', null); $set('searchTeacherInput', '')" class="text-gray-400 hover:text-red-500">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                 </button>
                            </div>
                        @else
                            <div class="relative">
                                <input 
                                    type="text" 
                                    wire:model.live="searchTeacherInput" 
                                    @focus="open = true"
                                    @click="open = true"
                                    placeholder="Search teacher..." 
                                    class="w-full px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none"
                                />
                                <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                     <svg class="w-5 h-5" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                        @endif
                        
                        <div x-show="open" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto" x-transition>
                            @php
                                $list = collect($availableTeachers);
                                if (!empty($searchTeacherInput)) {
                                    $list = $list->filter(fn($t) => str_contains(strtolower($t->name), strtolower($searchTeacherInput)));
                                }
                            @endphp
                            @foreach($list as $t)
                                <div wire:click="$set('selectedTeacherId', {{ $t->id }}); open = false" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                    {{ $t->name }}
                                </div>
                            @endforeach
                            @if($list->isEmpty())
                                 <div class="px-4 py-2 text-gray-400 text-sm text-center">No available teachers found</div>
                            @endif
                        </div>
                        
                        {{-- Conflict Warning --}}
                        @if($showConflictWarning && $conflictDetails)
                            <div class="mt-2 text-xs bg-red-50 text-red-600 p-2 rounded-lg border border-red-100 flex items-start gap-2 animate-pulse">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <span class="font-bold">Conflict:</span> {{ $conflictDetails }}
                                    <div class="mt-1">
                                         <button wire:click="$toggle('confirmedConflict'); $set('showConflictWarning', false)" class="text-red-700 underline font-bold hover:text-red-900">
                                            Ignore & Assign
                                         </button>
                                    </div>
                                </div>
                            </div>
                        @else
                           <p class="text-xs text-gray-400 mt-1">Only shows teachers not busy this period (unless Show All is checked)</p>
                        @endif
                    </div>
                    @else
                    {{-- Searchable Class Selector --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        @error('modalClassId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror
                        
                        @if($modalClassId)
                            {{-- Selected State --}}
                            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-green-300 rounded-xl">
                                 <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                    {{ substr($classes->firstWhere('id', $modalClassId)?->name ?? 'C', 0, 1) }}
                                 </span>
                                 <span class="font-medium text-gray-800 flex-1">{{ $classes->firstWhere('id', $modalClassId)?->name }}</span>
                                 <button wire:click="$set('modalClassId', null); $set('searchClass', '')" class="text-gray-400 hover:text-red-500 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                 </button>
                            </div>
                        @else
                             {{-- Input --}}
                             <div class="relative">
                                <input 
                                    type="text" 
                                    wire:model.live="searchClass" 
                                    @focus="open = true"
                                    @click="open = true"
                                    placeholder="Type to search available class..." 
                                    class="w-full px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none"
                                    autocomplete="off"
                                />
                                <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                             </div>

                             {{-- Dropdown --}}
                             <div 
                                x-show="open" 
                                x-transition
                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"
                            >
                                @php
                                    $searchTerm = $searchClass ?? '';
                                    $list = collect($availableClasses);
                                    if (!empty($searchTerm)) {
                                        $list = $list->filter(fn($c) => str_contains(strtolower($c->name), strtolower($searchTerm)));
                                    }
                                @endphp

                                @if($list->isEmpty())
                                     <div class="px-4 py-3 text-gray-400 text-sm text-center">No available classes found</div>
                                @else
                                    @foreach($list as $c)
                                        <div 
                                            wire:click="selectClass({{ $c->id }})"
                                            @click="open = false" 
                                            class="flex items-center gap-3 px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                                        >
                                            <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm">
                                                {{ substr($c->name, 0, 1) }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-700">{{ $c->name }}</span>
                                        </div>
                                    @endforeach
                                @endif
                             </div>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">Only shows classes not assigned this period</p>
                    </div>

                    {{-- Teacher Selector for Room Mode --}}
                    @if($viewMode === 'room')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                        @error('selectedTeacherId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror
                        <select wire:model.live="selectedTeacherId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select Teacher</option>
                            @foreach($availableTeachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Only shows teachers not busy this period</p>
                    </div>
                    @endif
                    @endif

                    <div>
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        @error('selectedSubjectId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror
                        @if($selectedSubjectId)
                            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl cursor-pointer" @click="open = !open">
                                 @php $s = collect($availableSubjects)->firstWhere('id', $selectedSubjectId); @endphp
                                 <span class="font-medium text-gray-800 flex-1">{{ $s->name ?? 'Unknown Subject' }}</span>
                                 <button wire:click.stop="$set('selectedSubjectId', null); $set('searchSubjectInput', '')" class="text-gray-400 hover:text-red-500">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                 </button>
                            </div>
                        @else
                            <div class="relative">
                                <input 
                                    type="text" 
                                    wire:model.live="searchSubjectInput" 
                                    @focus="open = true"
                                    @click="open = true"
                                    placeholder="Search subject..." 
                                    class="w-full px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none"
                                />
                                <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                     <svg class="w-5 h-5" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                        @endif
                        
                        <div x-show="open" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto" x-transition>
                            @php
                                $list = collect($availableSubjects);
                                if (!empty($searchSubjectInput)) {
                                    $list = $list->filter(fn($s) => str_contains(strtolower($s->name), strtolower($searchSubjectInput)));
                                }
                            @endphp
                            @foreach($list as $s)
                                <div wire:click="$set('selectedSubjectId', {{ $s->id }}); open = false" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                    {{ $s->name }}
                                </div>
                            @endforeach
                            @if($list->isEmpty())
                                 <div class="px-4 py-2 text-gray-400 text-sm text-center">No subjects found</div>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Excludes subjects already assigned today</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <input type="text" wire:model="room" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Room/Lab" />
                    </div>

                    {{-- Apply to All Days --}}
                    @if($selectedDay === 'Everyday')
                        <div class="bg-green-50 p-3 rounded-xl">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm font-medium text-green-700">Everyday Mode Active</span>
                            </div>
                            <p class="text-xs text-green-600 ml-7">This assignment will be applied to all days ({{ implode(', ', $days) }})</p>
                        </div>
                    @else
                        <div class="bg-blue-50 p-3 rounded-xl">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="applyToAllDays" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                <span class="text-sm font-medium text-blue-700">Apply to all days</span>
                            </label>
                            <p class="text-xs text-blue-600 ml-6">{{ $applyToAllDays ? 'Will assign to: ' . implode(', ', $days) : 'Assign this schedule to everyday (Mon-Fri' . ($includeSaturday ? '+Sat' : '') . ')' }}</p>
                        </div>
                    @endif

                    {{-- Double Subject Mode --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isDoubleSubject" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500" />
                            <span class="text-sm font-medium text-gray-700">Double Subject (Same Teacher)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">Assign two subjects (e.g. Isl + Pak Study) to this period</p>
                    </div>

                    @if($isDoubleSubject)
                        <div class="bg-pink-50 p-4 rounded-xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-pink-700 mb-1">Second Subject</label>
                                <select wire:model="selectedSubjectId2" class="w-full px-4 py-2 rounded-xl border border-pink-200 focus:ring-2 focus:ring-pink-500 outline-none bg-white">
                                    <option value="">Select Second Subject</option>
                                    @foreach($availableSubjects as $subject)
                                        @if($subject->id != $selectedSubjectId)
                                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Divided Class --}}
                    @if(!$isDoubleSubject)
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isDivided" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-700">Divided Class (2 Teachers)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">For split groups like Bio/Computer students</p>
                    </div>

                    @if($isDivided)
                        <div class="bg-purple-50 p-4 rounded-xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-purple-700 mb-1">Teacher 2</label>
                                <select wire:model="selectedTeacherId2" class="w-full px-4 py-2 rounded-xl border border-purple-200 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                                    <option value="">Select Second Teacher</option>
                                    @foreach($availableTeachers as $teacher)
                                        @if($teacher->id != $selectedTeacherId)
                                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-purple-700 mb-1">Subject 2</label>
                                <select wire:model="selectedSubjectId2" class="w-full px-4 py-2 rounded-xl border border-purple-200 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                                    <option value="">Select Second Subject</option>
                                    @foreach($availableSubjects2 as $subject)
                                        @if($subject->id != $selectedSubjectId)
                                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    @endif

                    {{-- Substitute --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isSubstitute" class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500" />
                            <span class="text-sm font-medium text-gray-700">Add Substitute (Single Day)</span>
                        </label>
                    </div>

                    @if($isSubstitute)
                        <div class="bg-orange-50 p-4 rounded-xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-orange-700 mb-1">Date</label>
                                @error('substituteDate') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror
                                <input type="date" wire:model.live="substituteDate" class="w-full px-4 py-2 rounded-xl border border-orange-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white">
                            </div>

                            @if($viewMode === 'teacher')
                                {{-- Teacher View: Select Absent Teacher --}}
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <label class="block text-sm font-medium text-orange-700 mb-1">Select Absent Teacher</label>
                                    @error('substituteTeacherId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror
                                    
                                    @if($substituteTargetTeacherId)
                                        <div class="flex items-center gap-2 px-4 py-2 bg-white border border-orange-300 rounded-xl">
                                            <span class="font-medium text-gray-800 flex-1">{{ $teachers->firstWhere('id', $substituteTargetTeacherId)?->name }}</span>
                                            <button wire:click="selectSubstituteTargetTeacher(null)" class="text-gray-400 hover:text-red-500 transition">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @else
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                wire:model.live="searchSubstituteTeacher" 
                                                @focus="open = true"
                                                @click="open = true"
                                                placeholder="Search absent teacher..." 
                                                class="w-full px-4 py-2 pr-10 rounded-xl border border-orange-200 focus:ring-2 focus:ring-orange-500 outline-none"
                                                autocomplete="off"
                                            />
                                            {{-- Dropdown Toggle --}}
                                            <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </div>

                                         <div 
                                                x-show="open" 
                                                x-transition
                                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"
                                            >
                                                @php
                                                    $searchTerm = $searchSubstituteTeacher ?? '';
                                                    $list = collect($teachers)->filter(fn($t) => $t->id != $modalTeacherId);
                                                    if (!empty($searchTerm)) {
                                                        $list = $list->filter(fn($t) => str_contains(strtolower($t->name), strtolower($searchTerm)));
                                                    }
                                                @endphp
                                                @foreach($list as $t)
                                                    <div 
                                                        wire:click="selectSubstituteTargetTeacher({{ $t->id }})"
                                                        @click="open = false" 
                                                        class="px-4 py-2 hover:bg-orange-50 cursor-pointer text-sm border-b border-gray-100 last:border-0"
                                                    >
                                                        {{ $t->name }}
                                                    </div>
                                                @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Target Schedule Display --}}
                                @if($substituteTargetSchedule)
                                     <div class="mt-2 p-3 bg-white rounded-lg border border-orange-200">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs text-orange-600 font-bold uppercase">Class to Cover</p>
                                        </div>
                                        <div class="flex items-center justify-between">
                                             <div>
                                                 <span class="font-bold text-gray-800">{{ $classes->firstWhere('id', $substituteTargetSchedule->class_id)->name ?? 'Unknown Class' }}</span>
                                                 <span class="text-gray-400 mx-2">|</span>
                                                 <span class="text-gray-600">{{ $subjects[$substituteTargetSchedule->subject_id]->name ?? 'Unknown Subject' }}</span>
                                             </div>
                                             <div class="text-xs bg-gray-100 px-2 py-1 rounded">
                                                 {{ $substituteTargetSchedule->room ?? 'No Room' }}
                                             </div>
                                        </div>
                                     </div>
                                @elseif($substituteTargetTeacherId)
                                    <p class="text-xs text-red-500 mt-2">No class found for this teacher at this time.</p>
                                @endif

                            @else
                                {{-- Standard View --}}
                                <div>
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-sm font-medium text-orange-700">Substitute Teacher</label>
                                            <label class="flex items-center gap-1.5 cursor-pointer hover:bg-orange-50 px-2 py-0.5 rounded transition">
                                                <input type="checkbox" wire:model.live="showAllSubstituteTeachers" class="w-3.5 h-3.5 rounded text-orange-600 focus:ring-orange-500 border-gray-300">
                                                <span class="text-xs text-orange-600 font-medium select-none">Show All</span>
                                            </label>
                                        </div>
                                        @error('substituteTeacherId') <span class="text-xs text-red-500 block mb-1">{{ $message }}</span> @enderror

                                        @if($substituteTeacherId)
                                            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-orange-300 rounded-xl cursor-pointer shadow-sm" @click="open = !open">
                                                 @php $t = collect($availableSubstituteTeachers)->firstWhere('id', $substituteTeacherId); @endphp
                                                 <span class="font-medium text-gray-800 flex-1">{{ $t->name ?? 'Unknown Teacher' }}</span>
                                                 <button wire:click.stop="$set('substituteTeacherId', null); $set('searchSubInput', '')" class="text-gray-400 hover:text-red-500 transition">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                 </button>
                                            </div>
                                        @else
                                            <div class="relative">
                                                <input 
                                                    type="text" 
                                                    wire:model.live="searchSubInput" 
                                                    @focus="open = true"
                                                    @click="open = true"
                                                    placeholder="Search teacher..." 
                                                    class="w-full px-4 py-2 pr-10 rounded-xl border border-orange-200 focus:ring-2 focus:ring-orange-500 outline-none shadow-sm"
                                                />
                                                <button @click="open = !open" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                                     <svg class="w-5 h-5" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                        
                                        <div x-show="open" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto" x-transition>
                                            @php
                                                $list = collect($availableSubstituteTeachers);
                                                if (!empty($searchSubInput)) {
                                                    $list = $list->filter(fn($t) => str_contains(strtolower($t->name), strtolower($searchSubInput)));
                                                }
                                            @endphp
                                            @foreach($list as $t)
                                                <div wire:click="$set('substituteTeacherId', {{ $t->id }}); open = false" class="px-4 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-50 last:border-0">
                                                    {{ $t->name }}
                                                </div>
                                            @endforeach
                                            @if($list->isEmpty())
                                                 <div class="px-4 py-2 text-gray-400 text-sm text-center">No teachers found</div>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-xs text-orange-600 mt-1">Only shows teachers free this period</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    @if($showConflictWarning)
                        <div class="bg-red-50 p-4 rounded-xl border border-red-200 mb-4 animate-fade-in-up">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-red-800">Conflict Detected</h4>
                                    <p class="text-xs text-red-600 mt-1">{{ $conflictDetails ?? 'Teacher occupied.' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3">
                            <button wire:click="confirmConflictAssignment" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium shadow-sm transition-transform active:scale-95">
                                Assign Anyway
                            </button>
                            <button wire:click="cancelConflictAssignment" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 font-medium transition-colors">
                                Cancel
                            </button>
                        </div>
                    @else
                        <div class="flex gap-3">
                            <button wire:click="save" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium shadow-sm transition-transform active:scale-95">
                                {{ $editingId ? 'Update' : 'Assign' }}
                            </button>
                            @if($editingId)
                                <button wire:click="delete" wire:confirm="Remove this assignment?" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium shadow-sm">
                                    Delete
                                </button>
                            @endif
                            <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium transition-colors">
                                Cancel
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    



</div>
