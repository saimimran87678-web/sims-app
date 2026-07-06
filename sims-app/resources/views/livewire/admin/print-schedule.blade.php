<div class="p-8 max-w-[297mm] mx-auto bg-white" style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;">
    <style>
        @media print {
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-card { background: white !important; border: 1px solid #cbd5e1 !important; }
            .page-break { page-break-before: always; }
        }
        body { background: white !important; }
    </style>

    {{-- Dynamic Institute Header --}}
    <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #cbd5e1; padding-bottom: 20px; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;">
        @php
            $logoPath = \App\Models\Setting::getGlobal('institute_logo');
            $formalName = \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS'));
            $address = \App\Models\Setting::getGlobal('institute_address');
        @endphp
        @if($logoPath)
            <div style="margin-bottom: 12px;">
                <img src="{{ '/' . $logoPath }}" style="height: 55px; max-width: 150px; object-fit: contain; margin: 0 auto;">
            </div>
        @endif
        <div style="font-size: 24px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">{{ $formalName }}</div>
        @if($address)
            <div style="font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500;">{{ $address }}</div>
        @endif
        <div style="font-size: 14px; font-weight: 700; color: #1e3a8a; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 12px;">
            @if($viewType === 'teacher')
                Teacher Timetable Schedule Cards ({{ $day === 'Everyday' ? 'All Days' : $day }})
            @elseif($viewType === 'class')
                Class Timetable Master Schedule ({{ $day === 'Everyday' ? 'All Days' : $day }})
            @elseif($viewType === 'room')
                Room Timetable Schedule Cards ({{ $day === 'Everyday' ? 'All Days' : $day }})
            @else
                Timetable Schedule Summary ({{ $day === 'Everyday' ? 'All Days' : $day }})
            @endif
        </div>
    </div>

    {{-- TEACHER VIEW (Individual Cards) --}}
    @if($viewType === 'teacher')
        <div class="grid grid-cols-2 gap-6">
            @foreach($teachers as $teacher)
                <div class="break-inside-avoid border border-slate-300 rounded-lg p-4 h-fit bg-white print-card">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-200 pb-2">
                        <div class="flex flex-col">
                            <h3 class="font-bold text-base text-slate-800">{{ $teacher->name }}</h3>
                            <p class="text-[11px] text-slate-500">{{ $day === 'Everyday' ? 'All Days' : $day }} Schedule</p>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] font-semibold bg-slate-50 text-slate-700 px-2 py-0.5 rounded border border-slate-200"> Teacher Schedule </span>
                        </div>
                    </div>
                    
                    <table class="w-full text-xs text-left border-collapse border border-slate-300">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-slate-300 px-2 py-1 font-bold text-slate-700 uppercase w-20">Period</th>
                                <th class="border border-slate-300 px-2 py-1 font-bold text-slate-700 uppercase">Assignment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($periods->where('is_break', false)->where('is_assembly', false) as $period)
                                @php $schedules = $this->getSchedulesByTeacher($teacher->id, $period->period_no); @endphp
                                <tr>
                                    <td class="border border-slate-300 px-2 py-1 font-semibold text-slate-700 align-top bg-slate-50 whitespace-nowrap">
                                        {{ $period->label }}<br>
                                        <span class="text-[9px] font-normal text-slate-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }}</span>
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1 align-middle h-8">
                                        @if($schedules->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                            @foreach($schedules as $schedule)
                                                @php
                                                    $class = collect($classes)->firstWhere('id', $schedule->class_id);
                                                    $subject = \App\Models\Subject::find($schedule->subject_id);
                                                @endphp
                                                <div class="flex items-center gap-1 border border-slate-300 px-1.5 py-0.5 rounded text-[10px] bg-slate-50">
                                                    <span class="font-bold text-green-700">{{ $class->name ?? '-' }}</span>
                                                    <span class="text-slate-400">|</span>
                                                    <span class="text-slate-700 font-semibold">{{ $subject->name ?? '-' }}</span>
                                                </div>
                                            @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic text-[10px]">Free</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(($loop->index + 1) % $cardsPerPage == 0 && !$loop->last)
                    <div class="col-span-2 page-break"></div>
                @endif
            @endforeach
        </div>

    {{-- ROOM VIEW (Individual Cards) --}}
    @elseif($viewType === 'room')
        <div class="grid grid-cols-2 gap-6">
            @forelse($rooms as $room)
                <div class="break-inside-avoid border border-slate-300 rounded-lg p-4 h-fit bg-white print-card">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-200 pb-2">
                        <div class="flex flex-col">
                            <h3 class="font-bold text-base text-slate-800">{{ $room }}</h3>
                            <p class="text-[11px] text-slate-500">{{ $day === 'Everyday' ? 'All Days' : $day }} Schedule</p>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] font-semibold bg-slate-50 text-slate-700 px-2 py-0.5 rounded border border-slate-200"> Room Schedule </span>
                        </div>
                    </div>
                    
                    <table class="w-full text-xs text-left border-collapse border border-slate-300">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="border border-slate-300 px-2 py-1 font-bold text-slate-700 uppercase w-20">Period</th>
                                <th class="border border-slate-300 px-2 py-1 font-bold text-slate-700 uppercase">Class & Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($periods->where('is_break', false)->where('is_assembly', false) as $period)
                                @php 
                                    $entry = $timetables->first(function ($t) use ($room, $period) {
                                        return $t->room === $room && $t->period_no === $period->period_no;
                                    });
                                @endphp
                                <tr>
                                    <td class="border border-slate-300 px-2 py-1 font-semibold text-slate-700 align-top bg-slate-50 whitespace-nowrap">
                                        {{ $period->label }}<br>
                                        <span class="text-[9px] font-normal text-slate-500">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }}</span>
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1 align-middle h-8">
                                        @if($entry)
                                            @php
                                                $class = collect($classes)->firstWhere('id', $entry->class_id);
                                                $subject = \App\Models\Subject::find($entry->subject_id);
                                                $teacher = collect($teachers)->firstWhere('id', $entry->teacher_id);
                                            @endphp
                                            <div class="flex items-center gap-1 border border-slate-300 px-1.5 py-0.5 rounded text-[10px] bg-slate-50">
                                                <span class="font-bold text-green-700">{{ $class->name ?? '-' }}</span>
                                                <span class="text-slate-400">|</span>
                                                <span class="text-slate-800 font-semibold">{{ $subject->name ?? '-' }}</span>
                                                <span class="text-slate-400">|</span>
                                                <span class="text-slate-600">{{ $teacher->name ?? '-' }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic text-[10px]">Free</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(($loop->index + 1) % $cardsPerPage == 0 && !$loop->last)
                    <div class="col-span-2 page-break"></div>
                @endif
            @empty
                <div class="col-span-2 text-center py-8 text-slate-500">No room assignments found.</div>
            @endforelse
        </div>

    {{-- CLASS VIEW (Master Table) --}}
    @elseif($viewType === 'class')
        <table class="w-full text-xs text-left border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100">
                    <th class="border border-slate-300 px-3 py-2 font-bold w-24 text-slate-800">Class</th>
                    @foreach($periods as $period)
                        <th class="border border-slate-300 px-3 py-2 font-bold text-center text-slate-800">
                            {{ $period->label }}<br>
                            <span class="text-[9px] font-normal text-slate-500">{{ \Carbon\Carbon::parse($period->start_time)->format('H:i') }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($classes as $class)
                    <tr>
                        <td class="border border-slate-300 px-3 py-2 font-bold text-slate-800 bg-slate-50">{{ $class->name }}</td>
                        @foreach($periods as $period)
                            @if($period->is_break)
                                <td class="border border-slate-300 bg-yellow-50/50 text-center px-2 py-2">
                                    <span class="text-[10px] font-bold text-yellow-700">BREAK</span>
                                </td>
                            @elseif($period->is_assembly)
                                <td class="border border-slate-300 bg-purple-50/50 text-center px-2 py-2">
                                    <span class="text-[10px] font-bold text-purple-700">ASSEMBLY</span>
                                </td>
                            @else
                                @php $schedules = $this->getScheduleByClass($class->id, $period->period_no); @endphp
                                <td class="border border-slate-300 px-2 py-2 text-center h-12 align-middle">
                                    @if($schedules->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($schedules as $schedule)
                                                @php
                                                    $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                    $subject = \App\Models\Subject::find($schedule->subject_id);
                                                @endphp
                                                <div class="{{ $loop->index > 0 ? 'border-t border-slate-200 pt-1' : '' }}">
                                                    <div class="font-bold text-[10px] text-slate-900">{{ $subject->name ?? '-' }}</div>
                                                    <div class="text-[9px] text-slate-500 font-medium">{{ $teacher->name ?? '-' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-300">-</span>
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
        <table class="w-full text-sm text-left border-collapse border border-slate-300 mb-8">
            <thead>
                <tr class="bg-slate-100">
                    <th class="border border-slate-300 px-4 py-2.5 font-bold text-slate-800">Class</th>
                    <th class="border border-slate-300 px-4 py-2.5 text-center font-bold text-slate-800">Assigned / Total</th>
                    <th class="border border-slate-300 px-4 py-2.5 text-center font-bold text-slate-800">Completion %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->getScheduleSummary() as $item)
                    <tr>
                        <td class="border border-slate-300 px-4 py-2 font-bold text-slate-800 bg-slate-50">{{ $item['class'] }}</td>
                        <td class="border border-slate-300 px-4 py-2 text-center text-slate-700">{{ $item['assigned'] }} / {{ $item['total'] }}</td>
                        <td class="border border-slate-300 px-4 py-2 text-center font-bold {{ $item['percentage'] == 100 ? 'text-green-700' : 'text-slate-800' }}">
                            {{ $item['percentage'] }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="grid grid-cols-3 gap-8 text-center border border-slate-300 rounded-lg p-6 bg-slate-50 mt-4">
            <div>
                <div class="text-3xl font-extrabold text-slate-900">{{ $timetables->count() }}</div>
                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Total Assignments</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900">{{ $timetables->pluck('teacher_id')->unique()->count() }}</div>
                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Teachers Active</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-slate-900">{{ $classes->count() }}</div>
                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Classes</div>
            </div>
        </div>
    @endif
</div>
