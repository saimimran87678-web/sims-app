@php
    $notifications = $notifications ?? \App\Services\SystemNotificationService::getActiveNotifications();
    $initialCount = count($notifications);
    $currentUser = auth()->user();
    $isAdmin = !$currentUser || $currentUser->role === 'admin' || (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('Super Admin'));
@endphp

<div x-data="{
        open: false,
        activeTab: 'all',
        rawNotifications: {{ Js::from($notifications) }},
        notifications: [],
        readIds: JSON.parse(localStorage.getItem('sims_read_notifications') || '[]'),
        dismissedIds: JSON.parse(sessionStorage.getItem('sims_dismissed_notifications') || '[]'),
        soundEnabled: localStorage.getItem('sims_notification_sound') !== 'false',
        expandedIds: {},
        activeToast: null,
        toastTimeout: null,
        toastInterval: null,
        toastProgress: 100,
        isHoveredToast: false,

        init() {
            this.processNotifications();

            // Real-time Cloud Push Event Listener
            window.addEventListener('sims-cloud-notification', (e) => {
                this.handleCloudPush(e.detail);
            });

            // Universal App Toast Listener (from Livewire or custom scripts)
            window.addEventListener('sims-toast', (e) => {
                this.showToast(e.detail);
            });

            // Dismiss dropdown on Escape key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                }
            });

            // Livewire notify event listener
            const registerLivewireListener = () => {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('notify', (data) => {
                        const payload = Array.isArray(data) ? (data[0] || {}) : (data || {});
                        this.showToast(payload);
                    });
                }
            };
            registerLivewireListener();
            document.addEventListener('livewire:initialized', registerLivewireListener, { once: true });

            @if(session('message'))
                this.$nextTick(() => {
                    this.showToast({
                        title: 'Success',
                        message: {{ Js::from(session('message')) }},
                        type: 'success',
                        icon_bg: 'bg-emerald-600',
                        badge: 'Success',
                        badge_color: 'bg-emerald-100 text-emerald-900 border-emerald-300'
                    });
                });
            @elseif(session('status'))
                this.$nextTick(() => {
                    this.showToast({
                        title: 'Status Update',
                        message: {{ Js::from(session('status')) }},
                        type: 'info',
                        icon_bg: 'bg-blue-600',
                        badge: 'Notice',
                        badge_color: 'bg-blue-100 text-blue-900 border-blue-300'
                    });
                });
            @elseif(session('error'))
                this.$nextTick(() => {
                    this.showToast({
                        title: 'Attention',
                        message: {{ Js::from(session('error')) }},
                        type: 'alert',
                        icon_bg: 'bg-red-600',
                        badge: 'Alert',
                        badge_color: 'bg-red-100 text-red-900 border-red-300'
                    });
                });
            @endif
        },

        processNotifications() {
            this.notifications = this.rawNotifications
                .filter(n => !this.dismissedIds.includes(n.id))
                .map(n => ({
                    ...n,
                    is_read: this.readIds.includes(n.id)
                }));
        },

        get unreadCount() {
            return this.notifications.filter(n => !n.is_read).length;
        },

        get filteredNotifications() {
            if (this.activeTab === 'unread') {
                return this.notifications.filter(n => !n.is_read);
            }
            if (this.activeTab === 'updates') {
                return this.notifications.filter(n => n.type === 'update');
            }
            if (this.activeTab === 'alerts') {
                return this.notifications.filter(n => n.type === 'license' || n.type === 'alert' || n.type === 'warning');
            }
            return this.notifications;
        },

        countByTab(tab) {
            if (tab === 'unread') return this.notifications.filter(n => !n.is_read).length;
            if (tab === 'updates') return this.notifications.filter(n => n.type === 'update').length;
            if (tab === 'alerts') return this.notifications.filter(n => n.type === 'license' || n.type === 'alert' || n.type === 'warning').length;
            return this.notifications.length;
        },

        toggleRead(id) {
            if (this.readIds.includes(id)) {
                this.readIds = this.readIds.filter(x => x !== id);
            } else {
                this.readIds.push(id);
            }
            localStorage.setItem('sims_read_notifications', JSON.stringify(this.readIds));
            this.processNotifications();
        },

        markAllAsRead() {
            this.notifications.forEach(n => {
                if (!this.readIds.includes(n.id)) {
                    this.readIds.push(n.id);
                }
            });
            localStorage.setItem('sims_read_notifications', JSON.stringify(this.readIds));
            this.processNotifications();
        },

        dismiss(id) {
            if (!this.dismissedIds.includes(id)) {
                this.dismissedIds.push(id);
                sessionStorage.setItem('sims_dismissed_notifications', JSON.stringify(this.dismissedIds));
            }
            this.processNotifications();
        },

        clearAll() {
            this.notifications.forEach(n => {
                if (!this.dismissedIds.includes(n.id)) {
                    this.dismissedIds.push(n.id);
                }
            });
            sessionStorage.setItem('sims_dismissed_notifications', JSON.stringify(this.dismissedIds));
            this.processNotifications();
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            localStorage.setItem('sims_notification_sound', this.soundEnabled ? 'true' : 'false');
            if (this.soundEnabled) {
                this.playChime();
            }
        },

        toggleExpand(id) {
            this.expandedIds[id] = !this.expandedIds[id];
        },

        handleCloudPush(detail) {
            if (!detail || !detail.id) return;
            const existing = this.rawNotifications.find(n => n.id === detail.id);
            if (!existing) {
                this.rawNotifications.unshift(detail);
                this.processNotifications();
                this.showToast(detail);
                this.playChime();
            }
        },

        showToast(data) {
            if (!data || !data.title) return;
            this.activeToast = data;
            this.toastProgress = 100;
            clearInterval(this.toastInterval);
            clearTimeout(this.toastTimeout);

            const duration = 5000;
            const step = 50;
            this.toastInterval = setInterval(() => {
                if (!this.isHoveredToast) {
                    this.toastProgress -= (step / duration) * 100;
                    if (this.toastProgress <= 0) {
                        this.closeToast();
                    }
                }
            }, step);
        },

        closeToast() {
            clearInterval(this.toastInterval);
            clearTimeout(this.toastTimeout);
            this.activeToast = null;
        },

        playChime() {
            if (!this.soundEnabled) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                const now = ctx.currentTime;
                
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();

                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now); // D5
                osc1.frequency.exponentialRampToValueAtTime(880, now + 0.12); // A5

                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now); // A5
                osc2.frequency.exponentialRampToValueAtTime(1174.66, now + 0.15); // D6

                gain.gain.setValueAtTime(0.08, now);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.55);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);

                osc1.start(now);
                osc2.start(now);
                osc1.stop(now + 0.55);
                osc2.stop(now + 0.55);
            } catch(e) {}
        }
     }" 
     class="relative">
    
    <!-- Bell Trigger Button (100% Solid White Surface, High-Contrast Borders, Distinct Badge) -->
    <button @click="open = !open" 
            :aria-expanded="open.toString()"
            aria-haspopup="dialog"
            class="relative flex items-center justify-center w-10 h-10 transition-all duration-150 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-blue-600 shadow-sm cursor-pointer"
            :class="open 
                ? 'bg-blue-600 border-blue-600 text-white shadow-md' 
                : (unreadCount > 0 
                    ? 'bg-white hover:bg-blue-50 border-blue-400 text-blue-700 hover:text-blue-800' 
                    : 'bg-white hover:bg-slate-50 border-slate-300 hover:border-slate-400 text-slate-700 hover:text-slate-900')"
            title="Notifications & System Updates">
        
        <svg xmlns="http://www.w3.org/2000/svg" 
             class="w-5 h-5 transition-transform duration-200" 
             :class="{ '-rotate-12': unreadCount > 0 && !open, 'scale-110': open }" 
             viewBox="0 0 24 24" 
             fill="none" 
             stroke="currentColor" 
             stroke-width="2.2" 
             stroke-linecap="round" 
             stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>

        <!-- Dynamic Unread Badge (Solid High-Contrast Red) -->
        <template x-if="unreadCount > 0">
            <span class="absolute -top-1.5 -right-1.5 flex items-center justify-center">
                <span class="animate-ping absolute inline-flex h-3.5 w-3.5 rounded-full bg-red-400 opacity-75"></span>
                <span class="relative flex h-4 min-w-[1.2rem] px-1 items-center justify-center rounded-full bg-red-600 text-[10px] font-black text-white shadow-md ring-2 ring-white"
                      x-text="unreadCount > 9 ? '9+' : unreadCount">
                </span>
            </span>
        </template>
    </button>

    <!-- Dropdown Panel (100% Solid White Surface, High Contrast, Crisp Enterprise Design) -->
    <div x-show="open" 
         @click.away="open = false" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         style="display: none;"
         class="absolute right-0 mt-2.5 w-[calc(100vw-1.5rem)] sm:w-[30rem] max-w-[30rem] origin-top-right rounded-2xl bg-white shadow-2xl border-2 border-slate-300 z-50 overflow-hidden divide-y divide-slate-200">
        
        <!-- Header Bar -->
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center justify-between gap-2.5">
                <!-- Title & Unread Count Badge -->
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div class="flex items-center gap-2 min-w-0">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-900 leading-tight whitespace-nowrap">Notifications</h4>
                        <template x-if="unreadCount > 0">
                            <span class="bg-blue-600 text-white text-[11px] font-black px-2 py-0.5 rounded-full shadow-2xs whitespace-nowrap shrink-0" x-text="`${unreadCount} New`"></span>
                        </template>
                        <template x-if="unreadCount === 0">
                            <span class="bg-slate-200 text-slate-600 text-[11px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap shrink-0">0 unread</span>
                        </template>
                    </div>
                </div>

                <!-- Header Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Sound Toggle Button -->
                    <button type="button" 
                            @click="toggleSound()" 
                            class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 rounded-lg transition-colors border border-slate-300 shadow-2xs shrink-0 cursor-pointer" 
                            :title="soundEnabled ? 'Chime sound is ON (click to mute)' : 'Chime sound is MUTED (click to enable)'">
                        <template x-if="soundEnabled">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                        </template>
                        <template x-if="!soundEnabled">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                        </template>
                    </button>

                    <!-- Mark All Read (When unread > 0) -->
                    <template x-if="unreadCount > 0">
                        <button type="button" 
                                @click="markAllAsRead()" 
                                class="h-8 px-2.5 inline-flex items-center gap-1.5 text-xs font-bold text-blue-700 hover:text-blue-900 bg-white hover:bg-blue-50 border border-blue-300 hover:border-blue-400 rounded-lg shadow-2xs transition-all cursor-pointer whitespace-nowrap shrink-0">
                            <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span class="whitespace-nowrap">Mark all read</span>
                        </button>
                    </template>

                    <!-- All Caught Up Pill (When unread == 0) -->
                    <template x-if="unreadCount === 0">
                        <span class="h-8 px-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg select-none whitespace-nowrap shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>All read</span>
                        </span>
                    </template>

                    <!-- Close X for mobile / touch -->
                    <button type="button" 
                            @click="open = false" 
                            class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-900 bg-white hover:bg-slate-100 rounded-lg transition-colors border border-slate-300 shadow-2xs sm:hidden cursor-pointer shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Solid Segmented Filter Tabs (High Contrast, Bold Active State, Never Truncates) -->
            <div class="grid {{ $isAdmin ? 'grid-cols-4' : 'grid-cols-3' }} gap-1 mt-3 p-1 bg-slate-200 rounded-xl text-xs font-bold text-slate-700 border border-slate-300">
                <button type="button" 
                        @click="activeTab = 'all'" 
                        :class="activeTab === 'all' 
                            ? 'bg-blue-600 text-white shadow-xs font-black' 
                            : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 font-bold'"
                        class="py-1.5 px-1.5 sm:px-2 rounded-lg text-center transition-all flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap shrink-0">
                    <span>All</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-black shrink-0" 
                          :class="activeTab === 'all' ? 'bg-white text-blue-700' : 'bg-slate-300 text-slate-800'" 
                          x-text="notifications.length"></span>
                </button>

                <button type="button" 
                        @click="activeTab = 'unread'" 
                        :class="activeTab === 'unread' 
                            ? 'bg-blue-600 text-white shadow-xs font-black' 
                            : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 font-bold'"
                        class="py-1.5 px-1.5 sm:px-2 rounded-lg text-center transition-all flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap shrink-0">
                    <span>Unread</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-black shrink-0"
                          :class="activeTab === 'unread' ? 'bg-white text-blue-700' : (unreadCount > 0 ? 'bg-red-600 text-white' : 'bg-slate-300 text-slate-800')"
                          x-text="unreadCount"></span>
                </button>

                @if($isAdmin)
                <button type="button" 
                        @click="activeTab = 'updates'" 
                        :class="activeTab === 'updates' 
                            ? 'bg-blue-600 text-white shadow-xs font-black' 
                            : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 font-bold'"
                        class="py-1.5 px-1.5 sm:px-2 rounded-lg text-center transition-all flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap shrink-0">
                    <span>Updates</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-black shrink-0"
                          :class="activeTab === 'updates' ? 'bg-white text-blue-700' : 'bg-slate-300 text-slate-800'"
                          x-text="countByTab('updates')"></span>
                </button>
                @endif

                <button type="button" 
                        @click="activeTab = 'alerts'" 
                        :class="activeTab === 'alerts' 
                            ? 'bg-blue-600 text-white shadow-xs font-black' 
                            : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 font-bold'"
                        class="py-1.5 px-1.5 sm:px-2 rounded-lg text-center transition-all flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap shrink-0">
                    <span>Alerts</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-black shrink-0"
                          :class="activeTab === 'alerts' ? 'bg-white text-blue-700' : 'bg-slate-300 text-slate-800'"
                          x-text="countByTab('alerts')"></span>
                </button>
            </div>
        </div>

        <!-- Notification Items List (Solid Cards, High Contrast, Distinct Borders & Clear Buttons) -->
        <div class="max-h-80 sm:max-h-96 overflow-y-auto overflow-x-hidden p-3 space-y-3 bg-slate-100 custom-scrollbar">
            <template x-for="item in filteredNotifications" :key="item.id">
                <div class="rounded-xl p-3.5 transition-all relative flex flex-col gap-2.5 border-2 shadow-xs"
                     :class="item.is_read 
                        ? 'bg-white border-slate-300 hover:border-slate-400' 
                        : 'bg-white border-blue-500 shadow-sm border-l-6 border-l-blue-600'">
                    
                    <!-- Card Top Row: Icon + Title + Badges -->
                    <div class="flex items-start gap-3">
                        <!-- Solid High-Contrast Category Icon -->
                        <div :class="item.icon_bg || 'bg-blue-600'" 
                             class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center text-white shadow-sm mt-0.5">
                            <template x-if="item.type === 'update'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </template>
                            <template x-if="item.type === 'broadcast'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </template>
                            <template x-if="item.type === 'license'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </template>
                            <template x-if="!['update', 'broadcast', 'license'].includes(item.type)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </template>
                        </div>

                        <!-- Title and Badges -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2 min-w-0">
                                    <h5 class="text-sm font-black text-slate-900 truncate" x-text="item.title"></h5>
                                    <template x-if="!item.is_read">
                                        <span class="bg-blue-600 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded-full shadow-2xs shrink-0">New</span>
                                    </template>
                                </div>
                                <span :class="item.badge_color || 'bg-slate-100 text-slate-800 border-slate-300'" 
                                      class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md border shrink-0 tracking-wide" 
                                      x-text="item.badge"></span>
                            </div>

                            <!-- Message Content -->
                            <div class="text-xs font-semibold text-slate-700 leading-relaxed mt-1 break-words"
                                 :class="{ 'line-clamp-2': !expandedIds[item.id] }"
                                 x-text="item.message"></div>

                            <template x-if="item.message && item.message.length > 100">
                                <button type="button" 
                                        @click="toggleExpand(item.id)" 
                                        class="text-xs font-bold text-blue-700 hover:text-blue-900 hover:underline mt-1 inline-block focus:outline-none cursor-pointer">
                                    <span x-text="expandedIds[item.id] ? 'Show less ▲' : 'Read full message ▼'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Primary CTA Action (if available) -->
                    <template x-if="item.action_url">
                        <a :href="item.action_url" 
                           class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg shadow-xs hover:shadow transition-all group cursor-pointer mt-1">
                            <span x-text="item.action_label || 'View Details'"></span>
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </template>
                    
                    <!-- Card Bottom Meta: Timestamp & Secondary Actions (Mark Read / Dismiss) -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200">
                        <!-- Timestamp -->
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 font-bold shrink-0">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="item.created_at" class="truncate max-w-[140px] whitespace-nowrap"></span>
                        </div>

                        <!-- Action Controls (Solid, High-Contrast) -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- Toggle Read / Unread Button (Solid, Clearly Labeled) -->
                            <button type="button" 
                                    @click="toggleRead(item.id)" 
                                    class="h-7.5 inline-flex items-center gap-1 px-2.5 text-xs font-bold rounded-lg border transition-all cursor-pointer whitespace-nowrap shrink-0"
                                    :class="item.is_read 
                                        ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300' 
                                        : 'bg-white hover:bg-blue-50 text-blue-700 hover:text-blue-900 border-blue-300 shadow-2xs'"
                                    :title="item.is_read ? 'Mark as unread' : 'Mark as read'">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="item.is_read ? 'Unread' : 'Mark Read'" class="whitespace-nowrap"></span>
                            </button>

                            <!-- Dismiss Button (Solid, Clearly Labeled) -->
                            <button type="button" 
                                    @click="dismiss(item.id)" 
                                    class="h-7.5 inline-flex items-center gap-1 px-2.5 text-xs font-bold text-slate-600 hover:text-red-700 bg-white hover:bg-red-50 border border-slate-300 hover:border-red-300 rounded-lg shadow-2xs transition-all cursor-pointer whitespace-nowrap shrink-0"
                                    title="Dismiss notification">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="whitespace-nowrap">Dismiss</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State Card (Identical Min-Height, High-Contrast Solid Layout) -->
            <template x-if="filteredNotifications.length === 0">
                <div class="min-h-[250px] sm:min-h-[280px] flex flex-col items-center justify-center p-6 text-center bg-white rounded-xl border-2 border-slate-300 shadow-xs">
                    
                    <!-- Sub-Case 1: No notifications at all in the system -->
                    <template x-if="notifications.length === 0">
                        <div class="flex flex-col items-center">
                            <div class="w-13 h-13 rounded-2xl bg-blue-50 border-2 border-blue-200 text-blue-600 flex items-center justify-center mb-3 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <p class="text-sm font-black text-slate-900 tracking-tight">No Notifications Yet</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1 max-w-[280px] leading-relaxed">System updates, alerts, and school announcements will appear here.</p>
                            @if($isAdmin)
                                <a href="{{ route('admin.settings', ['tab' => 'updates'], false) }}#updates-tab" class="mt-4 h-8 px-3.5 inline-flex items-center gap-1.5 text-xs font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-300 rounded-lg shadow-2xs transition-all cursor-pointer whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Check System Updates</span>
                                </a>
                            @endif
                        </div>
                    </template>

                    <!-- Sub-Case 2: Notifications exist, but 0 unread on Unread tab -->
                    <template x-if="notifications.length > 0 && activeTab === 'unread'">
                        <div class="flex flex-col items-center">
                            <div class="w-13 h-13 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center mb-3 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="text-sm font-black text-slate-900 tracking-tight">No Unread Notifications</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1 max-w-[280px] leading-relaxed">You're all caught up! You have <span class="font-bold text-slate-800" x-text="notifications.length"></span> read notifications.</p>
                            <button type="button" @click="activeTab = 'all'" class="mt-4 h-8 px-3.5 inline-flex items-center gap-1.5 text-xs font-bold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-300 rounded-lg shadow-2xs transition-all cursor-pointer whitespace-nowrap">
                                <span>View Read History (<span x-text="notifications.length"></span>)</span> &rarr;
                            </button>
                        </div>
                    </template>

                    <!-- Sub-Case 3: Notifications exist, but current tab (updates/alerts) has 0 items -->
                    <template x-if="notifications.length > 0 && activeTab !== 'unread'">
                        <div class="flex flex-col items-center">
                            <div class="w-13 h-13 rounded-2xl bg-slate-100 border-2 border-slate-200 text-slate-500 flex items-center justify-center mb-3 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            </div>
                            <p class="text-sm font-black text-slate-900 tracking-tight" x-text="activeTab === 'updates' ? 'No System Updates' : 'No Active Alerts'"></p>
                            <p class="text-xs font-semibold text-slate-500 mt-1 max-w-[280px] leading-relaxed" x-text="activeTab === 'updates' ? 'All software updates are currently applied.' : 'No urgent alerts or reminders in this category.'"></p>
                            <button type="button" @click="activeTab = 'all'" class="mt-4 h-8 px-3.5 inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs transition-all cursor-pointer whitespace-nowrap">
                                <span>Switch to All (<span x-text="notifications.length"></span>)</span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Dropdown Footer (Solid, High Contrast) -->
        <div class="px-4 py-3 bg-slate-50 flex items-center justify-between gap-2 text-xs border-t border-slate-200">
            @if($isAdmin)
                <a href="{{ route('admin.settings', ['tab' => 'updates'], false) }}#updates-tab" 
                   class="font-bold text-blue-700 hover:text-blue-900 flex items-center gap-1.5 transition-colors truncate">
                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="truncate">System Updates & Release Manager</span>
                </a>
            @else
                <span class="font-bold text-slate-600 flex items-center gap-1.5 truncate">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="truncate">Notification Center</span>
                </span>
            @endif

            <template x-if="notifications.length > 0">
                <button type="button" 
                        @click="clearAll()" 
                        class="h-7.5 px-3 text-xs font-bold text-slate-700 hover:text-red-700 bg-white hover:bg-red-50 border border-slate-300 hover:border-red-300 rounded-lg transition-colors cursor-pointer shadow-2xs whitespace-nowrap shrink-0">
                    Clear list
                </button>
            </template>
            <template x-if="notifications.length === 0">
                <span class="text-slate-400 font-bold text-[11px] flex items-center gap-1.5 whitespace-nowrap shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Live</span>
                </span>
            </template>
        </div>
    </div>

    <!-- Live Floating Toast Notification HUD (Solid White Card, 100% Opacity, High Contrast) -->
    <div x-show="activeToast !== null"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95 sm:translate-x-4"
         @mouseenter="isHoveredToast = true"
         @mouseleave="isHoveredToast = false"
         class="fixed top-5 right-5 z-[99999] w-96 max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border-2 border-slate-300 shadow-2xl overflow-hidden"
         style="display: none;">
        
        <!-- Animated Countdown Progress Line -->
        <div class="h-1.5 bg-slate-200 w-full overflow-hidden">
            <div class="h-full bg-blue-600 transition-all duration-75"
                 :style="`width: ${toastProgress}%`"></div>
        </div>

        <div class="p-4 flex items-start gap-3">
            <!-- Solid Category Icon -->
            <div :class="activeToast?.icon_bg || 'bg-blue-600'" 
                 class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0 shadow mt-0.5">
                <template x-if="activeToast?.type === 'update'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </template>
                <template x-if="activeToast?.type === 'broadcast'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </template>
                <template x-if="activeToast?.type === 'license'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
                <template x-if="!['update', 'broadcast', 'license'].includes(activeToast?.type)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </template>
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-sm font-extrabold text-slate-900 truncate" x-text="activeToast?.title"></span>
                    <span :class="activeToast?.badge_color || 'bg-slate-100 text-slate-800 border-slate-300'" 
                          class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md border shrink-0 tracking-wide" 
                          x-text="activeToast?.badge"></span>
                </div>
                <p class="text-xs font-semibold text-slate-700 leading-relaxed line-clamp-2" x-text="activeToast?.message"></p>
                
                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-200">
                    <button type="button" 
                            @click="open = true; closeToast()" 
                            class="text-xs font-bold text-blue-700 hover:text-blue-900 flex items-center gap-1 cursor-pointer">
                        <span>Open details</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <template x-if="activeToast?.action_url">
                        <a :href="activeToast?.action_url" 
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-xs cursor-pointer">
                            <span x-text="activeToast?.action_label || 'View'"></span> &rarr;
                        </a>
                    </template>
                </div>
            </div>

            <!-- Close Toast Button -->
            <button type="button" 
                    @click="closeToast()" 
                    class="text-slate-400 hover:text-slate-700 p-1.5 -mr-1 -mt-1 rounded-lg hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200 cursor-pointer"
                    title="Close preview">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</div>
