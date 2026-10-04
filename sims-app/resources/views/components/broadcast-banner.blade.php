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
     x-transition:enter-start="opacity-0 -translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-4"
     class="{{ empty($announcement) ? 'hidden' : '' }} mb-6 relative overflow-hidden rounded-2xl border border-indigo-200/90 bg-gradient-to-r from-blue-50/95 via-indigo-50/90 to-purple-50/95 shadow-[0_6px_28px_-6px_rgba(99,102,241,0.15)] backdrop-blur-md transition-all duration-300"
     style="display: {{ empty($announcement) ? 'none' : 'block' }}; padding: 1.15rem 1.4rem;">
    
    {{-- Top Decorative Gradient Accent Line --}}
    <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(to right, #3b82f6, #6366f1, #a855f7);"></div>

    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1.25rem;">
        <div style="display: flex; align-items: flex-start; gap: 1rem; flex: 1; min-width: 0;">
            {{-- Glowing Megaphone Icon --}}
            <div style="position: relative; flex-shrink: 0; margin-top: 0.15rem;">
                <div style="display: flex; width: 2.75rem; height: 2.75rem; align-items: center; justify-content: center; border-radius: 0.85rem; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #9333ea 100%); color: #ffffff; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35); border: 3px solid rgba(224, 231, 255, 0.85);">
                    <svg style="width: 1.35rem; height: 1.35rem;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                {{-- Live Radar Dot pinned to top right --}}
                <span style="position: absolute; top: -4px; right: -4px; display: flex; width: 0.85rem; height: 0.85rem; z-index: 10;">
                    <span class="animate-ping" style="position: absolute; display: inline-flex; height: 100%; width: 100%; border-radius: 9999px; background-color: #34d399; opacity: 0.75;"></span>
                    <span style="position: relative; display: inline-flex; border-radius: 9999px; height: 0.85rem; width: 0.85rem; background-color: #10b981; border: 2px solid #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></span>
                </span>
            </div>

            {{-- Text & Content (with explicit separation from icon) --}}
            <div style="flex: 1; min-width: 0; padding-left: 0.35rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.45rem;">
                    <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                        📢 Adminova Cloud Notice
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 700; color: #047857; background-color: #ecfdf5; padding: 0.2rem 0.6rem; border-radius: 9999px; border: 1px solid #a7f3d0;">
                        <span style="width: 0.45rem; height: 0.45rem; border-radius: 9999px; background-color: #10b981; display: inline-block;"></span>
                        Live Broadcast
                    </span>
                </div>
                <div style="padding-top: 0.15rem;">
                    <p id="sims-broadcast-text" style="font-size: 0.95rem; font-weight: 600; color: #1e293b; line-height: 1.6; margin: 0; word-break: break-word;">
                        {{ $announcement }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Dismiss Button --}}
        <div style="flex-shrink: 0; display: flex; align-items: flex-start; padding-top: 0.15rem;">
            <button type="button"
                    @click="dismiss()"
                    style="padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #64748b; background: rgba(255,255,255,0.75); border-radius: 0.65rem; border: 1px solid rgba(226, 232, 240, 0.9); display: flex; align-items: center; gap: 0.35rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);"
                    onmouseover="this.style.background='#ffffff'; this.style.color='#0f172a'; this.style.borderColor='#cbd5e1';"
                    onmouseout="this.style.background='rgba(255,255,255,0.75)'; this.style.color='#64748b'; this.style.borderColor='rgba(226, 232, 240, 0.9)';"
                    title="Dismiss Announcement">
                <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="hidden sm:inline">Dismiss</span>
            </button>
        </div>
    </div>
</div>
