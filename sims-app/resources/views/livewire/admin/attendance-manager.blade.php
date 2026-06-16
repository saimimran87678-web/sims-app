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

        {{-- Manage Holidays Button --}}
        <div>
            <button wire:click="openHolidayModal" class="h-[42px] px-5 bg-white border border-purple-200 text-purple-700 rounded-xl hover:bg-purple-50 hover:border-purple-300 transition-colors font-medium flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Manage Holidays
            </button>
        </div>

        {{-- Status Indicator (Weekend/Holiday) --}}
        @if($is_holiday)
            <div class="px-4 py-2 bg-purple-100 text-purple-700 rounded-xl font-medium text-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                Holiday: {{ $holiday_reason ?: 'Declared' }}
            </div>
        @elseif($is_weekend)
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
                        class="w-full px-4 py-3 rounded-xl border border-red-100 focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none resize-none font-mono text-lg disabled:bg-gray-50 disabled:opacity-75"
                        @if($is_weekend || $is_holiday) disabled @endif
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
                        class="w-full px-4 py-3 rounded-xl border border-yellow-100 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 outline-none resize-none font-mono text-lg disabled:bg-gray-50 disabled:opacity-75"
                        @if($is_weekend || $is_holiday) disabled @endif
                    ></textarea>
                </div>

                {{-- Submit --}}
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-bold shadow-lg shadow-blue-200 flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($is_weekend || $is_holiday) disabled @endif
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

    {{-- Holiday Management Modal --}}
    @if($showHolidayModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full my-8 flex flex-col md:flex-row overflow-hidden max-h-[90vh]">
            
            {{-- Left Side: Add/Edit Form --}}
            <div class="w-full md:w-1/3 bg-gray-50 p-6 border-b md:border-b-0 md:border-r border-gray-200 flex flex-col">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800">{{ $holidayId ? 'Edit Holiday' : 'Declare Holiday' }}</h2>
                    <p class="text-sm text-gray-500">Block attendance for specific dates.</p>
                </div>

                <form wire:submit.prevent="saveHoliday" class="space-y-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Date(s)</label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isMultiDay" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-xs font-semibold text-gray-600 uppercase">Multi-Day</span>
                        </label>
                    </div>

                    <div>
                        <input type="date" wire:model.defer="holidayStart" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                        @error('holidayStart') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($isMultiDay)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" wire:model.defer="holidayEnd" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                        @error('holidayEnd') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Title</label>
                        <input type="text" wire:model.defer="holidayReason" placeholder="{{ $isMultiDay ? 'e.g. Summer Vacations' : 'e.g. Public Holiday' }}" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        @error('holidayReason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 mt-auto">
                        <button type="submit" wire:loading.attr="disabled" class="w-full py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveHoliday">{{ $holidayId ? 'Update Holiday' : 'Save Holiday' }}</span>
                            <span wire:loading wire:target="saveHoliday">Saving...</span>
                        </button>
                        @if($holidayId)
                            <button type="button" wire:click="resetHolidayForm" class="w-full py-2 mt-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                                Cancel Edit
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Right Side: Existing Holidays List --}}
            <div class="w-full md:w-2/3 bg-white p-6 flex flex-col h-[50vh] md:h-auto overflow-hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-800">Existing Holidays</h3>
                    <button wire:click="closeHolidayModal" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if(session()->has('holiday_message'))
                    <div class="bg-green-50 text-green-700 px-4 py-2 rounded-lg text-sm font-medium mb-4">
                        {{ session('holiday_message') }}
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar border border-gray-100 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date(s)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($holidaysList as $holiday)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        @if($holiday->start_date->equalTo($holiday->end_date))
                                            {{ $holiday->start_date->format('d M, Y') }}
                                        @else
                                            {{ $holiday->start_date->format('d M') }} - {{ $holiday->end_date->format('d M, Y') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                        {{ $holiday->reason ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button wire:click="editHoliday({{ $holiday->id }})" class="text-blue-600 hover:text-blue-900 px-2 py-1 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">Edit</button>
                                        <button wire:click="deleteHoliday({{ $holiday->id }})" wire:confirm="Are you sure you want to revoke this holiday?" class="text-red-600 hover:text-red-900 px-2 py-1 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">Revoke</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        No holidays declared yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endif
</div>
