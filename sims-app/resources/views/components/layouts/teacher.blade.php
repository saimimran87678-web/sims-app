<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | IMCB G-6/2</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
    <div x-data="{ sidebarOpen: window.innerWidth >= 768 }" class="flex h-screen overflow-hidden bg-gray-50">
        
        <!-- Sidebar -->
        <aside x-show="sidebarOpen" class="fixed inset-y-0 left-0 z-50 w-64 border-r border-gray-200 bg-white/90 backdrop-blur-xl md:relative flex flex-col h-screen">
            <div class="flex items-center gap-3 p-6 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 text-white shadow-lg rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                    <!-- Graduation Cap Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600">
                        IMCB G-6/2
                    </h1>
                    <p class="text-xs font-medium tracking-wide text-gray-500">TEACHER PORTAL</p>
                </div>
            </div>

            <nav class="flex-1 py-6 space-y-1 overflow-y-auto px-2 custom-scrollbar">
                <x-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span class="font-medium">Dashboard</span>
                </x-nav-link>

                <!-- Attendance -->
                <x-nav-link :href="route('teacher.attendance')" :active="request()->routeIs('teacher.attendance')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    <span class="font-medium">Attendance</span>
                </x-nav-link>

                 <!-- Reports -->
                 <x-nav-link :href="route('teacher.reports')" :active="request()->routeIs('teacher.reports')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                    <span class="font-medium">Reports</span>
                </x-nav-link>

                <!-- Gradebook -->
                <x-nav-link :href="route('teacher.grades')" :active="request()->routeIs('teacher.grades')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span class="font-medium">Gradebook</span>
                </x-nav-link>

                <!-- Schedule -->
                <x-nav-link :href="route('teacher.schedule')" :active="request()->routeIs('teacher.schedule')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span class="font-medium">My Schedule</span>
                </x-nav-link>

                <!-- Students -->
                <x-nav-link :href="route('teacher.students')" :active="request()->routeIs('teacher.students')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="font-medium">My Students</span>
                </x-nav-link>

                <!-- Communication Hub -->
                <x-nav-link :href="route('teacher.communication-hub')" :active="request()->routeIs('teacher.communication-hub')" color="purple">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span class="font-medium">Communication Hub</span>
                </x-nav-link>
                <!-- Shared Admin Features -->
                @if(Auth::user()->getAllPermissions()->isNotEmpty())
                    <div class="px-6 py-4 mt-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                            Shared Features
                        </p>
                        
                        @can('exams.manage')
                        <x-nav-link :href="route('teacher.shared.exams')" :active="request()->routeIs('teacher.shared.exams')" color="purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span class="font-medium">Manage Exams</span>
                        </x-nav-link>
                        @endcan

                        @can('students.manage')
                        <x-nav-link :href="route('teacher.shared.students')" :active="request()->routeIs('teacher.shared.students')" color="purple">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="font-medium">Manage Students</span>
                        </x-nav-link>
                        @endcan

                        @can('classes.manage')
                        <x-nav-link :href="route('teacher.shared.classes')" :active="request()->routeIs('teacher.shared.classes')" color="purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M3 3h18v18H3zM21 9H3M21 15H3M12 3v18"/></svg>
                            <span class="font-medium">Manage Classes</span>
                        </x-nav-link>
                        @endcan

                        @can('schedule.manage')
                        <x-nav-link :href="route('teacher.shared.schedule')" :active="request()->routeIs('teacher.shared.schedule*')" color="purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span class="font-medium">Schedule Manager</span>
                        </x-nav-link>
                        @endcan
                        
                        @can('access-control.manage')
                        <x-nav-link :href="route('admin.feature-sharing')" :active="request()->routeIs('admin.feature-sharing')" color="purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.743 5.743L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.586a1 1 0 0 1.293-.707l5.964-5.964A6 6 0 1 1 21 9z"/></svg>
                            <span class="font-medium">Access Control</span>
                        </x-nav-link>
                        @endcan

                        @can('allocations.view')
                        <x-nav-link :href="route('admin.allocations')" :active="request()->routeIs('admin.allocations')" color="purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                            </svg>
                            <span class="font-medium">Allocations</span>
                        </x-nav-link>
                        @endcan
                    </div>
                @endif

            </nav>

            <div class="p-4 border-t border-gray-100 flex-shrink-0">
                <div class="p-4 border border-green-100 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center justify-center w-10 h-10 text-lg font-bold text-green-600 bg-white shadow-sm rounded-full">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs font-medium text-green-600 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    
                    <!-- Logout Form -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center justify-center w-full gap-2 px-3 py-2 text-sm font-medium text-red-500 transition-colors bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="relative flex flex-col flex-1 h-screen overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between px-4 border-b border-gray-200 h-16 bg-white/50 backdrop-blur-sm md:px-8 z-10">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">
                        @yield('header', 'Dashboard')
                    </h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right md:block">
                        <div class="text-sm font-bold text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="text-xs px-2 py-0.5 rounded-full inline-block bg-green-100 text-green-700">
                            {{ ucfirst(Auth::user()->role) }}
                        </div>
                    </div>

                    <div class="hidden pl-4 ml-2 text-right border-l border-gray-200 md:block">
                        <p class="text-sm font-medium text-gray-600">
                            {{ now()->format('l, d M Y') }}
                        </p>
                    </div>
                    <div class="flex items-center justify-center w-8 h-8 transition-colors bg-gray-100 rounded-full cursor-pointer hover:bg-gray-200 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 p-4 overflow-y-auto md:p-8 scroll-smooth">
                {{ $slot }}
            </div>

            <!-- Mobile Overlay -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" style="display: none;"></div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
