<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Users -->
        @can('users.manage')
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
        @endcan

        <!-- Total Classes -->
        @can('classes.manage')
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
        @endcan

        <!-- Total Students -->
        @can('students.manage')
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
        @endcan

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
    <!-- Timetable Printing Section -->
    <div class="glass-card p-6 rounded-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Timetable Printing</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Print by Class -->
            <div class="bg-gray-50/50 rounded-xl p-5 border border-gray-100" x-data="{ selectedClass: '' }">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                    By Class
                </h4>
                <div class="space-y-3">
                    <select x-model="selectedClass" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Class...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <button 
                        :disabled="!selectedClass"
                        @click="window.open('{{ route('admin.timetable.print.class', '__ID__') }}'.replace('__ID__', selectedClass), '_blank')"
                        class="w-full btn btn-primary flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Print Class Timetable</span>
                    </button>
                </div>
            </div>

            <!-- Print by Teacher -->
            <div class="bg-gray-50/50 rounded-xl p-5 border border-gray-100" x-data="{ selectedTeacher: '' }">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    By Teacher
                </h4>
                <div class="space-y-3">
                    <select x-model="selectedTeacher" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Teacher...</option>
                        <option value="all" class="font-bold text-blue-600">All Teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <button 
                        :disabled="!selectedTeacher"
                        @click="window.open('{{ route('admin.timetable.print.teacher', '__ID__') }}'.replace('__ID__', selectedTeacher), '_blank')"
                        class="w-full btn btn-secondary flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Print Teacher Timetable</span>
                    </button>
                </div>
            </div>

            <!-- Print Master -->
            <div class="bg-gray-50/50 rounded-xl p-5 border border-gray-100" x-data="{ selectedDay: 'Monday' }">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Master Timetable
                </h4>
                <div class="space-y-3">
                    <select x-model="selectedDay" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Days</option>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            @click="window.open('{{ route('admin.timetable.print.master') }}?mode=class&day=' + selectedDay, '_blank')"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-sm">
                            Class View
                        </button>
                        <button 
                            @click="window.open('{{ route('admin.timetable.print.master') }}?mode=teacher&day=' + selectedDay, '_blank')"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-sm">
                            Teacher View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
