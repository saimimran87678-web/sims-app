<div class="h-full flex flex-col space-y-4 p-4">
    {{-- Header / Controls --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-wrap gap-4 justify-between items-center">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-gray-800">Timetable</h1>
            
            <div class="flex bg-gray-100 p-1 rounded-lg">
                <button wire:click="$set('viewMode', 'class')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'class' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    By Class
                </button>
                <button wire:click="$set('viewMode', 'teacher')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'teacher' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    By Teacher
                </button>
            </div>
            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                {{ count($viewMode === 'class' ? $classes : $teachers) }} {{ $viewMode === 'class' ? 'Classes' : 'Teachers' }}
            </span>
        </div>

        <div class="flex items-center gap-4">


            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-600">Template:</label>
                <select wire:model.live="selectedTemplateId" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

             <div class="flex items-center gap-2 border-l pl-4 border-gray-200">
                 <button wire:click="deleteAllEntries" 
                         wire:confirm="Are you sure you want to delete ALL timetable entries for this schedule template? This action cannot be undone."
                         class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-white hover:text-red-700 hover:border-red-300 transition-all shadow-sm">
                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                     <span>Delete All</span>
                 </button>
                 <button wire:click="editTimings" 
                         class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-white hover:text-blue-700 hover:border-blue-300 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Edit Timings</span>
                 </button>
                 <a href="{{ route('admin.timetable.print.master', ['mode' => $viewMode, 'day' => $selectedDay]) }}" target="_blank" class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-white hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <span>Print Master</span>
                 </a>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            <span class="font-medium">Success!</span> {{ session('message') }}
        </div>
    @endif

    {{-- Schedule Grid --}}
    <div class="flex-1 overflow-auto bg-white rounded-xl shadow-sm border border-gray-200 relative">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                <tr>
                    <th class="p-3 font-bold text-gray-600 border-b border-gray-200 min-w-[150px] sticky left-0 bg-gray-50 z-20">
                        {{ $viewMode === 'class' ? 'Class' : 'Teacher' }}
                    </th>
                    @foreach($periods as $period)
                        <th class="p-3 font-bold text-gray-600 border-b border-gray-200 text-center min-w-[140px] {{ $period->is_break ? 'bg-orange-50 text-orange-700' : '' }}">
                            <div class="flex flex-col">
                                <span>{{ $period->label ?? 'Period ' . $period->period_no }}</span>
                                <span class="text-[10px] font-normal text-gray-400">
                                    {{ $period->start_time?->format('h:i') }} - {{ $period->end_time?->format('h:i') }}
                                </span>
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php 
                    $rows = $viewMode === 'class' ? $classes : $teachers;
                    $skipCells = [];
                @endphp
                
                @foreach($rows as $rowIndex => $row)
                <tr class="hover:bg-gray-50/50" wire:key="row-{{ $viewMode }}-{{ $row->id }}">
                    <td class="p-3 font-medium text-gray-900 sticky left-0 bg-white border-r border-gray-100 z-10 w-48 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                        <div class="flex justify-between items-center group">
                            <span>{{ $row->name }}</span>
                            <a href="{{ $viewMode === 'class' ? route('admin.timetable.print.class', $row->id) : route('admin.timetable.print.teacher', $row->id) }}" 
                               target="_blank"
                               class="text-gray-300 hover:text-blue-600 opacity-0 group-hover:opacity-100 transition-all p-1"
                               title="Print Timetable">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            </a>
                        </div>
                    </td>
                    
                    @foreach($periods as $period)
                    @if(isset($skipCells[$row->id][$period->period_no]))
                        @continue
                    @endif
                    @php
                        $data = isset($this) ? $this->getCellData($row->id, $selectedDay, $period->period_no) : collect();
                        $rowspan = 1;
                        if ($viewMode === 'class' && $data->isNotEmpty() && $data->whereNotNull('merged_class_id')->isNotEmpty()) {
                            $currentIds = $data->pluck('id')->sort()->values()->toJson();
                            for ($nextIndex = $rowIndex + 1; $nextIndex < count($rows); $nextIndex++) {
                                $nextRow = $rows[$nextIndex];
                                $nextData = $this->getCellData($nextRow->id, $selectedDay, $period->period_no);
                                $nextIds = $nextData->pluck('id')->sort()->values()->toJson();
                                if ($currentIds === $nextIds && $currentIds !== '[]') {
                                    $rowspan++;
                                    $skipCells[$nextRow->id][$period->period_no] = true;
                                } else {
                                    break;
                                }
                            }
                        }
                    @endphp
                    <td rowspan="{{ $rowspan }}" class="p-1 border-l border-gray-100 align-top {{ $period->is_break ? 'bg-orange-50/30' : '' }} {{ $rowspan > 1 ? 'relative z-10' : '' }}" wire:key="cell-{{ $viewMode }}-{{ $row->id }}-{{ $period->period_no }}">
                        @if($period->is_break || $period->is_assembly)
                            <div class="h-full min-h-[4rem] flex items-center justify-center text-xs text-gray-400 italic">
                                {{ $period->label }}
                            </div>
                        @else
                            <div 
                                wire:click="openModal('{{ $selectedDay }}', {{ $period->period_no }}, {{ $row->id }})"
                                class="h-full w-full min-h-[4rem] rounded-md p-1.5 cursor-pointer transition-all relative overflow-hidden hover:ring-2 hover:ring-blue-100 {{ $data->isNotEmpty() ? 'bg-blue-50 border border-blue-100' : 'hover:bg-gray-50' }}"
                                style="{{ $rowspan > 1 ? 'background-color: #f1f5f9; border: 2px dashed #93c5fd;' : '' }}"
                            >
                                @if($period->period_no == 0)
                                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-[0.03] text-gray-900 font-black uppercase text-[1.5rem] tracking-widest whitespace-nowrap -rotate-[15deg]">
                                        Assembly
                                    </div>
                                    <div class="absolute top-[2px] right=[2px] text-[9px] text-gray-400 font-semibold italic w-full text-right pr-1 pointer-events-none">Assembly</div>
                                @endif
                                
                                @if($data->isNotEmpty())
                                    <div class="flex flex-col h-full justify-center space-y-1 relative z-10">
                                        @foreach($data as $entry)
                                        <div class="border-b border-blue-200/50 pb-1 last:border-0 last:pb-0">
                                            <div class="font-medium text-xs text-blue-700 truncate" title="{{ $subjects[$entry->subject_id]->name ?? '?' }}">
                                                {{ $subjects[$entry->subject_id]->name ?? '?' }}
                                                @if($entry->merged_class_id && $rowspan == 1)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-medium bg-purple-100 text-purple-800 ml-1">
                                                        Merged w/ {{ $classesById[$entry->merged_class_id == $row->id ? $entry->class_id : $entry->merged_class_id]->name ?? '?' }}
                                                    </span>
                                                @elseif($entry->merged_class_id && $rowspan > 1)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-medium bg-purple-100 text-purple-800 ml-1">
                                                        Merged
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-gray-500 truncate mt-0.5 flex justify-between">
                                                <span>{{ $viewMode === 'class' ? ($teachersById[$entry->teacher_id]->name ?? '?') : ($classesById[$entry->class_id]->name ?? '?') }}</span>
                                                @if($entry->room)
                                                    <span class="text-[9px] text-gray-400">{{ $entry->room }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="h-full flex items-center justify-center group">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Editor Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="showModal = false">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">{{ $modalTitle }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 space-y-6 max-h-[60vh]">
                @foreach($entries as $index => $entry)
                <div class="p-4 border border-gray-200 rounded-lg bg-white relative shadow-sm">
                    @if(count($entries) > 1)
                    <button wire:click="removeEntry({{ $index }})" 
                            wire:confirm="Are you sure you want to remove this timetable entry?"
                            class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 hover:bg-red-200 shadow-sm border border-red-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @endif
                    
                    <div class="space-y-4">
                        @if($viewMode === 'class')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                                <select wire:model="entries.{{ $index }}.teacher_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Teacher --</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                @error('entries.'.$index.'.teacher_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                <select wire:model.live="entries.{{ $index }}.class_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Class --</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('entries.'.$index.'.class_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <select wire:model="entries.{{ $index }}.subject_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Subject --</option>
                                    @if($viewMode === 'class')
                                        @foreach(($subjectsByClass[$entry['class_id']] ?? collect()) as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(($subjectsByClass[$entry['class_id']] ?? collect()) as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('entries.'.$index.'.subject_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Room (Optional)</label>
                                <input type="text" wire:model="entries.{{ $index }}.room" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. Lab 1">
                            </div>
                        </div>
                        
                        @if($viewMode === 'class')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Merge With Class (Optional)</label>
                            <select wire:model="entries.{{ $index }}.merged_class_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- None --</option>
                                @foreach($classes as $c)
                                    @if($c->id != $entry['class_id'])
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <span class="text-xs text-gray-400">If selected, the teacher will teach both classes simultaneously in this period.</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                
                <button wire:click="addEntry" class="w-full py-2 border border-dashed border-blue-300 rounded-lg text-blue-800 font-medium bg-blue-100 flex items-center justify-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add another Teacher/Subject (Divide Class)
                </button>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" wire:model="syncAllDays" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Sync across all days (Mon – Sat)</span>
                </label>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" wire:click="clearPeriod" wire:confirm="Are you sure you want to clear this period? This will remove all assignments for this period." class="px-3 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        Clear Period
                    </button>
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="save" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Timings Modal --}}
    @if($showTimingsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="showTimingsModal = false">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Edit Period Timings</h3>
                <button wire:click="$set('showTimingsModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <p class="text-sm text-gray-600 mb-4">Set the start and end times for each period in the current schedule template.</p>
                
                @foreach($periodTimings as $index => $pt)
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <div class="font-medium text-gray-700 w-24">{{ $pt['label'] }}</div>
                    
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Start Time</label>
                        <input type="time" wire:model="periodTimings.{{ $index }}.start_time" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('periodTimings.'.$index.'.start_time') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    
                    <span class="text-gray-400 mt-5">-</span>
                    
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">End Time</label>
                        <input type="time" wire:model="periodTimings.{{ $index }}.end_time" class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('periodTimings.'.$index.'.end_time') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="$set('showTimingsModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button wire:click="saveTimings" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Timings
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
