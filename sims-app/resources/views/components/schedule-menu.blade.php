<div x-data="{ menuOpen: false }" class="relative inline-block">
    <button @click="menuOpen = !menuOpen" class="flex items-center justify-center w-10 h-10 text-gray-600 transition-colors bg-white rounded-lg hover:bg-gray-50 border border-gray-200 shadow-sm" title="Schedule Menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
    </button>
    <div x-show="menuOpen" @click.away="menuOpen = false" class="absolute left-0 z-50 w-64 mt-2 bg-white rounded-xl shadow-xl border border-gray-100 py-2 origin-top-left" style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100">
        <div class="px-4 py-2 border-b border-gray-100 mb-1">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Schedule Modules</p>
        </div>
        <a href="{{ auth()->user()->hasRole('Teacher') ? route('teacher.shared.schedule') : route('admin.schedule') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors hover:bg-gray-50 {{ request()->routeIs('*.schedule') ? 'text-blue-600 bg-blue-50' : 'text-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            Schedule Editor
        </a>

        @can('schedule.view')
        <a href="{{ auth()->user()->hasRole('Teacher') ? route('teacher.shared.schedule-view') : route('admin.view-schedule') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors hover:bg-gray-50 {{ request()->routeIs('*.view-schedule') || request()->routeIs('*.schedule-view') ? 'text-blue-600 bg-blue-50' : 'text-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            View Schedule
        </a>
        @endcan

        @can('schedule.config')
        <a href="{{ auth()->user()->hasRole('Teacher') ? route('teacher.shared.period-config') : route('admin.period-config') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors hover:bg-gray-50 {{ request()->routeIs('*.period-config') ? 'text-blue-600 bg-blue-50' : 'text-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6"/><path d="m8.5 3.5 2 3.5m3 6 2 3.5"/><path d="M3.5 8.5 7 10.5m10 3 3.5 2"/><path d="M1 12h6m6 0h6"/><path d="m3.5 15.5 3.5-2m10-3 3.5-2"/><path d="m8.5 20.5 2-3.5m3-6 2-3.5"/></svg>
            Period Configuration
        </a>
        @endcan
    </div>
</div>
