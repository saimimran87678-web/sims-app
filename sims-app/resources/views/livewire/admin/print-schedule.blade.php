<div class="p-8 max-w-[297mm] mx-auto bg-white">
    <style>
        @media print {
            body { background: white !important; -webkit-print-color-adjust: exact; }
            .print-card { background: white !important; }
        }
        body { background: white !important; }
    </style>

    {{-- TEACHER VIEW (Individual Cards) --}}
    @if($viewType === 'teacher')
        <div class="grid grid-cols-2 gap-6">
            @foreach($teachers as $teacher)
                <div class="break-inside-avoid border border-gray-400 rounded-lg p-4 h-fit bg-white print-card">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-300 pb-2">
                        <div class="flex flex-col">
                            <h3 class="font-bold text-lg text-black">{{ $teacher->name }}</h3>
                            <p class="text-xs text-gray-600">{{ $day === 'Everyday' ? 'All Days' : $day }} Schedule</p>
                        </div>
                        <div class="text-right">
                             <span class="text-xs font-semibold bg-gray-100 px-2 py-1 rounded border border-gray-300"> Teacher Schedule </span>
                        </div>
                    </div>
                    
                    <table class="w-full text-xs text-left border-collapse border border-gray-400">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-400 px-2 py-1 font-bold text-black uppercase w-20">Period</th>
                                <th class="border border-gray-400 px-2 py-1 font-bold text-black uppercase">Assignment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($periods->where('is_break', false)->where('is_assembly', false) as $period)
                                @php $schedules = $this->getSchedulesByTeacher($teacher->id, $period->period_no); @endphp
                                <tr>
                                    <td class="border border-gray-400 px-2 py-1 font-semibold text-gray-800 align-top bg-gray-50 whitespace-nowrap">
                                        {{ $period->label }}<br>
                                        <span class="text-[9px] font-normal text-gray-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }}</span>
                                    </td>
                                    <td class="border border-gray-400 px-2 py-1 align-middle h-8">
                                        @if($schedules->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                            @foreach($schedules as $schedule)
                                                @php
                                                    $class = collect($classes)->firstWhere('id', $schedule->class_id);
                                                    $subject = \App\Models\Subject::find($schedule->subject_id);
                                                @endphp
                                                <div class="flex items-center gap-1 border border-black px-1.5 py-0.5 rounded text-[10px]">
                                                    <span class="font-bold text-black">{{ $class->name ?? '-' }}</span>
                                                    <span class="text-gray-400">|</span>
                                                    <span class="text-black">{{ $subject->name ?? '-' }}</span>
                                                </div>
                                            @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic text-[10px]">Free</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Page break based on user selection --}}
                @if(($loop->index + 1) % $cardsPerPage == 0 && !$loop->last)
                    <div class="col-span-2 page-break"></div>
                    {{-- Header for next page --}}
                    {{-- Header removed as per request --}}
                @endif
            @endforeach
        </div>

    {{-- CLASS VIEW (Master Table) --}}
    @elseif($viewType === 'class')
        <table class="w-full text-xs text-left border-collapse border border-black">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-2 py-1.5 font-bold w-24">Class</th>
                    @foreach($periods as $period)
                        <th class="border border-black px-2 py-1.5 font-bold text-center">
                            {{ $period->label }}<br>
                            <span class="text-[9px] font-normal text-gray-600">{{ \Carbon\Carbon::parse($period->start_time)->format('H:i') }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($classes as $class)
                    <tr>
                        <td class="border border-black px-2 py-1.5 font-bold">{{ $class->name }}</td>
                        @foreach($periods as $period)
                            @if($period->is_break)
                                <td class="border border-black bg-gray-100 text-center px-1 py-1">
                                    <span class="text-[9px] font-semibold text-gray-600">BREAK</span>
                                </td>
                            @elseif($period->is_assembly)
                                <td class="border border-black bg-gray-100 text-center px-1 py-1">
                                    <span class="text-[9px] font-semibold text-gray-600">ASSEMBLY</span>
                                </td>
                            @else
                                @php $schedules = $this->getScheduleByClass($class->id, $period->period_no); @endphp
                                <td class="border border-black px-1 py-1 text-center h-10 align-middle">
                                    @if($schedules->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($schedules as $schedule)
                                                @php
                                                    $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                    $subject = \App\Models\Subject::find($schedule->subject_id);
                                                @endphp
                                                <div class="{{ $loop->index > 0 ? 'border-t border-gray-300 pt-0.5' : '' }}">
                                                    <div class="font-bold text-[10px] text-black">{{ $subject->name ?? '-' }}</div>
                                                    <div class="text-[9px] text-gray-700">{{ $teacher->name ?? '-' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- SUMMARY VIEW (Stats Table) --}}
    @elseif($viewType === 'summary')
        <table class="w-full text-sm text-left border-collapse border border-black mb-8">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-3 py-2 font-bold">Class</th>
                    <th class="border border-black px-3 py-2 text-center font-bold">Assigned / Total</th>
                    <th class="border border-black px-3 py-2 text-center font-bold">Completion %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->getScheduleSummary() as $item)
                    <tr>
                        <td class="border border-black px-3 py-2 font-bold">{{ $item['class'] }}</td>
                        <td class="border border-black px-3 py-2 text-center">{{ $item['assigned'] }} / {{ $item['total'] }}</td>
                        <td class="border border-black px-3 py-2 text-center font-bold {{ $item['percentage'] == 100 ? 'text-black' : 'text-gray-600' }}">
                            {{ $item['percentage'] }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="grid grid-cols-3 gap-8 text-center border-t border-black pt-4">
            <div>
                <div class="text-2xl font-bold">{{ $timetables->count() }}</div>
                <div class="text-sm text-gray-600 font-semibold">Total Assignments</div>
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $timetables->pluck('teacher_id')->unique()->count() }}</div>
                <div class="text-sm text-gray-600 font-semibold">Teachers Active</div>
            </div>
            <div>
                <div class="text-2xl font-bold">{{ $classes->count() }}</div>
                <div class="text-sm text-gray-600 font-semibold">Classes</div>
            </div>
        </div>
    @endif
</div>
