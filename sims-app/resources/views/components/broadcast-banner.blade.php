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
     x-transition:enter-start="opacity-0 -translate-y-3 scale-[0.98]"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 -translate-y-3 scale-[0.98]"
     class="{{ empty($announcement) ? 'hidden' : '' }} mb-6 relative overflow-hidden rounded-2xl border-2 border-indigo-200 bg-white shadow-md transition-all duration-300 p-4 sm:p-5"
     style="display: {{ empty($announcement) ? 'none' : 'block' }};">
    
    <!-- Top Gradient Accent Line -->
    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600"></div>

    <div class="flex items-start justify-between gap-3 sm:gap-5">
        <div class="flex items-start gap-3 sm:gap-4 flex-1 min-w-0">
            <!-- Glowing Megaphone Icon with Radar Ping -->
            <div class="relative shrink-0 mt-0.5">
                <div class="flex w-10 h-10 sm:w-11 sm:h-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/25 ring-2 ring-white">
                    <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <!-- Live Radar Dot -->
                <span class="absolute -top-1 -right-1 flex w-3 h-3 z-10">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-white shadow-xs"></span>
                </span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0 pt-0.5">
                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-indigo-100 text-indigo-800 border border-indigo-200/80 shadow-2xs">
                        📢 Adminova Cloud Notice
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                        Live Announcement
                    </span>
                </div>
                
                <p id="sims-broadcast-text" class="text-sm font-semibold text-slate-800 leading-relaxed word-break-break-word">
                    {{ $announcement }}
                </p>
            </div>
        </div>

        <!-- Dismiss Button -->
        <div class="shrink-0 pt-0.5">
            <button type="button"
                    @click="dismiss()"
                    class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg shadow-2xs hover:shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    title="Dismiss Announcement">
                <svg class="w-3.5 h-3.5 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="hidden sm:inline">Dismiss</span>
            </button>
        </div>
    </div>
</div>
