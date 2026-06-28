<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | {{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}</title>

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
        <aside 
            x-show="sidebarOpen" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 border-r border-gray-200 bg-white/90 backdrop-blur-xl md:relative md:translate-x-0 flex flex-col h-screen transform"
        >
            <div class="flex items-center gap-3 p-6 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 text-white shadow-lg rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                    <!-- Shield Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 truncate max-w-[150px]" title="{{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}">
                        {{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m12 3-10 5L12 13l10-5-10-5Z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    <span class="font-medium">Class Management</span>
                </x-nav-link>
                @endcan

                <!-- Student Management -->
                @can('students.manage')
                <x-nav-link :href="route('admin.students')" :active="request()->routeIs('admin.students')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M17 18a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2"/><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="font-medium">Student Management</span>
                </x-nav-link>
                @endcan

                <!-- Fee Management -->
                @if((\App\Services\LicenseStatus::getStatus()['plan'] ?? 'basic') !== 'basic')
                <div x-data="{ feeOpen: {{ request()->routeIs('admin.fee.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="feeOpen = !feeOpen" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:text-blue-700 hover:bg-blue-50 transition-colors {{ request()->routeIs('admin.fee.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-1"/><path d="M16 12h5"/><circle cx="18" cy="12" r="1"/></svg>
                            <span>Fee Management</span>
                        </div>
                        <svg :class="{'rotate-180': feeOpen}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="feeOpen" x-transition class="pl-11 pr-3 py-2 space-y-1">
                        <a href="{{ route('admin.fee.generator') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-blue-700 hover:bg-blue-50 {{ request()->routeIs('admin.fee.generator') ? 'text-blue-700 bg-blue-50 font-medium' : '' }}">Voucher Management</a>
                        <a href="{{ route('admin.fee.record-payment') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-blue-700 hover:bg-blue-50 {{ request()->routeIs('admin.fee.record-payment') ? 'text-blue-700 bg-blue-50 font-medium' : '' }}">Collect Fees</a>
                        <a href="{{ route('admin.fee.defaulters') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-blue-700 hover:bg-blue-50 {{ request()->routeIs('admin.fee.defaulters') ? 'text-blue-700 bg-blue-50 font-medium' : '' }}">Defaulter List</a>
                    </div>
                </div>
                @endif

                <!-- Gradebook -->
                @can('classes.manage') <!-- Using classes.manage or grades.manage if existed -->
                <x-nav-link :href="route('admin.grades')" :active="request()->routeIs('admin.grades')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    <span class="font-medium">Gradebook</span>
                </x-nav-link>
                @endcan

                <!-- Attendance -->
                @can('students.manage')
                <x-nav-link :href="route('admin.attendance')" :active="request()->routeIs('admin.attendance')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                    <span class="font-medium">Attendance</span>
                </x-nav-link>
                
                <!-- WhatsApp Setup -->
                <x-nav-link :href="route('admin.whatsapp-setup')" :active="request()->routeIs('admin.whatsapp-setup') || request()->routeIs('admin.whatsapp-templates')" color="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <span class="font-medium">WhatsApp Setup</span>
                </x-nav-link>

                <!-- Communication Hub -->
                <x-nav-link :href="route('admin.communication-hub')" :active="request()->routeIs('admin.communication-hub')" color="purple">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    <span class="font-medium">Communication Hub</span>
                </x-nav-link>
                @endcan

                 <!-- Reports -->
                @can('reports.view')
                <x-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span class="font-medium">Reports</span>
                </x-nav-link>
                @endcan

                <!-- Exams -->
                @can('exams.manage')
                <x-nav-link :href="route('admin.exams')" :active="request()->routeIs('admin.exams')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9.1 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                    <span class="font-medium">Exams</span>
                </x-nav-link>
                @endcan

                <!-- Schedule Management Entry -->
                @can('schedule.manage')
                <x-nav-link :href="route('admin.schedule')" :active="request()->routeIs('admin.schedule') || request()->routeIs('admin.view-schedule') || request()->routeIs('admin.period-config')" color="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span class="font-medium">Schedule Management</span>
                </x-nav-link>
                
                <x-nav-link :href="route('admin.substitutions')" :active="request()->routeIs('admin.substitutions')" color="orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/></svg>
                    <span class="font-medium">Substitutions & Attendance</span>
                </x-nav-link>
                @endcan



                <!-- Access Control (Shared) -->
                @can('access-control.manage')
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Access Control
                    </div>
                    
                    <x-nav-link :href="route('admin.feature-sharing')" :active="request()->routeIs('admin.feature-sharing')" color="blue">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                          <span class="font-medium">Feature Sharing</span>
                    </x-nav-link>

                    <x-nav-link :href="route('admin.allocations')" :active="request()->routeIs('admin.allocations')" color="blue">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                          <span class="font-medium">Data Scope</span>
                    </x-nav-link>
                </div>
                @endcan
            </nav>

        </aside>

        <!-- Main Content -->
        <main class="relative flex flex-col flex-1 h-screen overflow-hidden">
            <livewire:license-banner />
            <!-- Header -->
            <header class="flex items-center justify-between px-4 border-b border-gray-200 h-16 bg-white/50 backdrop-blur-sm md:px-8 z-10">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>

                    <h2 class="text-xl font-bold text-gray-800">
                        @yield('header', 'Dashboard')
                    </h2>

                    <!-- Session Selector -->
                    @php
                        $allSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
                        $currentSessionId = \App\Models\AcademicSession::getActiveSessionId();
                    @endphp
                    <form action="{{ route('change-session') }}" method="POST" id="session-switch-form" class="inline-flex items-center">
                        @csrf
                        <div class="relative flex items-center bg-blue-50 text-blue-700 border border-blue-100 rounded-full px-2.5 py-0.5 text-xs font-semibold">
                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            <span class="mr-1">Session:</span>
                            <select 
                                name="academic_session_id" 
                                onchange="this.form.submit()" 
                                class="bg-transparent border-none p-0 pr-6 text-xs font-semibold focus:ring-0 focus:outline-none cursor-pointer text-blue-700"
                            >
                                @foreach($allSessions as $session)
                                    <option value="{{ $session->id }}" @selected($session->id == $currentSessionId) class="text-gray-800">
                                        {{ $session->name }} 
                                        @if($session->shift_type && !str_contains(strtolower($session->name), strtolower($session->shift_type)))
                                            ({{ $session->shift_type }}) 
                                        @endif
                                        {{ $session->is_active ? '- Active' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <div class="flex items-center gap-3 md:gap-5">
                    <!-- Session Shifter (Morning/Evening) removed as per user request -->

                    <!-- Date -->
                    <div class="hidden md:block text-sm font-semibold text-gray-600 border-r border-gray-200 pr-5">
                        {{ now()->format('l, d M Y') }}
                    </div>

                    <!-- Notifications -->
                    <button class="relative flex items-center justify-center w-9 h-9 transition-colors bg-gray-100 rounded-full hover:bg-gray-200 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>

                    <!-- Profile Dropdown -->
                    <div x-data="{ profileOpen: false }" class="relative">
                        <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center gap-2 focus:outline-none bg-white p-1 pr-3 rounded-full border border-gray-200 hover:border-blue-300 transition-all shadow-sm">
                            <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-blue-600 bg-blue-50 rounded-full">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-bold text-gray-800 leading-none">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] font-bold text-purple-600 uppercase mt-0.5">{{ Auth::user()->role }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 hidden md:block" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="profileOpen" style="display: none;" x-transition.opacity class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] py-2 border border-gray-100 z-50">
                            <div class="px-4 py-2 border-b border-gray-50 md:hidden">
                                <p class="text-sm font-bold text-gray-800">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                            </div>
                            
                            <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                System Settings
                            </a>
                            
                            <div class="h-px bg-gray-100 my-1"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center px-4 py-2.5 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 p-4 overflow-y-auto md:p-8 scroll-smooth">
                {{ $slot }}
            </div>

            <!-- Mobile Overlay -->
            <div 
                x-show="sidebarOpen" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false" 
                class="fixed inset-0 z-40 bg-black/50 md:hidden"
            ></div>
        </main>
    </div>
    @stack('scripts')

    {{-- ============================================================
         Custom Read-Only Notification Modal
         Intercepts Livewire's LicenseLockedException BEFORE 
         Laravel/Livewire shows its default white error popup.
         ============================================================ --}}

    {{-- Modal Overlay --}}
    <div id="license-locked-modal" 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
         style="display:none !important;">
        
        {{-- Backdrop: blurred app background, no white canvas --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeLicenseModal()"></div>

        {{-- Card: matches app's glass-card style --}}
        <div id="license-locked-card"
             class="relative z-10 w-full max-w-md glass-card rounded-3xl p-8 text-center space-y-5"
             style="transform: scale(0.9) translateY(16px); opacity:0; transition: transform 0.4s cubic-bezier(0.16,1,0.3,1), opacity 0.4s ease;">

            {{-- Close X --}}
            <button onclick="closeLicenseModal()"
                    class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Animated Icon --}}
            <div class="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center border border-red-100 relative">
                <div class="absolute inset-0 bg-red-200 rounded-full animate-ping opacity-40"></div>
                <svg class="w-10 h-10 text-red-500 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            {{-- Heading --}}
            <div class="space-y-2">
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Read-Only Mode</h2>
                <span class="text-[11px] font-black text-red-600 uppercase tracking-widest bg-red-50 px-3 py-1 rounded-full border border-red-100 inline-block">
                    Action Blocked
                </span>
            </div>

            {{-- Info Box --}}
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-sm text-gray-600 text-left space-y-2">
                <p class="font-bold text-gray-900 text-base">Database is locked.</p>
                <p id="license-locked-message">The system is in READ-ONLY mode. You cannot save, edit, or delete data.</p>
                <p class="text-xs text-gray-400 flex items-center gap-1.5 mt-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Please renew your license to restore full access.
                </p>
            </div>

            {{-- Buttons --}}
            <div class="space-y-3 pt-1">
                @php
                    $vendorPhone = preg_replace('/[^0-9]/', '', config('services.license.vendor_phone', ''));
                @endphp
                <a href="https://wa.me/{{ $vendorPhone }}" target="_blank"
                   class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500
                          text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all duration-300
                          flex items-center justify-center gap-2 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp Support
                </a>
                <button onclick="closeLicenseModal()"
                        class="w-full py-3.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-all duration-300">
                    Cancel / Go Back
                </button>
            </div>
        </div>
    </div>

    <script>
        function openLicenseModal(message) {
            const modal = document.getElementById('license-locked-modal');
            const card  = document.getElementById('license-locked-card');
            if (message) {
                document.getElementById('license-locked-message').textContent = message;
            }
            modal.style.display = 'flex';
            // Trigger entrance animation
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    card.style.transform = 'scale(1) translateY(0)';
                    card.style.opacity   = '1';
                });
            });
        }

        function closeLicenseModal() {
            const modal = document.getElementById('license-locked-modal');
            const card  = document.getElementById('license-locked-card');
            card.style.transform = 'scale(0.9) translateY(16px)';
            card.style.opacity   = '0';
            setTimeout(() => { modal.style.display = 'none'; }, 350);
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLicenseModal();
        });

        // ================================================================
        // GUARANTEED FIX: Intercept fetch() at the network level.
        // Livewire uses window.fetch internally. We override it, detect our
        // 422 license_locked response, show our custom modal, and return a
        // fake 200 to Livewire so it NEVER creates its iframe popup.
        // ================================================================
        (function() {
            const _originalFetch = window.fetch;
            window.fetch = async function(url, options) {
                const response = await _originalFetch.apply(this, arguments);

                // Only intercept Livewire's own update endpoint
                if (typeof url === 'string' && url.includes('/livewire/update')) {
                    if (response.status === 422) {
                        try {
                            const cloned = response.clone();
                            const data = await cloned.json();
                            if (data && data.license_locked === true) {
                                // Show our beautiful custom modal
                                openLicenseModal();
                                // Return a fake successful empty response to Livewire
                                // so it never spawns its white iframe popup
                                return new Response(
                                    JSON.stringify({ components: [], assets: [] }),
                                    { status: 200, headers: { 'Content-Type': 'application/json' } }
                                );
                            }
                        } catch (e) { /* not our JSON, pass through */ }
                    }
                }
                return response;
            };
        })();
        function printPdf(url) {
            let iframe = document.getElementById('pdf-print-iframe');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'pdf-print-iframe';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }
            iframe.src = url;
            iframe.onload = function() {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error("Direct printing failed, opening PDF in new tab:", e);
                    window.open(url, '_blank');
                }
            };
        }
    </script>
    
    <x-security-scripts />
</body>
</html>
