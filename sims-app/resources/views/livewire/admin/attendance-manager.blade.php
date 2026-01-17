<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Attendance Manager</h1>
            <p class="text-gray-500">Mark daily attendance for any class</p>
        </div>
    </div>

    {{-- Controls --}}
    <div class="glass-card p-6 rounded-2xl flex flex-col md:flex-row gap-6 items-end">
        {{-- Class --}}
        <div class="w-full md:w-64">
            <label class="block text-sm font-medium text-gray-700 mb-1">Select Class</label>
            <select wire:model.live="selectedClassId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                <option value="">Select Class</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date --}}
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input 
                type="date" 
                wire:model.live="date"
                class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white"
            />
        </div>

        {{-- Weekend Indicator --}}
        @if($is_weekend)
            <div class="px-4 py-2 bg-orange-100 text-orange-700 rounded-xl font-medium text-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Weekend
            </div>
        @endif
    </div>

    {{-- Main Area --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Inputs Column --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6 rounded-2xl space-y-6">
                <h3 class="font-bold text-gray-800">Quick Entry</h3>
                
                {{-- Absent Input --}}
                <div>
                    <label class="block text-sm font-medium text-red-600 mb-2">Absent Roll Numbers</label>
                    <textarea 
                        wire:model.live.debounce.500ms="absent_rolls"
                        placeholder="e.g. 1, 5, 12"
                        rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-red-100 focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none resize-none font-mono text-lg"
                        @if($is_weekend) disabled @endif
                    ></textarea>
                    <p class="text-xs text-gray-400 mt-1">Separate with commas</p>
                </div>

                {{-- Leave Input --}}
                <div>
                    <label class="block text-sm font-medium text-yellow-600 mb-2">Leave Roll Numbers</label>
                    <textarea 
                        wire:model.live.debounce.500ms="leave_rolls"
                        placeholder="e.g. 3, 8"
                        rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-yellow-100 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 outline-none resize-none font-mono text-lg"
                        @if($is_weekend) disabled @endif
                    ></textarea>
                </div>

                {{-- Submit --}}
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-bold shadow-lg shadow-blue-200 flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($is_weekend) disabled @endif
                >
                    <span wire:loading.remove>Save Attendance</span>
                    <span wire:loading>Saving...</span>
                </button>

                @if(session()->has('message'))
                    <div class="text-green-600 text-center text-sm font-medium animate-fade-in">
                        {{ session('message') }}
                    </div>
                @endif
                 @if(session()->has('error'))
                    <div class="text-red-600 text-center text-sm font-medium animate-fade-in">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            {{-- Summary Card --}}
            <div class="glass-card p-6 rounded-2xl">
                <h3 class="font-bold text-gray-800 mb-4">Summary</h3>
                
                @if($attendance_status === 'submitted')
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Total Students</span>
                            <span class="font-bold text-gray-900">{{ $summary['total'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-gray-300 h-full" style="width: 100%"></div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-green-600 font-medium">Present</span>
                            <span class="font-bold text-green-700">{{ $summary['present'] }}</span>
                        </div>
                        <div class="w-full bg-green-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-green-500 h-full transition-all duration-500" style="width: {{ $summary['total'] > 0 ? ($summary['present'] / $summary['total']) * 100 : 0 }}%"></div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-red-600 font-medium">Absent</span>
                            <span class="font-bold text-red-700">{{ $summary['absent'] }}</span>
                        </div>
                        <div class="w-full bg-red-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-red-500 h-full transition-all duration-500" style="width: {{ $summary['total'] > 0 ? ($summary['absent'] / $summary['total']) * 100 : 0 }}%"></div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-yellow-600 font-medium">Leave</span>
                            <span class="font-bold text-yellow-700">{{ $summary['leave'] }}</span>
                        </div>
                        <div class="w-full bg-yellow-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-yellow-500 h-full transition-all duration-500" style="width: {{ $summary['total'] > 0 ? ($summary['leave'] / $summary['total']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="bg-gray-50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-gray-500 font-medium">Attendance is not marked yet</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- List Column --}}
        <div class="lg:col-span-2">
            <div class="glass-card rounded-2xl overflow-hidden">
                 <div class="bg-gray-50/50 px-6 py-3 border-b border-gray-100 font-medium text-gray-500 text-sm">
                    Student List
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Roll No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Adm No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Father Name</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($students as $student)
                                @php
                                    // Determine status from input strings
                                    $absentArr = array_map('trim', explode(',', $absent_rolls));
                                    $leaveArr = array_map('trim', explode(',', $leave_rolls));
                                    $roll = (string)$student->roll_no;
                                    
                                    $status = 'P';
                                    if(in_array($roll, $absentArr)) $status = 'A';
                                    elseif(in_array($roll, $leaveArr)) $status = 'L';
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">
                                        {{ $student->roll_no }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $student->admission_no ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $student->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $student->father_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($attendance_status === 'submitted')
                                            @if($status === 'P')
                                                <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-lg">Present</span>
                                            @elseif($status === 'A')
                                                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-lg">Absent</span>
                                            @elseif($status === 'L')
                                                <span class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-lg">Leave</span>
                                            @endif
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-lg">Not Updated Yet</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No students found in this class.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
