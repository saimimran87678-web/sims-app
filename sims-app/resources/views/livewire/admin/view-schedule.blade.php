<div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center print:hidden">
        <div class="flex items-start gap-4">
            <x-schedule-menu />
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Schedule View</h1>
                <p class="text-gray-500">Read-only schedule overview</p>
            </div>
        </div>
        <div class="flex gap-3 items-center">
            @if($viewType === 'teacher')
                <div class="flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-xl">
                    <span class="text-xs font-semibold text-gray-500">Cards/Page:</span>
                    <select wire:model.live="cardsPerPage" class="text-xs bg-transparent border-none focus:ring-0 p-0 cursor-pointer text-gray-700 font-bold">
                        <option value="2">2</option>
                        <option value="4">4</option>
                        <option value="6">6</option>
                        <option value="8">8</option>
                        <option value="10">10</option>
                    </select>
                </div>
            @endif

            <a href="{{ route('admin.print-schedule', ['day' => $selectedDay, 'viewType' => $viewType, 'cardsPerPage' => $cardsPerPage]) }}" target="_blank" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Schedule
            </a>
        </div>
    </div>

    {{-- View Type Tabs --}}
    <div class="flex flex-wrap gap-4 items-center print:hidden">
        <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
            <button wire:click="$set('viewType', 'class')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $viewType === 'class' ? 'bg-white shadow text-blue-600' : 'text-gray-600 hover:bg-gray-200' }}">
                By Class
            </button>
            <button wire:click="$set('viewType', 'teacher')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $viewType === 'teacher' ? 'bg-white shadow text-blue-600' : 'text-gray-600 hover:bg-gray-200' }}">
                By Teacher
            </button>
            <button wire:click="$set('viewType', 'room')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $viewType === 'room' ? 'bg-white shadow text-blue-600' : 'text-gray-600 hover:bg-gray-200' }}">
                By Room
            </button>
            <button wire:click="$set('viewType', 'summary')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $viewType === 'summary' ? 'bg-white shadow text-blue-600' : 'text-gray-600 hover:bg-gray-200' }}">
                Summary
            </button>
        </div>

        @if($viewType !== 'summary')
        <div class="flex gap-2 items-center">
            <button
                wire:click="$set('selectedDay', 'Everyday')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $selectedDay === 'Everyday' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
            >
                Everyday
            </button>
            <span class="text-gray-300">|</span>
            @foreach($days as $day)
                <button
                    wire:click="$set('selectedDay', '{{ $day }}')"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $selectedDay === $day ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ substr($day, 0, 3) }}
                </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Print Header Removed (Handled by dedicated page) --}}

    {{-- BY CLASS VIEW --}}
    @if($viewType === 'class')
    {{-- Screen View --}}
    <div class="glass-card rounded-2xl overflow-hidden print:hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-32 sticky left-0 bg-gray-50">Class</th>
                        @foreach($periods as $period)
                            <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[140px]">
                                <div class="font-bold text-gray-700">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($classes as $class)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2 text-sm font-bold text-gray-800 sticky left-0 bg-white">{{ $class->name }}</td>
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-2 py-2 bg-yellow-50/50 text-center">
                                        <span class="text-yellow-600 text-xs">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-2 py-2 bg-purple-50/50 text-center">
                                        <span class="text-purple-600 text-xs">Assembly</span>
                                    </td>
                                @else
                                    @php $schedules = $this->getScheduleByClass($class->id, $period->period_no); @endphp
                                    <td 
                                        wire:click="viewDetail({{ $class->id }}, {{ $period->period_no }})"
                                        class="px-2 py-2 border-l border-gray-100 text-center {{ $schedules->isNotEmpty() ? 'cursor-pointer hover:bg-blue-50' : '' }}"
                                    >
                                        @if($schedules->isNotEmpty())
                                            <div class="flex flex-col gap-1">
                                                @foreach($schedules as $schedule)
                                                    @php
                                                        $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                        $subject = \App\Models\Subject::find($schedule->subject_id);
                                                    @endphp
                                                    <div class="text-xs {{ $loop->index > 0 ? 'border-t border-gray-200 pt-1' : '' }}">
                                                        <div class="font-semibold text-blue-700">{{ $subject->name ?? '-' }}</div>
                                                        <div class="text-gray-500">{{ $teacher->name ?? '-' }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-xs">-</span>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Print Layout for Class View Removed (Handled by dedicated page) --}}
    @endif

    {{-- BY TEACHER VIEW --}}
    @if($viewType === 'teacher')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($teachers as $teacher)
        <div class="glass-card rounded-2xl overflow-hidden p-4">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">
                        {{ substr($teacher->name, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $teacher->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $selectedDay === 'Everyday' ? 'All Days' : $selectedDay }} Schedule</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-32">Period</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Assignment</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($periods->where('is_break', false)->where('is_assembly', false) as $period)
                            @php $schedules = $this->getSchedulesByTeacher($teacher->id, $period->period_no); @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-800 text-sm">{{ $period->label }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($schedules->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                        @foreach($schedules as $schedule)
                                            @php
                                                $class = collect($classes)->firstWhere('id', $schedule->class_id);
                                                $subject = \App\Models\Subject::find($schedule->subject_id);
                                            @endphp
                                            <div 
                                                wire:click="viewDetail({{ $schedule->class_id }}, {{ $period->period_no }})"
                                                class="flex items-center gap-2 cursor-pointer hover:bg-blue-50 px-3 py-1.5 rounded-lg border border-gray-100 hover:border-blue-100 transition-all group"
                                            >
                                                <span class="font-bold text-green-700 text-sm group-hover:text-blue-700">{{ $class->name ?? '-' }}</span>
                                                <span class="text-gray-300">|</span>
                                                <span class="text-xs text-gray-600 group-hover:text-blue-600 font-medium">{{ $subject->name ?? '-' }}</span>
                                            </div>
                                        @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-300 text-xs italic">Free</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- BY ROOM VIEW --}}
    @if($viewType === 'room')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($rooms as $room)
        <div class="glass-card rounded-2xl overflow-hidden p-4">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
                        {{ substr($room, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $room }}</h3>
                        <p class="text-xs text-gray-500">{{ $selectedDay === 'Everyday' ? 'All Days' : $selectedDay }} Schedule</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase w-32">Period</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Class & Teacher</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($periods->where('is_break', false)->where('is_assembly', false) as $period)
                            {{-- Filter for this room and period --}}
                            @php 
                                $entry = $timetables->first(function ($t) use ($room, $period) {
                                    return $t->room === $room && $t->period_no === $period->period_no;
                                });
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-800 text-sm">{{ $period->label }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($entry)
                                        @php
                                            $class = collect($classes)->firstWhere('id', $entry->class_id);
                                            $subject = \App\Models\Subject::find($entry->subject_id);
                                            $teacher = collect($teachers)->firstWhere('id', $entry->teacher_id);
                                        @endphp
                                        <div 
                                            wire:click="viewDetail({{ $entry->class_id }}, {{ $period->period_no }})"
                                            class="flex items-center gap-2 cursor-pointer hover:bg-blue-50 px-3 py-1.5 rounded-lg border border-gray-100 hover:border-blue-100 transition-all group"
                                        >
                                            <span class="font-bold text-green-700 text-sm group-hover:text-blue-700">{{ $class->name ?? '-' }}</span>
                                            <span class="text-gray-300">|</span>
                                            <span class="text-xs text-gray-600 group-hover:text-blue-600 font-medium">{{ $subject->name ?? '-' }}</span>
                                            <span class="text-gray-300">|</span>
                                            <span class="text-xs text-xs text-gray-500">{{ $teacher->name ?? '-' }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-300 text-xs italic">Free</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No Rooms Found</h3>
            <p class="text-gray-500 mt-1">Schedules with assigned rooms will appear here.</p>
        </div>
        @endforelse
    </div>
    @endif
    
    {{-- SUMMARY VIEW --}}
    @if($viewType === 'summary')
    <div class="glass-card rounded-2xl overflow-hidden p-6 print:hidden">
        <h2 class="text-lg font-bold text-gray-800 mb-4">{{ $selectedDay }} Schedule Summary</h2>
        
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->getScheduleSummary() as $item)
                <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-800">{{ $item['class'] }}</div>
                        <div class="text-sm text-gray-500">{{ $item['assigned'] }} / {{ $item['total'] }} periods</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold {{ $item['percentage'] == 100 ? 'text-green-600' : ($item['percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $item['percentage'] }}%
                        </div>
                        <div class="text-xs text-gray-400">Assigned</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Overall Stats --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-blue-50 rounded-xl p-4">
                    <div class="text-3xl font-bold text-blue-600">{{ $timetables->count() }}</div>
                    <div class="text-sm text-gray-600">Total Assignments</div>
                </div>
                <div class="bg-green-50 rounded-xl p-4">
                    <div class="text-3xl font-bold text-green-600">{{ $timetables->pluck('teacher_id')->unique()->count() }}</div>
                    <div class="text-sm text-gray-600">Teachers Active</div>
                </div>
                <div class="bg-purple-50 rounded-xl p-4">
                    <div class="text-3xl font-bold text-purple-600">{{ $classes->count() }}</div>
                    <div class="text-sm text-gray-600">Classes</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Print Layout for Summary View Removed (Handled by dedicated page) --}}
    @endif

    {{-- Detail Modal (like assignment modal but read-only) --}}
    @if($showDetailModal && $detailData)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Assignment Details</h2>
                        <p class="text-sm text-gray-500">{{ $detailData['class_name'] }} • {{ $selectedDay }} • {{ $detailData['period_label'] }}</p>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Time</label>
                        <div class="text-lg font-semibold text-gray-800">{{ $detailData['period_time'] }}</div>
                    </div>

                    @foreach($detailData['entries'] as $entry)
                        <div class="mt-4 space-y-4 {{ $loop->index > 0 ? 'border-t border-gray-200 pt-4' : '' }}">
                            <div class="bg-blue-50 rounded-xl p-4">
                                <label class="block text-xs font-medium text-blue-600 uppercase mb-1">Teacher {{ $loop->count > 1 ? ($loop->index + 1) : '' }}</label>
                                <div class="text-lg font-semibold text-gray-800">{{ $entry['teacher_name'] }}</div>
                            </div>

                            <div class="bg-green-50 rounded-xl p-4">
                                <label class="block text-xs font-medium text-green-600 uppercase mb-1">Subject {{ $loop->count > 1 ? ($loop->index + 1) : '' }}</label>
                                <div class="text-lg font-semibold text-gray-800">{{ $entry['subject_name'] }}</div>
                            </div>

                            <div class="bg-orange-50 rounded-xl p-4">
                                <label class="block text-xs font-medium text-orange-600 uppercase mb-1">Room {{ $loop->count > 1 ? ($loop->index + 1) : '' }}</label>
                                <div class="text-lg font-semibold text-gray-800">{{ $entry['room'] }}</div>
                            </div>

                            @if($entry['is_divided'])
                                <div class="bg-purple-50 rounded-xl p-4 text-center">
                                    <span class="text-purple-700 font-semibold">🔀 Divided Class</span>
                                    <p class="text-xs text-purple-600 mt-1">Multiple teachers assigned to this period</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button wire:click="closeDetailModal" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        @media print {
            body { background: white !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
            table { font-size: 10px; }
            th, td { padding: 4px 6px !important; }
        }
    </style>
</div>

