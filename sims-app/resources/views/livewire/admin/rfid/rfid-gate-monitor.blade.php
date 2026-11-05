<div class="py-6 space-y-6" wire:poll.3s>
    {{-- Header & Stats Strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Gate RFID Scans</p>
                <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $rfidScannedToday }}</h3>
                <span class="text-[11px] text-gray-400">Total smart cards verified</span>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Present Today</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $totalPresentToday }}</h3>
                <span class="text-[11px] text-gray-400">All shifts combined</span>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Campus Enrolled</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $totalActiveStudents }}</h3>
                <span class="text-[11px] text-gray-400">Active students in session</span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Today's Date</p>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-1">{{ \Carbon\Carbon::parse($todayDate)->format('d M, Y') }}</h3>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Gateway Feed
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.attendance') }}" class="px-3 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                    &larr; Daily Register
                </a>
                <a href="{{ route('admin.attendance.smart-cards') }}" class="px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Card Studio &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Main Content: Live Feed & Class Progress --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Live Stream (Left 2 cols) --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span>Real-Time Gate Punch-in Feed</span>
                </h3>
                <span class="text-xs text-gray-400">Refreshes automatically</span>
            </div>

            <div class="space-y-3">
                @forelse($recentScans as $index => $scan)
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between hover:shadow-md transition-all {{ $index === 0 ? 'ring-2 ring-emerald-500/30 bg-emerald-50/10' : '' }}">
                        <div class="flex items-center gap-4">
                            @if($scan->student?->profile_photo_path)
                                <img src="{{ asset('storage/' . $scan->student->profile_photo_path) }}" alt="{{ $scan->student->name }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                            @else
                                <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-base">
                                    {{ substr($scan->student?->name ?? 'S', 0, 1) }}
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white">{{ $scan->student?->name ?? 'Unknown Student' }}</h4>
                                    <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        Roll #{{ $scan->enrollment?->roll_number ?: ($scan->student?->roll_no ?: '-') }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-2">
                                    <span>Class: <strong class="text-gray-700 dark:text-gray-200">{{ $scan->enrollment?->class?->name ?? ($scan->student?->class?->name ?? 'Class') }}</strong></span>
                                    <span>•</span>
                                    <span>Terminal: <strong class="font-mono text-gray-600 dark:text-gray-300">{{ $scan->device_id ?: 'GATE-01' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $scan->check_in_at ? $scan->check_in_at->format('h:i:s A') : $scan->created_at->format('h:i:s A') }}</span>
                            </span>
                            @if($scan->check_out_at)
                                <div class="text-[11px] text-gray-400 mt-1">Out: {{ $scan->check_out_at->format('h:i A') }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 text-gray-400 space-y-2">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">No RFID Gate Scans Recorded Today</p>
                        <p class="text-xs">Cards tapped at the gate reader will appear here in real time.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Class Attendance Breakdown (Right col) --}}
        <div class="space-y-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Class-wise Gate Check-in</h3>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                @foreach($classes as $c)
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $c->name }}</span>
                            <span class="font-mono text-gray-500 dark:text-gray-400">{{ $c->present }} / {{ $c->total }} ({{ $c->percentage }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $c->percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
