<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Attendance Reports</h1>
            <p className="text-gray-500">View and export monthly attendance summaries</p>
        </div>
    </div>

    {{-- Controls --}}
    <div class="glass-card p-6 rounded-2xl">
        <form wire:submit="generate" class="flex flex-col md:flex-row gap-4 items-end">
            {{-- Class --}}
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select wire:model="selectedClassId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Month --}}
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <input 
                    type="month" 
                    wire:model="selectedMonth"
                    class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                />
            </div>

            {{-- Generate --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 disabled:opacity-50 h-[42px]"
            >
                <span wire:loading.remove>Generate Report</span>
                <span wire:loading>Processing...</span>
            </button>
        </form>

        @if(session()->has('error'))
            <div class="mt-4 p-4 text-red-700 bg-red-50 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Results --}}
    @if(!empty($reportData))
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">
                    Report Data: {{ Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                </h3>
                <button 
                    wire:click="downloadCsv"
                    class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Export CSV
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Roll No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wider">Present</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-red-600 uppercase tracking-wider">Absent</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-yellow-600 uppercase tracking-wider">Leave</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-blue-600 uppercase tracking-wider">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reportData as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $row['roll_no'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-600">
                                {{ $row['total_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-green-600">
                                {{ $row['present'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-red-600">
                                {{ $row['absent'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-yellow-600">
                                {{ $row['leave'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-blue-600">
                                {{ $row['percentage'] }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($noDataAvailable)
        {{-- No Data Available --}}
        <div class="glass-card p-12 text-center rounded-2xl">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <p class="text-gray-600 font-medium text-lg">Attendance data not available</p>
            <p class="text-gray-400 text-sm mt-1">No attendance records found for {{ Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</p>
        </div>
    @elseif($selectedClassId && !$isLoading)
        {{-- Prompt to Generate --}}
         <div class="glass-card p-12 text-center rounded-2xl">
            <p class="text-gray-500">Click "Generate Report" to view data.</p>
        </div>
    @endif
</div>
