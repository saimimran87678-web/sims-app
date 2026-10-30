<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Student Portal' }} | {{ \App\Models\Setting::getGlobal('institute_name', 'Adminova SIMS') }}</title>

    {{-- Dynamic Favicon Matching SIMS Student Module (Pink Theme + Student Cap Icon) --}}
    @php
        $faviconColor = '%23ec4899'; // Pink (matches SIMS Student module)
        $svgPath = '%3Cpath d="M22 10v6M2 10l10-5 10 5-10 5z"/%3E%3Cpath d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/%3E';
    @endphp
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='{{ $faviconColor }}'/%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' transform='translate%284.2%2C%204.2%29%20scale%280.65%29'%3E{{ $svgPath }}%3C/g%3E%3C/svg%3E">

    <!-- Local Offline-First Fonts (Zero Internet Latency) -->
    <link rel="stylesheet" href="/fonts/inter.css">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 min-h-screen flex flex-col">
    {{-- Top Navigation Bar matching Adminova SIMS --}}
    <header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 border-b border-gray-200 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            {{-- Brand / Logo --}}
            <div class="flex items-center gap-3 min-w-0">
                @php
                    $logoPath = \App\Models\Setting::getGlobal('institute_logo');
                    $instituteName = \App\Models\Setting::getGlobal('institute_name', 'Adminova SIMS');
                    $instituteShortName = \App\Models\Setting::getGlobal('institute_short_name', $instituteName);
                @endphp

                @if($logoPath && file_exists(public_path($logoPath)))
                    <div class="flex items-center justify-center w-10 h-10 shadow-md rounded-xl bg-white border border-gray-100 overflow-hidden p-1 shrink-0">
                        <img src="{{ '/' . $logoPath }}" alt="{{ $instituteName }}" class="w-full h-full object-contain">
                    </div>
                @else
                    <div class="flex items-center justify-center w-10 h-10 text-white shadow-lg rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                @endif

                <div class="truncate">
                    <h1 class="text-base sm:text-lg font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 truncate max-w-[200px] sm:max-w-md" title="{{ $instituteName }}">
                        {{ $instituteShortName }}
                    </h1>
                    <p class="text-[10px] sm:text-xs font-semibold tracking-wider text-gray-400 uppercase">STUDENT & PARENT PORTAL</p>
                </div>
            </div>

            {{-- Right Actions: Active Student Badge & Logout --}}
            <div class="flex items-center gap-2.5 shrink-0">
                @if(session('student_portal_name'))
                    <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-700 shadow-2xs">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="truncate max-w-[150px]">{{ session('student_portal_name') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-all cursor-pointer shadow-2xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-400 mt-auto">
        <p>Powered by <strong class="text-gray-600 font-semibold">Adminova SIMS Enterprise</strong> · Empowering Institutional Excellence</p>
    </footer>

    @livewireScripts
</body>
</html>
