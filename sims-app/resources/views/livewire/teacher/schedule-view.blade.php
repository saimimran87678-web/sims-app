<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">My Schedule</h1>
            <p class="text-gray-500">Your teaching schedule for the week</p>
        </div>
    </div>

    {{-- Day Tabs --}}
    <div class="flex gap-2 print:hidden">
        @foreach($days as $day)
            <button
                wire:click="$set('selectedDay', '{{ $day }}')"
                class="px-4 py-2 rounded-lg font-medium transition-colors {{ $selectedDay === $day ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
            >
                {{ $day }}
            </button>
        @endforeach
    </div>

    {{-- Print Header --}}
    <div class="hidden print:block text-center mb-4">
        <h1 class="text-xl font-bold">ISLAMABAD MODEL COLLEGE FOR BOYS (VI-X)</h1>
        <p class="text-sm">G-6/2 ISLAMABAD</p>
        <h2 class="text-lg font-semibold mt-2">{{ Auth::user()->name }} - {{ $selectedDay }} Schedule</h2>
    </div>

    {{-- Schedule Grid --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        @if($periods->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Period</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Room</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($periods as $period)
                            @if($period->is_break || $period->is_assembly)
                                <tr class="{{ $period->is_break ? 'bg-yellow-50' : 'bg-purple-50' }}">
                                    <td class="px-4 py-3 text-sm font-medium {{ $period->is_break ? 'text-yellow-700' : 'text-purple-700' }}">
                                        {{ $period->label }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                                    </td>
                                    <td colspan="3" class="px-4 py-3 text-center text-sm {{ $period->is_break ? 'text-yellow-600' : 'text-purple-600' }}">
                                        {{ $period->label }}
                                    </td>
                                </tr>
                            @else
                                @php $schedule = $this->getClassScheduleByPeriod($period->period_no); @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-bold text-gray-700">{{ $period->label }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                                    </td>
                                    @if($schedule)
                                        @php
                                            $class = collect($classes)->firstWhere('id', $schedule->class_id);
                                            $subject = \App\Models\Subject::find($schedule->subject_id);
                                        @endphp
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-sm font-semibold text-green-700 bg-green-100 rounded-lg">{{ $class->name ?? '-' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-blue-700">{{ $subject->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $schedule->room ?? '-' }}</td>
                                    @else
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs text-gray-400 bg-gray-50 rounded">Free</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400">-</td>
                                        <td class="px-4 py-3 text-sm text-gray-400">-</td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h3 class="text-lg font-medium text-gray-900">Period Configuration Missing</h3>
                <p class="mt-2 text-gray-500">Please contact admin to configure school periods.</p>
            </div>
        @endif
    </div>

    @if($timetables->isNotEmpty())
        <div class="text-sm text-gray-500 text-center print:hidden">
            You have {{ $timetables->count() }} class(es) on {{ $selectedDay }}.
        </div>
    @endif
    <style>
        @media print {
            body { background: white !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
        }
    </style>
</div>
