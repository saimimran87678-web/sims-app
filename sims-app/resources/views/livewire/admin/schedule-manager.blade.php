<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div class="flex items-start gap-4">
            <x-schedule-menu />
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Schedule Management</h1>
                <p class="text-gray-500">Assign teachers to classes for each period</p>
            </div>
        </div>
        @can('schedule.view-sessions')
        <div class="relative w-40">
             <div class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
             </div>
             <select 
                wire:model.live="selectedSessionId" 
                class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 text-gray-700 text-sm appearance-none cursor-pointer shadow-sm"
            >
                @foreach($academicSessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }} @if($session->is_active) (Current) @endif</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
           </div>
        </div>
        @endrole
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700">{{ session('message') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Day Tabs --}}
    <div class="flex flex-wrap gap-2 justify-between items-center border-b border-gray-200 pb-3">
        <div class="flex gap-2 items-center">
            <button
                wire:click="$set('selectedDay', 'Everyday')"
                class="px-4 py-2 rounded-t-lg font-medium transition-colors {{ $selectedDay === 'Everyday' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
            >
                Everyday
            </button>
            <span class="text-gray-300">|</span>
            @foreach($days as $day)
                <button
                    wire:click="$set('selectedDay', '{{ $day }}')"
                    class="px-4 py-2 rounded-t-lg font-medium transition-colors {{ $selectedDay === $day ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ substr($day, 0, 3) }}
                </button>
            @endforeach
            <label class="flex items-center gap-1.5 ml-2 cursor-pointer">
                <input type="checkbox" wire:model.live="includeSaturday" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="text-sm text-gray-600">+Sat</span>
            </label>
        </div>
        <div class="flex gap-2">
            <button
                wire:click="copyToAllDays"
                wire:confirm="Copy {{ $selectedDay }}'s schedule to all other weekdays? This will replace existing schedules."
                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                Copy to All
            </button>
            <button
                wire:click="clearDay"
                wire:confirm="Clear all schedule entries for {{ $selectedDay }}?"
                class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Clear Day
            </button>
            {{-- View Schedule button removed (moved to sidebar) --}}
        </div>
    </div>

    {{-- Schedule Grid --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-32 sticky left-0 bg-gray-50">Class</th>
                        @foreach($periods as $period)
                            <th class="px-2 py-3 text-center text-xs font-medium {{ $period->is_break ? 'bg-yellow-50 text-yellow-700' : ($period->is_assembly ? 'bg-purple-50 text-purple-700' : 'text-gray-500') }} min-w-[140px]">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($classes as $class)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-sm font-bold text-gray-800 sticky left-0 bg-white">{{ $class->name }}</td>
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-2 py-3 bg-yellow-50/50 text-center">
                                        <span class="text-yellow-600 text-xs">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-2 py-3 bg-purple-50/50 text-center">
                                        <span class="text-purple-600 text-xs">Assembly</span>
                                    </td>
                                @else
                                    @php $schedule = $this->getSchedule($class->id, $period->period_no); @endphp
                                    <td
                                        wire:click="openModal({{ $class->id }}, {{ $period->period_no }})"
                                        class="px-2 py-2 cursor-pointer hover:bg-blue-50 transition-colors border-l border-gray-100"
                                    >
                                        @if($schedule)
                                            @php
                                                $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                $subject = \App\Models\Subject::find($schedule->subject_id);
                                            @endphp
                                            <div class="text-xs space-y-0.5">
                                                <div class="font-bold text-blue-700 truncate">{{ $subject->name ?? '-' }}</div>
                                                <div class="text-gray-500 truncate">{{ $teacher->name ?? '-' }}</div>
                                                @if($schedule->is_divided)
                                                    <span class="text-[10px] text-purple-600 bg-purple-50 px-1 rounded">Divided</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-center text-gray-300 text-xs py-2">+ Assign</div>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Assignment Modal --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Assign Period</h2>
                        <p class="text-sm text-gray-500">
                            {{ $classes->firstWhere('id', $modalClassId)?->name ?? '' }} • 
                            @if($selectedDay === 'Everyday')
                                <span class="text-green-600 font-medium">All Days</span>
                            @else
                                {{ $selectedDay }}
                            @endif
                            • {{ $modalPeriodLabel }}
                        </p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Main Assignment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                        <select wire:model.live="selectedTeacherId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select Teacher</option>
                            @foreach($availableTeachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Only shows teachers not busy this period</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <select wire:model.live="selectedSubjectId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select Subject</option>
                            @foreach($availableSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Excludes subjects already assigned today</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <input type="text" wire:model="room" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Room/Lab" />
                    </div>

                    {{-- Apply to All Days --}}
                    @if($selectedDay === 'Everyday')
                        <div class="bg-green-50 p-3 rounded-xl">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm font-medium text-green-700">Everyday Mode Active</span>
                            </div>
                            <p class="text-xs text-green-600 ml-7">This assignment will be applied to all days ({{ implode(', ', $days) }})</p>
                        </div>
                    @else
                        <div class="bg-blue-50 p-3 rounded-xl">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="applyToAllDays" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                <span class="text-sm font-medium text-blue-700">Apply to all days</span>
                            </label>
                            <p class="text-xs text-blue-600 ml-6">{{ $applyToAllDays ? 'Will assign to: ' . implode(', ', $days) : 'Assign this schedule to everyday (Mon-Fri' . ($includeSaturday ? '+Sat' : '') . ')' }}</p>
                        </div>
                    @endif

                    {{-- Divided Class --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isDivided" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-700">Divided Class (2 Teachers)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">For split groups like Bio/Computer students</p>
                    </div>

                    @if($isDivided)
                        <div class="bg-purple-50 p-4 rounded-xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-purple-700 mb-1">Teacher 2</label>
                                <select wire:model="selectedTeacherId2" class="w-full px-4 py-2 rounded-xl border border-purple-200 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                                    <option value="">Select Second Teacher</option>
                                    @foreach($availableTeachers as $teacher)
                                        @if($teacher->id != $selectedTeacherId)
                                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-purple-700 mb-1">Subject 2</label>
                                <select wire:model="selectedSubjectId2" class="w-full px-4 py-2 rounded-xl border border-purple-200 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                                    <option value="">Select Second Subject</option>
                                    @foreach($availableSubjects2 as $subject)
                                        @if($subject->id != $selectedSubjectId)
                                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Substitute --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isSubstitute" class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500" />
                            <span class="text-sm font-medium text-gray-700">Add Substitute (Single Day)</span>
                        </label>
                    </div>

                    @if($isSubstitute)
                        <div class="bg-orange-50 p-4 rounded-xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-orange-700 mb-1">Date</label>
                                <input type="date" wire:model="substituteDate" class="w-full px-4 py-2 rounded-xl border border-orange-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-orange-700 mb-1">Substitute Teacher</label>
                                <select wire:model="substituteTeacherId" class="w-full px-4 py-2 rounded-xl border border-orange-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white">
                                    <option value="">Select Substitute</option>
                                    @foreach($availableSubstituteTeachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-orange-600 mt-1">Only shows teachers free this period</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="save" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium">
                        {{ $editingId ? 'Update' : 'Assign' }}
                    </button>
                    @if($editingId)
                        <button wire:click="delete" wire:confirm="Remove this assignment?" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium">
                            Delete
                        </button>
                    @endif
                    <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
