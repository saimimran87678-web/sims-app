<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
            <p class="text-gray-500">Generate detailed records for your class</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-4 border-b border-gray-200">
        <button
            wire:click="setTab('attendance')"
            class="pb-3 px-1 flex items-center gap-2 font-medium transition-colors relative {{ $activeTab === 'attendance' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Attendance Reports
            @if($activeTab === 'attendance')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-t-full"></div>
            @endif
        </button>
        <button
            wire:click="setTab('results')"
            class="pb-3 px-1 flex items-center gap-2 font-medium transition-colors relative {{ $activeTab === 'results' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            Result Generation
            @if($activeTab === 'results')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-t-full"></div>
            @endif
        </button>
    </div>

    {{-- Content --}}
    <div class="min-h-[60vh]">
        @if($activeTab === 'attendance')
            <livewire:teacher.reports.attendance-report />
        @elseif($activeTab === 'results')
            <livewire:teacher.result-report />
        @endif
    </div>
</div>
