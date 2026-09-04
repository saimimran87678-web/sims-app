<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} | {{ \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2') }}</title>

    @php
        // Define page-specific colors and SVG icon paths for the tab favicon
        $pageTitleLower = strtolower($title ?? 'dashboard');
        
        // Defaults: Indigo theme with Grid icon
        $faviconColor = '%236366f1'; 
        $svgPath = '%3Crect x="3" y="3" width="7" height="9" rx="1.5"/%3E%3Crect x="14" y="3" width="7" height="5" rx="1.5"/%3E%3Crect x="14" y="12" width="7" height="9" rx="1.5"/%3E%3Crect x="3" y="16" width="7" height="5" rx="1.5"/%3E';
        
        if (str_contains($pageTitleLower, 'user')) {
            $faviconColor = '%238b5cf6'; // Violet
            $svgPath = '%3Cpath d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/%3E%3Ccircle cx="9" cy="7" r="4"/%3E%3Cpath d="M22 21v-2a4 4 0 0 0-3-3.87"/%3E%3Cpath d="M16 3.13a4 4 0 0 1 0 7.75"/%3E';
        } elseif (str_contains($pageTitleLower, 'class')) {
            $faviconColor = '%2310b981'; // Emerald
            $svgPath = '%3Cpath d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/%3E%3Cpath d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/%3E';
        } elseif (str_contains($pageTitleLower, 'student')) {
            $faviconColor = '%23ec4899'; // Pink
            $svgPath = '%3Cpath d="M22 10v6M2 10l10-5 10 5-10 5z"/%3E%3Cpath d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/%3E';
        } elseif (str_contains($pageTitleLower, 'fee')) {
            $faviconColor = '%23f59e0b'; // Amber
            $svgPath = '%3Crect width="20" height="14" x="2" y="5" rx="2"/%3E%3Cline x1="2" x2="22" y1="10" y2="10"/%3E%3Cpath d="M12 14h.01"/%3E%3Cpath d="M16 14h.01"/%3E%3Cpath d="M8 14h.01"/%3E';
        } elseif (str_contains($pageTitleLower, 'attendance')) {
            $faviconColor = '%2314b8a6'; // Teal
            $svgPath = '%3Crect x="3" y="4" width="18" height="18" rx="2" ry="2"/%3E%3Cline x1="16" y1="2" x2="16" y2="6"/%3E%3Cline x1="8" y1="2" x2="8" y2="6"/%3E%3Cline x1="3" y1="10" x2="21" y2="10"/%3E%3Cpath d="m9 16 2 2 4-4"/%3E';
        } elseif (str_contains($pageTitleLower, 'whatsapp')) {
            $faviconColor = '%2310b981'; // Emerald
            $svgPath = '%3Cpath d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/%3E';
        } elseif (str_contains($pageTitleLower, 'communication') || str_contains($pageTitleLower, 'hub')) {
            $faviconColor = '%23a855f7'; // Purple
            $svgPath = '%3Cpath d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/%3E%3Cpath d="M13.73 21a2 2 0 0 1-3.46 0"/%3E';
        } elseif (str_contains($pageTitleLower, 'report')) {
            $faviconColor = '%23f43f5e'; // Rose
            $svgPath = '%3Cpath d="M3 3v18h18"/%3E%3Cpath d="m19 9-5 5-4-4-3 3"/%3E';
        } elseif (str_contains($pageTitleLower, 'exam')) {
            $faviconColor = '%23ef4444'; // Red
            $svgPath = '%3Cpath d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/%3E%3Cpolyline points="14 2 14 8 20 8"/%3E%3Cline x1="16" y1="13" x2="8" y2="13"/%3E%3Cline x1="16" y1="17" x2="8" y2="17"/%3E';
        } elseif (str_contains($pageTitleLower, 'schedule')) {
            $faviconColor = '%2306b6d4'; // Cyan
            $svgPath = '%3Crect x="3" y="4" width="18" height="18" rx="2" ry="2"/%3E%3Cline x1="16" y1="2" x2="16" y2="6"/%3E%3Cline x1="8" y1="2" x2="8" y2="6"/%3E%3Cline x1="3" y1="10" x2="21" y2="10"/%3E';
        } elseif (str_contains($pageTitleLower, 'substitution')) {
            $faviconColor = '%23f97316'; // Orange
            $svgPath = '%3Cpath d="m17 2 4 4-4 4"/%3E%3Cpath d="M3 11v-1a4 4 0 0 1 4-4h14"/%3E%3Cpath d="m7 22-4-4 4-4"/%3E%3Cpath d="M21 13v1a4 4 0 0 1-4 4H3"/%3E';
        } elseif (str_contains($pageTitleLower, 'setting')) {
            $faviconColor = '%2364748b'; // Slate
            $svgPath = '%3Cpath d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/%3E%3Ccircle cx="12" cy="12" r="3"/%3E';
        }
    @endphp
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='{{ $faviconColor }}'/%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' transform='translate%284.2%2C%204.2%29%20scale%280.65%29'%3E{{ $svgPath }}%3C/g%3E%3C/svg%3E">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
<div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden bg-gray-50">
        
        <!-- Sidebar -->
        <aside 
            x-show="sidebarOpen" 
            :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-0 -translate-x-full hidden'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-0"
            class="fixed inset-y-0 left-0 z-50 border-r border-gray-200 bg-white/90 backdrop-blur-xl md:relative flex flex-col h-screen shrink-0 overflow-hidden transition-all duration-300"
        >
            <div class="flex items-center gap-3 p-6 border-b border-gray-100 flex-shrink-0">
                @php
                    $logoPath = \App\Models\Setting::getGlobal('institute_logo');
                @endphp
                @if($logoPath && file_exists(public_path($logoPath)))
                    <div class="flex items-center justify-center w-10 h-10 shadow-md rounded-xl bg-white border border-gray-100 overflow-hidden p-1">
                        <img src="{{ '/' . $logoPath }}" class="w-full h-full object-contain">
                    </div>
                @else
                    <div class="flex items-center justify-center w-10 h-10 text-white shadow-lg rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500">
                        <!-- Graduation Cap + Shield hybrid icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 truncate max-w-[150px]" title="{{ \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2') }}">
                        {{ \App\Models\Setting::getGlobal('institute_short_name', \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2')) }}
                    </h1>
                    <p class="text-xs font-semibold tracking-wider text-gray-400">ADMIN PORTAL</p>
                </div>
            </div>

            <nav class="flex-1 py-6 space-y-1 overflow-y-auto min-h-0">
                <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" color="indigo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    <span class="font-medium">Dashboard</span>
                </x-nav-link>

                <!-- Users -->
                @can('access-control.manage')
                <x-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" color="violet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="font-medium">User Management</span>
                </x-nav-link>
                @endcan

                <!-- Class Management -->
                @can('classes.manage')
                <x-nav-link :href="route('admin.classes')" :active="request()->routeIs('admin.classes')" color="emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span class="font-medium">Class Management</span>
                </x-nav-link>
                @endcan

                <!-- Student Management -->
                @can('students.manage')
                <x-nav-link :href="route('admin.students')" :active="request()->routeIs('admin.students')" color="pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                    <span class="font-medium">Student Management</span>
                </x-nav-link>
                @endcan

                <!-- Fee Management -->
                @if((\App\Services\LicenseStatus::getStatus()['plan'] ?? 'basic') !== 'basic')
                <div x-data="{ feeOpen: {{ request()->routeIs('admin.fee.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button" @click="feeOpen = !feeOpen" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:text-amber-700 hover:bg-amber-50 transition-colors {{ request()->routeIs('admin.fee.*') ? 'bg-amber-50 text-amber-700' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 14h.01"/></svg>
                            <span>Fee Management</span>
                        </div>
                        <svg :class="{'rotate-180': feeOpen}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="feeOpen" x-transition class="pl-11 pr-3 py-2 space-y-1">
                        <a href="{{ route('admin.fee.generator') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-amber-700 hover:bg-amber-50 {{ request()->routeIs('admin.fee.generator') ? 'text-amber-700 bg-amber-50 font-medium' : '' }}">Voucher Management</a>
                        <a href="{{ route('admin.fee.record-payment') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-amber-700 hover:bg-amber-50 {{ request()->routeIs('admin.fee.record-payment') ? 'text-amber-700 bg-amber-50 font-medium' : '' }}">Collect Fees</a>
                        <a href="{{ route('admin.fee.defaulters') }}" class="block px-3 py-2 text-sm text-gray-600 rounded-lg hover:text-amber-700 hover:bg-amber-50 {{ request()->routeIs('admin.fee.defaulters') ? 'text-amber-700 bg-amber-50 font-medium' : '' }}">Defaulter List</a>
                    </div>
                </div>
                @endif

                <!-- Gradebook -->
                @can('classes.manage') <!-- Using classes.manage or grades.manage if existed -->
                <x-nav-link :href="route('admin.grades')" :active="request()->routeIs('admin.grades')" color="indigo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span class="font-medium">Gradebook</span>
                </x-nav-link>
                @endcan

                <!-- Attendance -->
                @can('students.manage')
                <x-nav-link :href="route('admin.attendance')" :active="request()->routeIs('admin.attendance')" color="teal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                    <span class="font-medium">Attendance</span>
                </x-nav-link>
                
                <!-- WhatsApp Setup -->
                <!-- WhatsApp Setup -->
                <x-nav-link :href="route('admin.whatsapp-setup')" :active="request()->routeIs('admin.whatsapp-setup') || request()->routeIs('admin.whatsapp-templates')" color="emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span class="font-medium">WhatsApp Setup</span>
                </x-nav-link>

                <!-- Communication Hub -->
                <x-nav-link :href="route('admin.communication-hub')" :active="request()->routeIs('admin.communication-hub')" color="purple">
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                     <span class="font-medium">Communication Hub</span>
                </x-nav-link>
                @endcan

                 <!-- Reports -->
                @can('reports.view')
                <x-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" color="rose">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span class="font-medium">Reports</span>
                </x-nav-link>
                @endcan

                <!-- Exams -->
                @can('exams.manage')
                <x-nav-link :href="route('admin.exams')" :active="request()->routeIs('admin.exams')" color="red">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="font-medium">Exams</span>
                </x-nav-link>
                @endcan

                <!-- Schedule Management Entry -->
                @can('schedule.manage')
                <x-nav-link :href="route('admin.schedule')" :active="request()->routeIs('admin.schedule') || request()->routeIs('admin.view-schedule') || request()->routeIs('admin.period-config')" color="cyan">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                    <span class="font-medium">Schedule Management</span>
                </x-nav-link>
                
                <x-nav-link :href="route('admin.substitutions')" :active="request()->routeIs('admin.substitutions')" color="orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                    <span class="font-medium">Substitutions & Attendance</span>
                </x-nav-link>
                @endcan



                <!-- Access Control (Shared) -->
                @can('access-control.manage')
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <div class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Access Control
                    </div>
                    
                    <x-nav-link :href="route('admin.feature-sharing')" :active="request()->routeIs('admin.feature-sharing')" color="fuchsia">
                         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                          <span class="font-medium">Feature Sharing</span>
                    </x-nav-link>

                    <x-nav-link :href="route('admin.allocations')" :active="request()->routeIs('admin.allocations')" color="slate">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
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
            <header class="flex items-center justify-between px-4 border-b border-gray-200 h-16 bg-white/50 backdrop-blur-sm md:px-8 z-40">
                <div class="flex items-center gap-4">
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="p-1.5 rounded-lg text-gray-600 hover:text-indigo-600 hover:bg-gray-100 transition-colors focus:outline-none" title="Toggle Sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>

                    <h2 class="hidden sm:block text-xl font-bold text-gray-800"
                        x-data="{ pageTitle: '' }"
                        x-init="pageTitle = document.title.split(' | ')[0] || 'Dashboard'"
                        x-on:livewire:navigated.document="pageTitle = document.title.split(' | ')[0] || 'Dashboard'">
                        <span x-text="pageTitle"></span>
                    </h2>

                    <!-- Session & Shift Selector -->
                    @php
                        $allSessions = \App\Models\AcademicSession::active()->orderBy('start_date', 'desc')->get();
                        $currentSessionId = \App\Models\AcademicSession::getActiveSessionId();
                        
                        $routeName = request()->route() ? request()->route()->getName() : '';
                        $allowedToSeeBoth = in_array($routeName, [
                            'admin.dashboard',
                            'teacher.dashboard',
                            'admin.students',
                            'teacher.students',
                            'teacher.shared.students'
                        ]);
                        
                        if (session('selected_shift_type') === 'both' && !$allowedToSeeBoth && $routeName) {
                            session(['selected_shift_type' => 'morning']);
                        }
                        
                        $currentShift = session('selected_shift_type', 'morning');
                        $currentSessionObj = \App\Models\AcademicSession::find($currentSessionId);
                        $currentSessionIsRegular = ($currentSessionObj && $currentSessionObj->shift_type === 'Regular');
                    @endphp
                    <form action="{{ route('change-session') }}" method="POST" id="session-switch-form" class="inline-flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="academic_session_id" id="nav-session-id-input" value="{{ $currentSessionId }}">
                        <input type="hidden" name="shift_type" id="nav-shift-type-input" value="{{ $currentShift }}">

                        <!-- Session Selector Custom Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="relative flex items-center bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 text-indigo-700 border border-indigo-100 hover:border-indigo-300 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-sm transition-all duration-200 group">
                                <!-- Calendar Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 mr-2 text-indigo-500 group-hover:scale-110 transition-transform">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <span class="mr-1 hidden md:inline text-indigo-800">Session:</span>
                                @php
                                    $selectedSessionObj = $allSessions->firstWhere('id', $currentSessionId);
                                @endphp
                                <span class="font-extrabold pr-4">{{ $selectedSessionObj ? $selectedSessionObj->name : 'Choose Session' }}</span>
                                <svg class="w-3 h-3 text-indigo-500 absolute right-2 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Custom Dropdown Menu -->
                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] py-1.5 border border-gray-100 z-50">
                                @foreach($allSessions as $session)
                                    <button type="button" @click="document.getElementById('nav-session-id-input').value = '{{ $session->id }}'; document.getElementById('session-switch-form').submit();" class="w-full text-left px-4 py-2 text-xs hover:bg-indigo-50 hover:text-indigo-700 flex items-center gap-2 {{ $session->id == $currentSessionId ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-700 font-medium' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $session->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                        <span class="flex-1">{{ $session->name }}</span>
                                        @if($session->is_active)
                                            <span class="text-[9px] font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded-full border border-green-100">Active</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if(!$currentSessionIsRegular)
                        <!-- Shift Selector Custom Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="relative flex items-center bg-gradient-to-r from-purple-50 to-fuchsia-50 hover:from-purple-100 hover:to-fuchsia-100 text-purple-700 border border-purple-100 hover:border-purple-300 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-sm transition-all duration-200 group">
                                <!-- Sun/Moon/Clock Icon based on active shift -->
                                @if($currentShift === 'morning')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 mr-2 text-amber-500 group-hover:rotate-45 transition-transform duration-300">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21M4.978 4.978l1.59 1.59m10.862 10.862 1.59 1.59M3 12h2.25m13.5 0H21M4.978 19.022l1.59-1.59m10.862-10.862 1.59-1.59M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z" />
                                    </svg>
                                @elseif($currentShift === 'evening')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 mr-2 text-indigo-500 group-hover:-translate-y-0.5 transition-transform">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998z" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 mr-2 text-purple-500 group-hover:scale-110 transition-transform">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                    </svg>
                                @endif
                                <span class="mr-1 hidden md:inline text-purple-800">Shift:</span>
                                <span class="font-extrabold pr-4 capitalize">{{ $currentShift === 'both' ? 'Both Shifts' : $currentShift }}</span>
                                <svg class="w-3 h-3 text-purple-500 absolute right-2 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Custom Dropdown Menu -->
                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] py-1.5 border border-gray-100 z-50">
                                <button type="button" @click="document.getElementById('nav-shift-type-input').value = 'morning'; document.getElementById('session-switch-form').submit();" class="w-full text-left px-4 py-2 text-xs hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 {{ $currentShift === 'morning' ? 'bg-purple-50 text-purple-700 font-extrabold' : 'text-gray-700 font-medium' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Morning</span>
                                </button>
                                <button type="button" @click="document.getElementById('nav-shift-type-input').value = 'evening'; document.getElementById('session-switch-form').submit();" class="w-full text-left px-4 py-2 text-xs hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 {{ $currentShift === 'evening' ? 'bg-purple-50 text-purple-700 font-extrabold' : 'text-gray-700 font-medium' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    <span>Evening</span>
                                </button>
                                @if($allowedToSeeBoth)
                                <button type="button" @click="document.getElementById('nav-shift-type-input').value = 'both'; document.getElementById('session-switch-form').submit();" class="w-full text-left px-4 py-2 text-xs hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 {{ $currentShift === 'both' ? 'bg-purple-50 text-purple-700 font-extrabold' : 'text-gray-700 font-medium' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span>Both Shifts</span>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endif
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
                        <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center gap-2 focus:outline-none bg-white p-1 md:pr-3 rounded-full border border-gray-200 hover:border-blue-300 transition-all shadow-sm">
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
            <div class="flex-1 p-4 overflow-y-auto md:p-8 relative custom-scrollbar smooth-scroll-container">
                <!-- Modern color blending mesh background -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none -z-10 select-none">
                    <div class="absolute top-[-15%] left-[-15%] w-[60%] h-[60%] rounded-full bg-gradient-to-tr from-blue-200/20 to-sky-200/30 blur-[130px]"></div>
                    <div class="absolute bottom-[-15%] right-[-15%] w-[70%] h-[70%] rounded-full bg-gradient-to-br from-indigo-200/25 to-violet-200/20 blur-[150px]"></div>
                    <div class="absolute top-[30%] right-[20%] w-[40%] h-[40%] rounded-full bg-pink-100/15 blur-[120px]"></div>
                </div>

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
    
    <!-- Floating Add Student Action Button (shown on Dashboard & Class Management) -->
    @can('students.manage')
        @if(
            in_array($title ?? '', ['Dashboard', 'Class Management']) ||
            request()->routeIs('admin.dashboard') || 
            request()->routeIs('admin.classes') || 
            request()->is('admin/dashboard') || 
            request()->is('admin/classes')
        )
            <div class="fixed bottom-6 right-6 z-[9999]" x-data>
                <button 
                    @click="$dispatch('open-add-student-modal')" 
                    class="flex items-center justify-center w-14 h-14 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white rounded-full shadow-[0_4px_20px_rgba(99,102,241,0.35)] hover:shadow-[0_6px_25px_rgba(99,102,241,0.45)] transition-all duration-300 hover:-translate-y-1 group relative"
                    title="Add New Student"
                >
                    <span class="absolute inset-0 rounded-full bg-indigo-500/20 animate-ping scale-105 group-hover:duration-1000"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
            <livewire:admin.student-manager :only-modal="true" />
        @endif
    @endcan

    @livewireScripts
    <x-security-scripts />
</body>
</html>
