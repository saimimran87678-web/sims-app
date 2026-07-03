<div class="p-6">
    {{-- Header --}}
    {{-- Directory State (No User Selected) --}}
    @if(!$selectedUserId)
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Staff Directory</h2>
            <p class="text-gray-500 mt-1">Select a staff member to configure their admin privileges.</p>
        </div>

        {{-- Search & Grid --}}
        <div class="mb-6">
            <div class="relative max-w-xl">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search staff by name or email..." 
                    class="block w-full pl-10 pr-3 py-4 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all"
                >
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($filteredUsers as $user)
                <div 
                    wire:click="$set('selectedUserId', {{ $user->id }})"
                    class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-300 cursor-pointer transition-all group flex flex-col items-center text-center"
                >
                    <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold mb-4 group-hover:scale-110 transition-transform">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <h3 class="font-bold text-gray-900 line-clamp-1 w-full">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-1 w-full">{{ $user->email }}</p>
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                        {{ ucfirst($user->getRoleNames()->first() ?? 'Staff') }}
                    </span>
                </div>
            @endforeach

            @if(count($filteredUsers) === 0)
                <div class="col-span-full text-center py-12 text-gray-400">
                    No users found matching "{{ $search }}".
                </div>
            @endif
        </div>

    @else
        {{-- Permission Editor State --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
             <div>
                <button wire:click="$set('selectedUserId', null)" class="flex items-center gap-2 text-gray-500 hover:text-gray-800 transition-colors mb-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Directory
                </button>
                <h2 class="text-3xl font-bold text-gray-800">Permissions: <span class="text-blue-600">{{ $users->find($selectedUserId)->name ?? 'User' }}</span></h2>
            </div>
             
             <div class="flex items-center gap-2">
                 @php $oppositeSession = $this->getOppositeShiftSession(); @endphp
                 @if($oppositeSession && $this->isActiveInOppositeShift)
                     <button 
                         wire:click="syncToOppositeShift"
                         wire:loading.attr="disabled"
                         class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-sm rounded-xl border border-blue-200 transition-all shadow-sm active:scale-95 duration-150"
                     >
                         <svg wire:loading.remove class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                         <svg wire:loading class="animate-spin h-4.5 w-4.5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                         Apply to {{ $oppositeSession->shift_type }} Shift
                     </button>
                 @endif
             </div>
        </div>
        
        @if(session()->has('message'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-2 text-sm font-medium animate-fade-in shadow-sm">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('message') }}
            </div>
        @endif
    @endif

    {{-- Only show Permissions Grid if user selected --}}
    @if($selectedUserId)




        {{-- Permissions Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($permissionsGrouped as $groupName => $group)
                @php
                    $isGroupFullyActive = $group['permissions']->every(fn($p) => in_array($p->name, $userPermissions));
                @endphp
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden group flex flex-col h-full">
                    {{-- Card Header --}}
                    <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex justify-between items-start">
                        <div class="flex items-center gap-3">
                             {{-- Dynamic Icon handling --}}
                             <div class="p-2 {{ $isGroupFullyActive ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }} rounded-lg transition-colors">
                                 @if($group['icon'] == 'clipboard-document-list')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                 @elseif($group['icon'] == 'users')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                 @elseif($group['icon'] == 'academic-cap')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                                 @elseif($group['icon'] == 'calendar')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                 @elseif($group['icon'] == 'chart-bar')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                                 @elseif($group['icon'] == 'key')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                 @elseif($group['icon'] == 'credit-card')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                 @elseif($group['icon'] == 'arrow-path-rounded-square')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3M2.25 12a48.664 48.664 0 01.138-3.662 4.006 4.006 0 013.7-3.7 48.656 48.656 0 017.324 0 4.006 4.006 0 013.7 3.7c.017.22.032.441.046.662M2.25 12l3 3m-3-3l-3 3"></path></svg>
                                 @elseif($group['icon'] == 'adjustments-horizontal')
                                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"></path></svg>
                                 @endif
                             </div>
                             <div>
                                 <h4 class="font-bold text-gray-800">{{ $groupName }}</h4>
                                 <p class="text-xs text-gray-500 line-clamp-1" title="{{ $group['desc'] }}">{{ $group['desc'] }}</p>
                             </div>
                        </div>
                        
                        {{-- Master Toggle --}}
                        <div class="flex items-center">
                            <button 
                                wire:click="toggleGroup('{{ $groupName }}', {{ $isGroupFullyActive ? 'false' : 'true' }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $isGroupFullyActive ? 'bg-blue-600' : 'bg-gray-200' }}"
                            >
                                <span class="sr-only">Toggle Group</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isGroupFullyActive ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Granular Permissions --}}
                    <div class="p-5 bg-white space-y-3 flex-1" x-data="{ expanded: false }">
                        <div class="space-y-3 relative" :class="expanded ? '' : 'max-h-48 overflow-hidden'">
                            @php
                                $childToParentMap = [
                                    'class.create' => 'classes.manage',
                                    'class.edit' => 'classes.manage',
                                    'class.delete' => 'classes.manage',
                                    'subject.create' => 'subjects.manage',
                                    'subject.delete' => 'subjects.manage',
                                    'classes.view-sessions' => 'sessions.manage',
                                    'exam.create' => 'exams.manage',
                                    'exam.edit' => 'exams.manage',
                                    'exam.delete' => 'exams.manage',
                                    'exam.datesheet' => 'exams.manage',
                                    'exams.view-sessions' => 'exams.manage',
                                    'student.create' => 'students.manage',
                                    'student.edit' => 'students.manage',
                                    'student.delete' => 'students.manage',
                                    'students.view-all-classes' => 'students.manage',
                                    'students.view-sessions' => 'students.manage',
                                    'schedule.view' => 'schedule.manage',
                                    'schedule.config' => 'schedule.manage',
                                    'schedule.view-sessions' => 'schedule.manage',
                                ];

                                $permissions = $group['permissions'];
                                $parentPermissions = $permissions->filter(fn($p) => !array_key_exists($p->name, $childToParentMap));
                                $childPermissions = $permissions->filter(fn($p) => array_key_exists($p->name, $childToParentMap));
                            @endphp

                            @foreach($parentPermissions as $parentPerm)
                                @php
                                    $parts = explode('.', $parentPerm->name);
                                    $label = ucfirst(end($parts)) . ' ' . ucfirst($parts[0] ?? '');
                                    $label = str_replace('-', ' ', $label);
                                    if(str_contains($parentPerm->name, 'view-all-classes')) $label = "View All Classes (Global)";
                                    if(str_contains($parentPerm->name, 'sessions.view-all')) $label = "Access All Sessions";
                                    
                                    $children = $childPermissions->filter(fn($cp) => $childToParentMap[$cp->name] === $parentPerm->name);
                                @endphp

                                <div class="space-y-1.5">
                                    {{-- Parent Permission Row --}}
                                    <div class="flex justify-between items-center group/item hover:bg-gray-50 p-1.5 rounded-lg -mx-1.5 transition-colors">
                                        <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                                        <button 
                                            wire:click="togglePermission('{{ $parentPerm->name }}')"
                                            class="text-xs font-bold px-2 py-1 rounded transition-colors {{ in_array($parentPerm->name, $userPermissions) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}"
                                        >
                                            {{ in_array($parentPerm->name, $userPermissions) ? 'Allowed' : 'Denied' }}
                                        </button>
                                    </div>

                                    {{-- Children Indented List --}}
                                    @if($children->isNotEmpty())
                                        <div class="pl-4 ml-1.5 border-l-2 border-gray-100 space-y-1.5 mb-2.5">
                                            @foreach($children as $childPerm)
                                                @php
                                                    $cParts = explode('.', $childPerm->name);
                                                    $cLabel = ucfirst(end($cParts)) . ' ' . ucfirst($cParts[0] ?? '');
                                                    $cLabel = str_replace('-', ' ', $cLabel);
                                                    if(str_contains($childPerm->name, 'view-all-classes')) $cLabel = "View All Classes (Global)";
                                                    if(str_contains($childPerm->name, 'sessions.view-all')) $cLabel = "Access All Sessions";
                                                    if(str_contains($childPerm->name, 'classes.view-sessions')) $cLabel = "View sessions Classes";
                                                @endphp
                                                <div class="flex justify-between items-center group/item hover:bg-gray-50/50 p-1 rounded-md transition-colors">
                                                    <span class="text-xs text-gray-500 font-medium">{{ $cLabel }}</span>
                                                    <button 
                                                        wire:click="togglePermission('{{ $childPerm->name }}')"
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded transition-colors {{ in_array($childPerm->name, $userPermissions) ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-gray-50 text-gray-400 border border-gray-200 hover:bg-gray-100' }}"
                                                    >
                                                        {{ in_array($childPerm->name, $userPermissions) ? 'Allowed' : 'Denied' }}
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Fallback for any children whose parents are not in this group --}}
                            @foreach($childPermissions as $childPerm)
                                @php
                                    $parentName = $childToParentMap[$childPerm->name];
                                    $hasParentInGroup = $permissions->contains('name', $parentName);
                                @endphp
                                @if(!$hasParentInGroup)
                                    @php
                                        $cParts = explode('.', $childPerm->name);
                                        $cLabel = ucfirst(end($cParts)) . ' ' . ucfirst($cParts[0] ?? '');
                                        $cLabel = str_replace('-', ' ', $cLabel);
                                    @endphp
                                    <div class="flex justify-between items-center group/item hover:bg-gray-50 p-1.5 rounded-lg -mx-1.5 transition-colors">
                                        <span class="text-sm text-gray-700">{{ $cLabel }}</span>
                                        <button 
                                            wire:click="togglePermission('{{ $childPerm->name }}')"
                                            class="text-xs font-semibold px-2 py-1 rounded {{ in_array($childPerm->name, $userPermissions) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}"
                                        >
                                            {{ in_array($childPerm->name, $userPermissions) ? 'Allowed' : 'Denied' }}
                                        </button>
                                    </div>
                                @endif
                            @endforeach
                            
                            <div x-show="!expanded && {{ $group['permissions']->count() > 3 ? 'true' : 'false' }}" class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
                        </div>
                        
                        @if($group['permissions']->count() > 3)
                            <button @click="expanded = !expanded" class="w-full text-center text-xs text-blue-600 font-bold hover:underline mt-2">
                                <span x-text="expanded ? 'Show Less' : 'Show All Features'"></span>
                            </button>
                        @endif
                        
                        {{-- Restricted Class Access (For Students, Reports, etc) --}}
                        @if(in_array($groupName, ['Students', 'Reports', 'Gradebook']))
                            <div class="mt-6 pt-5 border-t border-gray-200/60">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h5 class="text-sm font-bold text-gray-800">Restricted Class Access</h5>
                                        <p class="text-xs text-gray-500 font-medium mt-0.5">Classes this user can manage.</p>
                                    </div>
                                     <button wire:click="toggleAllClasses" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors border border-blue-100 hover:border-blue-200 ring-4 ring-transparent active:ring-blue-100">
                                        {{ count($allClasses) === count($userClassAccess) ? 'Deselect All' : 'Select All Classes' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                                   @foreach($allClasses as $class)
                                       @php $hasAccess = in_array($class->id, $userClassAccess); @endphp
                                       <button 
                                           wire:click="toggleClassAccess({{ $class->id }})"
                                           wire:key="class-access-{{ $class->id }}"
                                           class="group flex items-center gap-2 px-3 py-2 rounded-lg border transition-all duration-200 {{ $hasAccess ? 'border-blue-500 bg-blue-50/50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}"
                                       >
                                           <div class="relative flex items-center justify-center w-4 h-4 transition-colors duration-200 {{ $hasAccess ? 'bg-blue-500 border-blue-500' : 'bg-white border-gray-300 border-2 group-hover:border-gray-400' }} rounded text-white shadow-sm">
                                               @if($hasAccess)
                                                   <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                               @endif
                                           </div>
                                           <span class="font-semibold text-xs truncate">{{ $class->name }}</span>
                                       </button>
                                   @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
    @endif
</div>
