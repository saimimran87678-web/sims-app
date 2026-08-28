<div class="flex items-center gap-2">
    <!-- Session Dropdown -->
    @if(count($activeSessions) > 1)
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors border border-gray-200 text-sm font-medium text-gray-700">
                @php
                    $currentSession = $activeSessions->firstWhere('id', $currentSessionId);
                @endphp
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span>{{ $currentSession ? $currentSession->name : 'Session' }}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div x-show="open" @click.away="open = false" style="display: none;" x-transition.opacity class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
                <div class="py-1">
                    @foreach($activeSessions as $session)
                        <button wire:click="switchSession({{ $session->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 {{ $session->id == $currentSessionId ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            {{ $session->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        @php
            $currentSession = $activeSessions->firstWhere('id', $currentSessionId);
        @endphp
        @if($currentSession)
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-100 text-sm font-medium text-gray-500">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span>{{ $currentSession->name }}</span>
            </div>
        @endif
    @endif

    <!-- Shift Dropdown / Badge -->
    @if(!$currentSessionIsRegular)
        @if($allowedShifts === 'both')
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors border border-gray-200 text-sm font-medium text-gray-700">
                    @if($currentShift === 'evening')
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span>Evening</span>
                    @elseif($currentShift === 'both')
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        <span>Both Shifts</span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                        <span>Morning</span>
                    @endif
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div x-show="open" @click.away="open = false" style="display: none;" x-transition.opacity class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
                    <div class="py-1">
                        <button wire:click="switchShift('morning')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 {{ $currentShift === 'morning' ? 'bg-orange-50 text-orange-700 font-medium' : '' }}">
                            <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                            Morning
                        </button>
                        <button wire:click="switchShift('evening')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 {{ $currentShift === 'evening' ? 'bg-purple-50 text-purple-700 font-medium' : '' }}">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            Evening
                        </button>
                        @if($showBothOption)
                        <button wire:click="switchShift('both')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 {{ $currentShift === 'both' ? 'bg-teal-50 text-teal-700 font-medium' : '' }}">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                            Both Shifts
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-55 border border-gray-200/60 text-sm font-semibold text-gray-600">
                @if($currentShift === 'evening')
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    <span>Evening Shift</span>
                @else
                    <span class="w-2 h-2 rounded-full bg-orange-400 animate-pulse"></span>
                    <span>Morning Shift</span>
                @endif
            </div>
        @endif
    @endif
</div>
