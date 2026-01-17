<x-layouts.admin>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Users -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
                <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-blue-50 text-blue-600"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Total Users</p>
                        <h3 class="text-3xl font-bold text-gray-800">{{ $stats['users'] }}</h3>
                    </div>
                    <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
            </div>

            <!-- Total Classes -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
                <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-green-50 text-green-600"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Total Classes</p>
                        <h3 class="text-3xl font-bold text-gray-800">{{ $stats['classes'] }}</h3>
                    </div>
                    <div class="p-3 rounded-xl bg-green-50 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                    </div>
                </div>
            </div>

            <!-- Total Students -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
                <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-purple-50 text-purple-600"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Total Students</p>
                        <h3 class="text-3xl font-bold text-gray-800">{{ $stats['students'] }}</h3>
                    </div>
                    <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </div>
                </div>
            </div>

            <!-- Avg Attendance -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
                <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-yellow-50 text-yellow-600"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Avg Attendance</p>
                        <h3 class="text-3xl font-bold text-gray-800">{{ $stats['attendance'] }}%</h3>
                    </div>
                    <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section Placeholder -->
        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Attendance Overview</h3>
            <div class="h-80 w-full flex items-center justify-center bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <div class="text-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-12 h-12 mx-auto mb-2 opacity-50"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                    <p>Chart data will appear here once attendance records are generated.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
