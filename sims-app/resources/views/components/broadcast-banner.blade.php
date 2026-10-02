@php
    $announcement = \App\Services\LicenseStatus::getBroadcastAnnouncement();
@endphp

<div id="sims-broadcast-banner"
     class="{{ empty($announcement) ? 'hidden' : '' }} mb-6 relative overflow-hidden rounded-2xl border border-indigo-200/90 bg-gradient-to-r from-blue-50/95 via-indigo-50/95 to-violet-50/95 p-4 shadow-sm transition-all duration-300"
     style="display: {{ empty($announcement) ? 'none' : 'block' }};">
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-3.5">
            {{-- Pulsing Announcement Icon --}}
            <div class="relative shrink-0 mt-0.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-600 border-2 border-white"></span>
                </span>
            </div>

            {{-- Text Content --}}
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                        Adminova Cloud Notice
                    </span>
                    <span class="text-xs text-indigo-400 font-medium hidden sm:inline">• Live Cloud Announcement</span>
                </div>
                <p id="sims-broadcast-text" class="text-sm font-semibold text-gray-800 leading-relaxed pt-0.5">
                    {{ $announcement }}
                </p>
            </div>
        </div>

        {{-- Dismiss Button --}}
        <button type="button"
                onclick="document.getElementById('sims-broadcast-banner').style.display='none'"
                class="shrink-0 p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-white/80 transition-colors"
                title="Dismiss Notice">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
