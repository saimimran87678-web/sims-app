<div>
@if(count($activeSessions) > 1)
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors border border-gray-200 text-sm font-medium text-gray-700">
            @php
                $currentSession = $activeSessions->firstWhere('id', $currentSessionId);
            @endphp
            
            @if($currentSession && $currentSession->shift_type === 'Evening')
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            @elseif($currentSession && $currentSession->shift_type === 'Morning')
                <span class="w-2 h-2 rounded-full bg-orange-400"></span>
            @else
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            @endif
            
            <span>{{ $currentSession ? $currentSession->shift_type . ' Shift' : 'Shift' }}</span>
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <div x-show="open" @click.away="open = false" x-transition.opacity class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
            <div class="py-1">
                @foreach($activeSessions as $session)
                    <button wire:click="switchSession({{ $session->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 {{ $session->id == $currentSessionId ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
                        @if($session->shift_type === 'Evening')
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        @elseif($session->shift_type === 'Morning')
                            <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        @endif
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
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-100 text-sm font-medium text-gray-500" title="Your assigned shift">
             @if($currentSession->shift_type === 'Evening')
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            @elseif($currentSession->shift_type === 'Morning')
                <span class="w-2 h-2 rounded-full bg-orange-400"></span>
            @else
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            @endif
            <span>{{ $currentSession->shift_type }}</span>
        </div>
    @endif
@endif
</div>
