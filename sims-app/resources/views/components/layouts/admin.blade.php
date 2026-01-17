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
                <div class="flex items-center justify-center w-10 h-10 text-white shadow-lg rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                    <!-- Shield Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
                        IMCB G-6/2
                    </h1>
                    <p class="text-xs font-medium tracking-wide text-gray-500">ADMIN PORTAL</p>
                </div>
            </div>

            <nav class="flex-1 py-6 space-y-1 overflow-y-auto min-h-0">
                <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span class="font-medium">Dashboard</span>
                </x-nav-link>

                <!-- Users -->
                @can('access-control.manage')
                <x-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="font-medium">User Management</span>
                </x-nav-link>
                @endcan

                <!-- Class Management -->
                @can('classes.manage')
                <x-nav-link :href="route('admin.classes')" :active="request()->routeIs('admin.classes')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                    <span class="font-medium">Class Management</span>
                </x-nav-link>
                @endcan

                <!-- Student Management -->
                @can('students.manage')
                <x-nav-link :href="route('admin.students')" :active="request()->routeIs('admin.students')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span class="font-medium">Student Management</span>
                </x-nav-link>
                @endcan

                <!-- Gradebook -->
                @can('classes.manage') <!-- Using classes.manage or grades.manage if existed -->
                <x-nav-link :href="route('admin.grades')" :active="request()->routeIs('admin.grades')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span class="font-medium">Gradebook</span>
                </x-nav-link>
                @endcan

                <!-- Attendance -->
                @can('students.manage')
                <x-nav-link :href="route('admin.attendance')" :active="request()->routeIs('admin.attendance')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    <span class="font-medium">Attendance</span>
                </x-nav-link>
                
                <!-- WhatsApp Setup -->
                <x-nav-link :href="route('admin.whatsapp-setup')" :active="request()->routeIs('admin.whatsapp-setup')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <span class="font-medium">WhatsApp Setup</span>
                </x-nav-link>

                <!-- Communication Hub -->
                <x-nav-link :href="route('admin.communication-hub')" :active="request()->routeIs('admin.communication-hub')" color="purple">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span class="font-medium">Communication Hub</span>
                </x-nav-link>
                @endcan

                 <!-- Reports -->
                @can('reports.view')
                <x-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="font-medium">Reports</span>
                </x-nav-link>
                @endcan

                <!-- Exams -->
                @can('exams.manage')
                <x-nav-link :href="route('admin.exams')" :active="request()->routeIs('admin.exams')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span class="font-medium">Exams</span>
                </x-nav-link>
                @endcan

                <!-- Schedule Management Entry -->
                @can('schedule.manage')
                <x-nav-link :href="route('admin.schedule')" :active="request()->routeIs('admin.schedule') || request()->routeIs('admin.view-schedule') || request()->routeIs('admin.period-config')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span class="font-medium">Schedule Management</span>
                </x-nav-link>
                @endcan

                <!-- Access Control (Shared) -->
                @can('access-control.manage')
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Access Control
                    </div>
                    
                    <x-nav-link :href="route('admin.feature-sharing')" :active="request()->routeIs('admin.feature-sharing')" color="blue">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                         <span class="font-medium">Feature Sharing</span>
                    </x-nav-link>

                    <x-nav-link :href="route('admin.allocations')" :active="request()->routeIs('admin.allocations')" color="blue">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                         <span class="font-medium">Data Scope</span>
                    </x-nav-link>
                </div>
                @endcan
            </nav>

            <div class="p-4 border-t border-gray-100 flex-shrink-0">
                <div class="p-4 border border-blue-100 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center justify-center w-10 h-10 text-lg font-bold text-blue-600 bg-white shadow-sm rounded-full">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs font-medium text-blue-600 truncate">{{ Auth::user()->email }}</p>
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
                        <div class="text-xs px-2 py-0.5 rounded-full inline-block bg-purple-100 text-purple-700">
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
