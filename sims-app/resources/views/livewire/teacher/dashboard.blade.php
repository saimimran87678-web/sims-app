<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Welcome back, {{ explode(' ', Auth::user()->name)[0] }}! 👋
            </h1>
            <p class="text-gray-500 mt-1">Here's what's happening in your classes today.</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-2 text-sm font-medium text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-blue-500"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            {{ now()->format('l, F j') }}
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Students -->
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg bg-gradient-to-br from-blue-500 to-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Students</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['students'] }}</h3>
            </div>
        </div>
        
        <!-- Assigned Subjects -->
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg bg-gradient-to-br from-purple-500 to-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Assigned Subjects</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['subjects'] }}</h3>
            </div>
        </div>

        <!-- Classes Today -->
        <div class="glass-card p-6 rounded-2xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg bg-gradient-to-br from-orange-500 to-orange-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Classes Today</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['classes_today'] }}</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Activity / Schedule Placeholder -->
        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-blue-500"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Today's Schedule
            </h3>
            <div class="space-y-4">
                @forelse($periods as $period)
                    @if(!$period->is_break && !$period->is_assembly)
                        @php 
                            $classSchedule = $todaySchedule[$period->period_no] ?? null;
                            // Simple Time Status Logic
                            $now = now();
                            $start = \Carbon\Carbon::parse($period->start_time);
                            $end = \Carbon\Carbon::parse($period->end_time);
                            $isNow = $now->between($start, $end);
                            $isPast = $now->gt($end);
                        @endphp

                        @if($classSchedule)
                            <div class="p-4 rounded-xl border flex items-center justify-between {{ $isNow ? 'bg-blue-50 border-blue-100' : 'bg-white border-gray-100' }}">
                                <div>
                                    <h4 class="font-bold {{ $isNow ? 'text-blue-900' : 'text-gray-800' }} flex items-center gap-2">
                                        {{ $classSchedule->subject_name }}
                                        @if($classSchedule->is_substitute)
                                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-[10px] uppercase tracking-wider font-bold rounded-full">Substitute</span>
                                        @endif
                                    </h4>
                                    <p class="text-sm {{ $isNow ? 'text-blue-700' : 'text-gray-600' }}">
                                        {{ $classSchedule->class_name }} • {{ $start->format('h:i A') }} - {{ $end->format('h:i A') }}
                                    </p>
                                </div>
                                @if($isNow)
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">Now</span>
                                @elseif(!$isPast)
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">Upcoming</span>
                                @else
                                    <span class="px-3 py-1 bg-gray-50 text-gray-400 text-xs font-bold rounded-full">Done</span>
                                @endif
                            </div>
                        @endif
                    @endif
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No schedule available.</p>
                @endforelse
                
                @if($todaySchedule->isEmpty())
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-center text-gray-500 text-sm">
                        No classes scheduled for today.
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-orange-500"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                Important Notices
            </h3>
            <div class="space-y-4">
                <div class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors">
                    <p class="text-sm text-gray-500 mb-1">Admin Office • 2 hours ago</p>
                    <p class="font-medium text-gray-800">Staff meeting scheduled for Friday at 2:00 PM.</p>
                </div>
                <div class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors">
                    <p class="text-sm text-gray-500 mb-1">System • 1 day ago</p>
                    <p class="font-medium text-gray-800">Grade submission deadline for Mid-Terms is approaching.</p>
                </div>
            </div>
        </div>
    </div>
</div>
