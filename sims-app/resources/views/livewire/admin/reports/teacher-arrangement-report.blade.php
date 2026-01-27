<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Teacher Arrangement Report</h1>
            <p class="text-sm text-gray-500">View and print daily substitution arrangements</p>
        </div>
        <div class="flex gap-3">
             <input type="date" wire:model.live="date" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
             
            <button wire:click="downloadPdf" class="flex items-center gap-2 px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    <!-- Preview Card -->
    <div class="p-6 bg-white shadow-sm rounded-xl border border-gray-100">
        <div class="mb-6 text-center border-b border-gray-100 pb-4">
            <h2 class="text-xl font-bold text-gray-800">Teacher Arrangement Report</h2>
            <p class="text-gray-500">{{ $formattedDate }}</p>
        </div>

        @if(empty($reportData))
            <div class="py-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-green-50 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">All Available</h3>
                <p class="text-gray-500">No teachers are marked absent or on leave for this date.</p>
            </div>
        @else
            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="p-4 font-semibold text-gray-700 border-r w-1/3">Absent Teacher</th>
                            <th class="p-4 font-semibold text-gray-700">Arrangements / Substitutions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($reportData as $row)
                            <tr class="hover:bg-gray-50/50">
                                <td class="p-4 align-top border-r">
                                    <div class="font-bold text-gray-900">{{ $row['teacher'] }}</div>
                                    <div class="text-xs font-medium uppercase mt-1 px-2 py-0.5 rounded-full inline-block 
                                        {{ $row['status'] == 'absent' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $row['status'] == 'leave' ? 'bg-orange-100 text-orange-700' : '' }}
                                        {{ $row['status'] == 'official_duty' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $row['status'] == 'late' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                        {{ str_replace('_', ' ', $row['status']) }}
                                    </div>
                                    @if($row['remarks'])
                                        <p class="mt-2 text-xs italic text-gray-500">"{{ $row['remarks'] }}"</p>
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if(empty($row['schedule']))
                                        <span class="text-sm text-gray-400 italic">No classes scheduled for today.</span>
                                    @else
                                        <div class="space-y-3">
                                            @foreach($row['schedule'] as $sched)
                                                <div class="flex items-start gap-3 p-2 rounded-lg bg-gray-50 border border-gray-100">
                                                    <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 font-bold bg-white border border-gray-200 rounded text-gray-600 text-sm">
                                                        {{ $sched['period'] }}
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $sched['class'] }} - {{ $sched['subject'] }}
                                                        </div>
                                                        <div class="text-sm font-semibold mt-0.5 {{ $sched['sub_teacher_id'] ? 'text-blue-600' : 'text-red-500' }}">
                                                            Sub: {{ $sched['substitute'] }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
