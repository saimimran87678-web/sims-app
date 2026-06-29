<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
        <!-- Total Users -->
        @can('users.manage')
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-blue-50 text-blue-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $stats['users'] }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Total Classes -->
        @can('classes.manage')
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-green-50 text-green-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Classes</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $stats['classes'] }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-green-50 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><circle cx="12" cy="9" r="2"/></svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Total Students -->
        @can('students.manage')
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-purple-50 text-purple-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Students</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $stats['students'] }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-purple-50 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Avg Attendance -->
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-yellow-50 text-yellow-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Avg Attendance</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $stats['attendance'] }}%</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-yellow-50 text-yellow-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
        </div>

        <!-- Paid This Month -->
        @can('students.manage')
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow border-l-4 border-emerald-500">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-emerald-50 text-emerald-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Paid This Month</p>
                    <h3 class="text-2xl font-bold text-emerald-600">{{ $stats['paid_this_month'] }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Students Left -->
        @can('students.manage')
        <div class="glass-card p-6 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-shadow border-l-4 border-rose-500">
            <div class="absolute right-0 top-0 w-24 h-24 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110 opacity-20 bg-rose-50 text-rose-600"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Students Left</p>
                    <h3 class="text-2xl font-bold text-rose-600">{{ $stats['students_left'] }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-rose-50 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <!-- Main Content Area Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Attendance Overview -->
        <div class="glass-card p-6 rounded-2xl lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Attendance Overview</h3>
            <div class="h-80 w-full flex items-center justify-center bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <div class="text-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-12 h-12 mx-auto mb-2 opacity-50"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                    <p>Chart data will appear here once attendance records are generated.</p>
                </div>
            </div>
        </div>

        <!-- Quick Operations -->
        <div class="glass-card p-6 rounded-2xl flex flex-col h-full justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">Quick Operations</h3>
                <p class="text-xs text-gray-400 mb-6">Common administrative shortcuts</p>
                
                <div class="space-y-3">
                    <!-- Manage Students -->
                    <a href="{{ route('admin.students') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50/50 text-gray-700 hover:text-blue-600 transition-colors border border-gray-100/50 hover:border-blue-100">
                        <div class="p-2 rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">Manage Students</p>
                            <p class="text-xs text-gray-400">View list, admit, or edit profiles</p>
                        </div>
                    </a>

                    <!-- Manage Classes -->
                    <a href="{{ route('admin.classes') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-green-50/50 text-gray-700 hover:text-green-600 transition-colors border border-gray-100/50 hover:border-green-100">
                        <div class="p-2 rounded-lg bg-green-50 text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">Manage Classes</p>
                            <p class="text-xs text-gray-400">Add classes, sections, and sessions</p>
                        </div>
                    </a>

                    <!-- Mark Attendance -->
                    <a href="{{ route('admin.attendance') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-yellow-50/50 text-gray-700 hover:text-yellow-600 transition-colors border border-gray-100/50 hover:border-yellow-100">
                        <div class="p-2 rounded-lg bg-yellow-50 text-yellow-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">Mark Attendance</p>
                            <p class="text-xs text-gray-400">Take student daily attendance</p>
                        </div>
                    </a>

                    <!-- Record Payment -->
                    <a href="{{ route('admin.fee.record-payment') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50/50 text-gray-700 hover:text-emerald-600 transition-colors border border-gray-100/50 hover:border-emerald-100">
                        <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">Record Payment</p>
                            <p class="text-xs text-gray-400">Collect student fee & issue receipts</p>
                        </div>
                    </a>

                    <!-- WhatsApp Setup -->
                    <a href="{{ route('admin.whatsapp-setup') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-teal-50/50 text-gray-700 hover:text-teal-600 transition-colors border border-gray-100/50 hover:border-teal-100">
                        <div class="p-2 rounded-lg bg-teal-50 text-teal-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">WhatsApp Setup</p>
                            <p class="text-xs text-gray-400">Connect API and check status</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
