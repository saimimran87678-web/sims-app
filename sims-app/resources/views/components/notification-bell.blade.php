@php
    $notifications = \App\Services\SystemNotificationService::getActiveNotifications();
    $initialCount = count($notifications);
@endphp

<div x-data="{
        open: false,
        notifications: {{ Js::from($notifications) }},
        unreadCount: {{ $initialCount }},
        dismissedIds: JSON.parse(sessionStorage.getItem('sims_dismissed_notifications') || '[]'),
        init() {
            this.filterDismissed();
            window.addEventListener('sims-cloud-notification', (e) => {
                this.handleCloudPush(e.detail);
            });
        },
        filterDismissed() {
            this.notifications = this.notifications.filter(n => !this.dismissedIds.includes(n.id));
            this.unreadCount = this.notifications.length;
        },
        markAllAsRead() {
            this.notifications.forEach(n => {
                if (!this.dismissedIds.includes(n.id)) this.dismissedIds.push(n.id);
            });
            sessionStorage.setItem('sims_dismissed_notifications', JSON.stringify(this.dismissedIds));
            this.unreadCount = 0;
        },
        dismiss(id) {
            if (!this.dismissedIds.includes(id)) {
                this.dismissedIds.push(id);
                sessionStorage.setItem('sims_dismissed_notifications', JSON.stringify(this.dismissedIds));
            }
            this.filterDismissed();
        },
        handleCloudPush(detail) {
            if (detail && detail.id) {
                const exists = this.notifications.find(n => n.id === detail.id);
                if (!exists) {
                    this.notifications.unshift(detail);
                    this.unreadCount = this.notifications.length;
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try {
                            new Notification(detail.title || 'Adminova Notification', {
                                body: detail.message || '',
                                icon: '/favicon.ico'
                            });
                        } catch(e) {}
                    }
                }
            }
        }
     }" 
     class="relative">
    
    <!-- Bell Button -->
    <button @click="open = !open" 
            class="relative flex items-center justify-center w-9 h-9 transition-all duration-200 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-400/40 shadow-sm"
            :class="{ 'bg-indigo-50 text-indigo-600 ring-2 ring-indigo-200': open }"
            title="Notifications & System Updates">
        
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform duration-300" :class="{ '-rotate-12': unreadCount > 0 }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>

        <!-- Dynamic Unread Badge -->
        <template x-if="unreadCount > 0">
            <span class="absolute -top-1 -right-1 flex h-4 min-w-[1rem] px-1 items-center justify-center rounded-full bg-red-500 text-[10px] font-black text-white shadow-sm ring-2 ring-white animate-pulse"
                  x-text="unreadCount">
            </span>
        </template>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" 
         @click.away="open = false" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         style="display: none;"
         class="absolute right-0 mt-2.5 w-80 sm:w-96 rounded-2xl bg-white shadow-[0_12px_45px_rgba(0,0,0,0.15)] border border-gray-100 divide-y divide-gray-100 z-50 overflow-hidden">
        
        <!-- Dropdown Header -->
        <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-indigo-50/40 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h4 class="text-xs font-black uppercase tracking-wider text-gray-800">Notifications & Updates</h4>
                <template x-if="unreadCount > 0">
                    <span class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="`${unreadCount} new`"></span>
                </template>
            </div>
            <template x-if="unreadCount > 0">
                <button @click="markAllAsRead()" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Clear all
                </button>
            </template>
        </div>

        <!-- Notification Items List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 custom-scrollbar">
            <template x-for="item in notifications" :key="item.id">
                <div class="p-3.5 hover:bg-slate-50/80 transition-colors relative flex items-start gap-3">
                    <!-- Icon -->
                    <div :class="item.icon_bg || 'bg-gray-500'" class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center text-white shadow-sm mt-0.5">
                        <template x-if="item.type === 'update'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </template>
                        <template x-if="item.type === 'broadcast'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </template>
                        <template x-if="item.type === 'license'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </template>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-xs font-bold text-gray-900 truncate" x-text="item.title"></span>
                            <span :class="item.badge_color || 'bg-gray-100 text-gray-700'" class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded border" x-text="item.badge"></span>
                        </div>
                        <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed" x-text="item.message"></p>
                        
                        <div class="flex items-center justify-between mt-2 pt-1 border-t border-gray-100">
                            <span class="text-[10px] text-gray-400 font-medium" x-text="item.created_at"></span>
                            <div class="flex items-center gap-2">
                                <template x-if="item.action_url">
                                    <a :href="item.action_url" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-0.5">
                                        <span x-text="item.action_label || 'View'"></span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </template>
                                <button @click="dismiss(item.id)" class="text-[11px] text-gray-400 hover:text-gray-600 font-medium ml-1">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="notifications.length === 0">
                <div class="py-8 px-4 text-center">
                    <div class="w-10 h-10 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-xs font-bold text-gray-800">You're all caught up!</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">No pending updates or announcements.</p>
                </div>
            </template>
        </div>

        <!-- Dropdown Footer -->
        <div class="p-2.5 bg-slate-50/90 text-center">
            <a href="{{ route('admin.settings') }}" class="text-xs font-semibold text-gray-600 hover:text-indigo-600 flex items-center justify-center gap-1.5 transition-colors">
                <span>System Updates & Release Manager</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>
