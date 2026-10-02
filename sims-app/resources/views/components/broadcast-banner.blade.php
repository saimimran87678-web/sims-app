@php
    $announcement = \App\Services\LicenseStatus::getBroadcastAnnouncement();
    $announcementHash = !empty($announcement) ? substr(md5($announcement), 0, 10) : '';
@endphp

<div id="sims-broadcast-banner"
     x-data="{
        dismissed: sessionStorage.getItem('sims_dismissed_announcement') === '{{ $announcementHash }}',
        dismiss() {
            this.dismissed = true;
            sessionStorage.setItem('sims_dismissed_announcement', '{{ $announcementHash }}');
        }
     }"
     x-show="!dismissed"
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="opacity-0 -translate-y-4 scale-98"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 -translate-y-4 scale-98"
     class="{{ empty($announcement) ? 'hidden' : '' }} mb-6 relative overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-r from-blue-50/90 via-indigo-50/80 to-purple-50/90 p-4 md:p-5 shadow-[0_4px_24px_-4px_rgba(99,102,241,0.12)] backdrop-blur-md transition-all duration-300"
     style="display: {{ empty($announcement) ? 'none' : 'block' }};">
    
    {{-- Top Decorative Gradient Accent Line --}}
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>

    <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-3.5 md:gap-4 flex-1">
            {{-- Glowing Megaphone Icon --}}
            <div class="relative shrink-0 mt-0.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/25 ring-4 ring-indigo-100/50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                {{-- Live Radar Dot --}}
                <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white shadow-sm"></span>
                </span>
            </div>

            {{-- Text & Content --}}
            <div class="space-y-1 flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100/90 text-indigo-800 border border-indigo-200/80 shadow-xs">
                        📢 Adminova Cloud Notice
                    </span>
                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Live Broadcast
                    </span>
                </div>
                <p id="sims-broadcast-text" class="text-sm font-semibold text-slate-800 leading-relaxed pt-0.5 break-words">
                    {{ $announcement }}
                </p>
            </div>
        </div>

        {{-- Dismiss Button --}}
        <div class="shrink-0 flex items-center">
            <button type="button"
                    @click="dismiss()"
                    class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-white/80 rounded-xl transition-all duration-200 border border-transparent hover:border-slate-200 shadow-xs flex items-center gap-1"
                    title="Dismiss Announcement">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="hidden sm:inline">Dismiss</span>
            </button>
        </div>
    </div>
</div>
