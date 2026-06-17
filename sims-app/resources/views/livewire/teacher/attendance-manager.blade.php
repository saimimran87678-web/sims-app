<div class="space-y-6 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Attendance Management</h1>
            <p class="text-gray-500">
                @if($classId)
                    {{ $className }} • {{ $summary['total'] }} Students
                @else
                    <span class="text-red-500">No Class Assigned</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-500 ml-2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            <input
                wire:model.live="date"
                type="date"
                max="{{ date('Y-m-d') }}"
                class="border-none focus:ring-0 text-gray-700 font-medium bg-transparent"
            />
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Holiday Warning --}}
    @if($is_holiday)
        <div class="bg-purple-50 border border-purple-100 p-4 rounded-xl flex items-center gap-3 text-purple-800 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
            <p class="font-medium">Holiday: {{ $holiday_reason ?: 'Declared' }}. Attendance cannot be marked.</p>
        </div>
    @elseif($is_weekend)
        <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl flex items-center gap-3 text-orange-800 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            <p class="font-medium">Selected date is a weekend. Attendance cannot be marked.</p>
        </div>
    @endif

    <div class="glass-card p-6 rounded-2xl">
        <form wire:submit="save" class="space-y-8">
            {{-- Instructions --}}
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <h3 class="font-bold text-blue-900 mb-1 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Smart Attendance Mode
                </h3>
                <p class="text-sm text-blue-700">
                    All students are marked <strong>Present</strong> by default.
                    Simply enter the confirmed <strong>Roll Numbers</strong> of students who are <strong>Absent</strong> or on <strong>Leave</strong>.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Absent Input --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-red-600 uppercase tracking-wide">
                        Absent Roll Numbers
                    </label>
                    <div class="relative">
                        <input
                            wire:model.live.debounce.300ms="absent_rolls"
                            type="text"
                            placeholder="e.g. 2, 5, 12"
                            @if($is_weekend || $is_holiday) disabled @endif
                            class="w-full pl-4 pr-24 py-3 rounded-xl border-2 border-red-100 focus:border-red-500 focus:ring-0 outline-none transition-colors text-lg font-medium placeholder:font-normal placeholder-red-200 disabled:bg-gray-50 disabled:opacity-75"
                        />
                        <div class="hidden md:block absolute right-3 top-1/2 -translate-y-1/2 bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded">
                            ABSENT
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Separate multiple roll numbers with commas.</p>
                </div>

                {{-- Leave Input --}}
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-yellow-600 uppercase tracking-wide">
                        On Leave Roll Numbers
                    </label>
                    <div class="relative">
                        <input
                            wire:model.live.debounce.300ms="leave_rolls"
                            type="text"
                            placeholder="e.g. 8, 15"
                             @if($is_weekend || $is_holiday) disabled @endif
                            class="w-full pl-4 pr-24 py-3 rounded-xl border-2 border-yellow-100 focus:border-yellow-500 focus:ring-0 outline-none transition-colors text-lg font-medium placeholder:font-normal placeholder-yellow-200 disabled:bg-gray-50 disabled:opacity-75"
                        />
                        <div class="hidden md:block absolute right-3 top-1/2 -translate-y-1/2 bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-1 rounded">
                            LEAVE
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Separate multiple roll numbers with commas.</p>
                </div>
            </div>

            {{-- Summary Preview --}}
            <div class="border-t border-gray-100 pt-6">
                <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4">Summary Preview</h4>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-green-50 p-4 rounded-xl text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ $summary['present'] }}
                        </div>
                        <div class="text-xs font-bold text-green-800 uppercase mt-1">Present</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl text-center">
                        <div class="text-2xl font-bold text-red-600">
                             {{ $summary['absent'] }}
                        </div>
                        <div class="text-xs font-bold text-red-800 uppercase mt-1">Absent</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-xl text-center">
                        <div class="text-2xl font-bold text-yellow-600">
                             {{ $summary['leave'] }}
                        </div>
                        <div class="text-xs font-bold text-yellow-800 uppercase mt-1">On Leave</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    @if($is_weekend || $is_holiday) disabled @endif
                    class="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-bold shadow-lg shadow-blue-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>Save Attendance</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if($attendance_status === 'submitted')
        <div class="glass-card p-6 rounded-2xl bg-white border border-gray-100 shadow-sm mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Manage Late Arrivals
            </h3>
            
            <p class="text-sm text-gray-500 mb-6">
                Students marked Absent or On Leave are listed below. Click **"Mark as Late"** when a student arrives. They will be marked as **Present**, and an urgent priority late notification will be queued for their parents.
            </p>

            @php
                $lateCandidates = $this->absentOrLeaveStudents;
            @endphp

            @if($lateCandidates->isEmpty())
                <div class="bg-gray-50 border border-dashed border-gray-200 p-8 rounded-xl text-center text-gray-500">
                    No students are currently marked absent or on leave for this date.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 rounded-lg">
                            <tr>
                                <th scope="col" class="px-6 py-3 rounded-l-lg">Roll No</th>
                                <th scope="col" class="px-6 py-3">Student Name</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                                <th scope="col" class="px-6 py-3 rounded-r-lg text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($lateCandidates as $student)
                                <tr class="bg-white hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-900">#{{ $student->roll_no }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-700">{{ $student->name }}</td>
                                    <td class="px-6 py-4">
                                        @if($student->status === 'A')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-50 text-red-600 border border-red-100">Absent</span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-100">On Leave</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            wire:click="markAsLate({{ $student->id }})" 
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-sm transition-colors"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                            Mark as Late
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
