<div class="py-6 space-y-6" wire:poll.2s="checkReaderStatus">
    {{-- Header & Device Status Bar --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Smart Card Manager & Provisioner</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Issue, write, and inspect cryptographic RFID cards for students</p>
                </div>
            </div>
        </div>

        {{-- Hardware Status Badge --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    @if($isReaderOnline)
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-400"></span>
                    @endif
                </span>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <span>Desk ({{ $deskDeviceId }}):</span>
                    @if($isReaderOnline)
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">ONLINE</span>
                        @if($readerHwStatus === 'ok')
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                RC-522 {{ $readerHwVersion ? $readerHwVersion : 'Ready' }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300">
                                RC-522 Wiring Error
                            </span>
                        @endif
                    @else
                        <span class="text-amber-600 dark:text-amber-400 font-bold">STANDBY</span>
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-2 border-l pl-3 dark:border-gray-600">
                <a href="{{ route('admin.attendance') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &larr; Daily Register
                </a>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <a href="{{ route('admin.attendance.gate-monitor') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <span>Gate Monitor</span> &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session()->has('success_message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success_message') }}</span>
            </div>
        </div>
    @endif
    @if (session()->has('error_message'))
        <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error_message') }}</span>
        </div>
    @endif
    @if (session()->has('warning_message'))
        <div class="p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 rounded-xl text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('warning_message') }}</span>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700">
        <button wire:click="setTab('writer')" class="py-3 px-5 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'writer' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Card Provisioning & Writer Station
        </button>
        <button wire:click="setTab('inspector')" class="py-3 px-5 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'inspector' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Card Inspector & Anti-Tamper Check
        </button>
    </div>

    @if($activeTab === 'writer')
        {{-- WRITER STATION PANEL --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left 2 Columns: Student Selection Table --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Filters --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-3">
                        <select wire:model.live="selectedClassId" class="text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 pl-3.5 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm">
                            <option value="">All Classes</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="statusFilter" class="text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 pl-3.5 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm">
                            <option value="all">All Students</option>
                            <option value="unassigned">Needs Card (Unassigned)</option>
                            <option value="active">Active Cards</option>
                            <option value="lost">Lost / Suspended</option>
                        </select>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, roll, UID..." class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white pl-9 pr-4 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 font-semibold border-b border-gray-200 dark:border-gray-700 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Roll #</th>
                                <th class="p-3">Student Name</th>
                                <th class="p-3">Class</th>
                                <th class="p-3">RFID Tag (UID)</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($students as $st)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors {{ $selectedStudentId === $st->id ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : '' }}">
                                    <td class="p-3 font-mono font-bold text-gray-800 dark:text-gray-100">{{ $st->roll_no ?: '-' }}</td>
                                    <td class="p-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $st->name }}
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $st->admission_no }}</div>
                                    </td>
                                    <td class="p-3">{{ $st->class?->name ?? '-' }}</td>
                                    <td class="p-3 font-mono">
                                        @if($st->rfid_tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ $st->rfid_tag }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if($st->card_status === 'active' && $st->rfid_tag)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                        @elseif($st->card_status === 'lost')
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Lost</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right space-x-1">
                                        <button wire:click="selectStudent({{ $st->id }})" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 transition-colors">
                                            Select
                                        </button>
                                        @if($st->rfid_tag)
                                            <button wire:click="revokeCard({{ $st->id }})" wire:confirm="Revoke card from this student?" class="p-1.5 text-gray-400 hover:text-rose-600 rounded" title="Revoke Card">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-400">No students found matching your criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="p-3 border-t border-gray-100 dark:border-gray-700">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>

            {{-- Right Column: Interactive Card Writing Rig --}}
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center justify-between">
                        <span>Card Writer Terminal</span>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-normal lowercase">
                            <input type="checkbox" wire:model.live="autoAdvance" class="rounded text-indigo-600">
                            <span class="text-gray-600 dark:text-gray-300">Auto-advance queue</span>
                        </label>
                    </h3>

                    @if($selectedStudent)
                        <div class="p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/30 dark:bg-indigo-900/10 mb-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Target Student</span>
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">Roll #{{ $selectedStudent->roll_no ?: '-' }}</span>
                            </div>
                            <div class="font-bold text-base text-gray-900 dark:text-white">{{ $selectedStudent->name }}</div>
                            <div class="text-xs text-gray-500">
                                Class: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $selectedStudent->class?->name ?? 'Unassigned' }}</span> |
                                Adm: <span class="font-mono text-gray-700 dark:text-gray-300">{{ $selectedStudent->admission_no }}</span>
                            </div>
                            @if($selectedStudent->rfid_tag)
                                <div class="text-[11px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 p-2 rounded-lg mt-2">
                                    ⚠️ Currently assigned UID: <span class="font-mono font-bold">{{ $selectedStudent->rfid_tag }}</span>. Writing will overwrite with a new card.
                                </div>
                            @endif
                        </div>

                        {{-- Arming Controls --}}
                        @if($isWritingArmed)
                            <div class="p-5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700 rounded-xl text-center space-y-3 animate-pulse">
                                <div class="mx-auto w-12 h-12 bg-emerald-100 dark:bg-emerald-800 text-emerald-600 dark:text-emerald-300 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-emerald-800 dark:text-emerald-200 text-sm">READER ARMED & READY</div>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-300 mt-0.5">TAP BLANK CARD ON DESK READER NOW...</p>
                                </div>
                                <button wire:click="cancelArm" class="px-4 py-1.5 bg-white dark:bg-gray-800 text-xs font-bold text-rose-600 border border-rose-200 dark:border-rose-700 rounded-lg shadow-sm hover:bg-rose-50 transition-colors">
                                    Cancel Arming
                                </button>
                            </div>
                        @else
                            <button wire:click="armWriter" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>ARM READER TO WRITE CARD</span>
                            </button>
                        @endif

                    @else
                        <div class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-gray-400 space-y-2">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                            <p class="text-xs">Select a student from the list to begin card provisioning.</p>
                        </div>
                    @endif

                    {{-- Class Progress Widget --}}
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span>Class Issuance Progress</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $issuedInClass }} / {{ $totalInClass }} cards</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                            @php
                                $pct = $totalInClass > 0 ? round(($issuedInClass / $totalInClass) * 100) : 0;
                            @endphp
                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Hardware Instruction Card --}}
                <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400 space-y-2">
                    <div class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>How Card Writing Works:</span>
                    </div>
                    <p>1. Connect your NodeMCU to USB/power on the local Wi-Fi.</p>
                    <p>2. Select student & click <strong>Arm Reader</strong>.</p>
                    <p>3. Tap a blank MIFARE 1K or NTAG213 card. The reader writes encrypted admission & HMAC anti-tamper blocks and updates SIMS instantly.</p>
                </div>
            </div>
        </div>

    @else
        {{-- CARD INSPECTOR & VERIFIER TAB --}}
        <div class="max-w-2xl mx-auto space-y-6">
            {{-- Hardware Status Banner --}}
            <div class="flex items-center justify-between p-4 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 rounded-2xl text-xs">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        @if($isReaderOnline)
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-400"></span>
                        @endif
                    </span>
                    <div>
                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                            <span>Reader: {{ $deskDeviceId }}</span>
                            @if($isReaderOnline)
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold">Auto-Inspect Armed</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 font-bold">Standby</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            Tap any physical smart card on the reader. The reader reads silicon EEPROM and verifies the cryptographic HMAC seal without marking attendance.
                        </p>
                    </div>
                </div>
                @if($inspectResult)
                    <button wire:click="clearInspectResult" class="px-3 py-1 bg-white dark:bg-gray-700 hover:bg-gray-100 text-gray-600 dark:text-gray-300 font-semibold text-xs rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm transition-colors">
                        Clear
                    </button>
                @endif
            </div>

            {{-- Manual Search Bar (Fallback) --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <div class="flex gap-2">
                    <input type="text" wire:model="inspectCardUid" wire:keydown.enter="inspectCard" placeholder="e.g. A3:F1:28:C9 (or tap card on reader above)..." class="flex-1 text-sm rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 font-mono">
                    <button wire:click="inspectCard" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors">
                        Inspect
                    </button>
                </div>

                @if($inspectResult)
                    <div class="space-y-4 pt-2">
                        {{-- Verdict Banner --}}
                        @if(!empty($inspectResult['is_authentic']))
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <div>
                                        <div class="font-bold text-emerald-900 dark:text-emerald-200 text-sm">AUTHENTIC INSTITUTIONAL SMART CARD</div>
                                        <div class="text-xs text-emerald-700 dark:text-emerald-300">Cryptographic HMAC seal matches student identity and school master key.</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-600 text-white font-mono font-bold text-xs rounded-lg shadow-sm">VERIFIED</span>
                            </div>
                        @elseif(!empty($inspectResult['is_cloned']))
                            <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-300 dark:border-rose-800 rounded-xl space-y-1">
                                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-200 font-bold text-sm">
                                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>SECURITY ALERT: UNSEALED OR CLONED CARD DETECTED</span>
                                </div>
                                <p class="text-xs text-rose-700 dark:text-rose-300">
                                    This card UID is registered in the database, but the physical silicon memory does <strong>NOT</strong> have a valid cryptographic HMAC seal. This may be a cloned or counterfeit card!
                                </p>
                            </div>
                        @elseif(!$inspectResult['is_registered'])
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-300">
                                ⚠️ <strong>Unregistered Card:</strong> This card (UID: <span class="font-mono font-bold">{{ $inspectResult['card_uid'] }}</span>) is not linked to any student. Use the <strong>Writer Station</strong> tab to issue this card.
                            </div>
                        @endif

                        {{-- Side-by-side Audit Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            {{-- Card 1: Physical Silicon Memory --}}
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600 space-y-3">
                                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    <span>Physical Silicon Memory (Sector 1)</span>
                                </div>

                                @if(!empty($inspectResult['on_card_data']))
                                    <div class="space-y-1.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600">
                                            <span class="text-gray-400">Inst Magic:</span>
                                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $inspectResult['on_card_data']['inst_code'] }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600">
                                            <span class="text-gray-400">Student ID:</span>
                                            <span>#{{ $inspectResult['on_card_data']['student_id'] }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600">
                                            <span class="text-gray-400">Roll No:</span>
                                            <span>{{ $inspectResult['on_card_data']['roll_no'] }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600">
                                            <span class="text-gray-400">Admission:</span>
                                            <span>{{ $inspectResult['on_card_data']['admission_no'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">HMAC Seal:</span>
                                            <span class="text-[10px] {{ !empty($inspectResult['hmac_verified']) ? 'text-emerald-600 font-bold' : 'text-rose-500 font-bold' }}">
                                                {{ $inspectResult['on_card_data']['hmac_hex'] }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-center text-xs text-gray-400 font-mono">
                                        No cryptographic sectors found (Blank card / Key A protected)
                                    </div>
                                @endif
                            </div>

                            {{-- Card 2: Database Record Profile --}}
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600 space-y-3">
                                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>SIMS Database Record</span>
                                </div>

                                @if(!empty($inspectResult['student']))
                                    <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-300">
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $inspectResult['student']['name'] }}</div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600 font-mono">
                                            <span class="text-gray-400">Class:</span>
                                            <span>{{ $inspectResult['student']['class_name'] }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600 font-mono">
                                            <span class="text-gray-400">Roll No:</span>
                                            <span>{{ $inspectResult['student']['roll_no'] }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 dark:border-gray-600 font-mono">
                                            <span class="text-gray-400">Card Status:</span>
                                            <span class="font-bold uppercase {{ $inspectResult['student']['card_status'] === 'active' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $inspectResult['student']['card_status'] }}</span>
                                        </div>
                                        <div class="flex justify-between font-mono text-[10px]">
                                            <span class="text-gray-400">Issued At:</span>
                                            <span>{{ $inspectResult['student']['card_issued_at'] ?? 'Unknown' }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-center text-xs text-gray-400">
                                        Card UID not assigned to any student in SIMS
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
