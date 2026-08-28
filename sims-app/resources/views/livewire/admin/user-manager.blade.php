<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-500">Manage admins and teachers</p>
        </div>
        <button
            wire:click="create"
            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 shadow-lg shadow-blue-200"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" x2="20" y1="8" y2="14"/><line x1="23" x2="17" y1="11" y2="11"/></svg>
            Add New User
        </button>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 flex items-center gap-2 animate-in fade-in slide-in-from-top-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700 flex items-center gap-2 animate-in fade-in slide-in-from-top-2">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="glass-card p-4 rounded-2xl flex items-center gap-4">
        <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search users by name or email..."
                class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20 text-gray-700"
            />
        </div>
    </div>

    {{-- Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Class</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-blue-700 font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        {{ $user->name }}
                                        @if(!($user->session_is_active ?? false))
                                            <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded bg-red-100 text-red-800 border border-red-200">
                                                Disabled
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                         <td class="px-6 py-4 text-sm">
                            @if($user->session_class_id)
                                <div class="mb-1">
                                    <span class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs font-semibold">Class Teacher</span>
                                    <span class="font-medium text-gray-700 ml-1">{{ $user->class_name ?? 'Class '.$user->session_class_id }}@if(!$currentSessionIsRegular && !empty($user->class_shift_type)) ({{ ucfirst($user->class_shift_type) }})@endif</span>
                                    @if($user->session_class_subject)
                                        <span class="text-gray-500 text-xs ml-1">({{ $user->session_class_subject }})</span>
                                    @endif
                                </div>
                            @endif
                            @php $allocs = $userAllocations[$user->id] ?? collect([]); @endphp
                            @if($allocs->count() > 0)
                                <div class="text-xs text-gray-500">
                                    <span class="font-medium text-gray-600">Subjects:</span>
                                    @foreach($allocs as $a)
                                        <span class="ml-1">{{ $a->subject }} ({{ $a->class }}@if(!$currentSessionIsRegular && !empty($a->class_shift_type)) - {{ ucfirst($a->class_shift_type) }}@endif)@if(!$loop->last),@endif</span>
                                    @endforeach
                                </div>
                            @endif
                            @if(!$user->session_class_id && $allocs->isEmpty())
                                <span class="text-gray-400">-</span>
                            @endif
                            @if($user->role === 'teacher' && !$currentSessionIsRegular)
                                <div class="mt-1.5 flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Shifts:</span>
                                    @if(($user->session_allowed_shifts ?? 'both') === 'both')
                                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-teal-50 text-teal-700 border border-teal-100/70">Both Shifts</span>
                                    @elseif($user->session_allowed_shifts === 'morning')
                                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-orange-50 text-orange-700 border border-orange-100/70">Morning Only</span>
                                    @elseif($user->session_allowed_shifts === 'evening')
                                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-100/70">Evening Only</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-3 transition-transform hover:scale-110" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                             @if($user->id !== auth()->id() && $user->id !== 1)
                                <button
                                    wire:click="toggleAccountStatus({{ $user->id }})"
                                    class="{{ ($user->session_is_active ?? false) ? 'text-orange-500 hover:text-orange-700' : 'text-green-600 hover:text-green-800' }} mr-3 transition-transform hover:scale-110"
                                    title="{{ ($user->session_is_active ?? false) ? 'Disable Account' : 'Enable Account' }}"
                                >
                                    @if($user->session_is_active ?? false)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                    @endif
                                </button>
                                <button
                                    wire:click="delete({{ $user->id }})"
                                    wire:confirm="Are you sure you want to delete this user?"
                                    class="text-red-600 hover:text-red-900 transition-transform hover:scale-110"
                                    title="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    @teleport('body')
    <div class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal Panel --}}
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl md:max-w-2xl sm:w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                <form wire:submit="store">
                    {{-- Modal Body Content --}}
                    <div class="px-6 pt-6 pb-6 space-y-5">
                        {{-- Modal Header --}}
                        <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2" id="modal-title">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                {{ $isEditMode ? 'Edit User details' : 'Add New User' }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-full transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Form Inputs --}}
                        <div class="space-y-5">
                            {{-- Two Column Core Info --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Name --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Full Name</label>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" wire:model="name" placeholder="John Doe" class="block w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-sm text-gray-700 placeholder-gray-400 transition-all shadow-sm" />
                                    </div>
                                    @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Email Address</label>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="email" wire:model="email" autocomplete="off" placeholder="john.doe@example.com" class="block w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-sm text-gray-700 placeholder-gray-400 transition-all shadow-sm" />
                                    </div>
                                    @error('email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>

                                {{-- Password --}}
                                <div x-data="{ showPassword: false }">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center justify-between">
                                        <span>Password</span>
                                        @if($isEditMode) <span class="text-[10px] font-medium text-gray-400 normal-case">(Blank keeps current)</span> @endif
                                    </label>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input :type="showPassword ? 'text' : 'password'" wire:model="password" autocomplete="new-password" placeholder="••••••••" class="block w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-sm text-gray-700 placeholder-gray-400 transition-all shadow-sm" />
                                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>

                                {{-- Role --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Role</label>
                                    <div x-data="{ open: false, selected: @entangle('role').live }" class="relative mt-1">
                                        <button type="button" @click="open = !open" @click.away="open = false" 
                                            :class="open ? 'ring-2 ring-blue-500/20 border-blue-500' : 'border-gray-200'"
                                            class="relative w-full pl-10 pr-10 py-2.5 border rounded-xl bg-white text-left outline-none text-sm text-gray-700 transition-all shadow-sm flex items-center justify-between">
                                            <span class="flex items-center gap-2">
                                                <span x-text="selected === 'admin' ? 'Admin' : 'Teacher'"></span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <div x-show="open" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-[1001] mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-lg py-1.5 text-sm text-gray-700">
                                            <button type="button" @click="selected = 'teacher'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                <span>Teacher</span>
                                                <svg x-show="selected === 'teacher'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="selected = 'admin'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                <span>Admin</span>
                                                <svg x-show="selected === 'admin'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    @error('role') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Teacher Details Section --}}
                            @if($role === 'teacher')
                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 mt-4 space-y-4 animate-in fade-in duration-200">
                                <div class="flex items-center gap-2 border-b border-gray-200/60 pb-3 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.168.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Teacher Configurations & Subject Allocations</h4>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Class Teacher --}}
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">Class Teacher For</label>
                                        <div x-data="{ open: false, selected: @entangle('class_id').live }" class="relative mt-1">
                                            <button type="button" @click="open = !open" @click.away="open = false"
                                                :class="open ? 'ring-2 ring-blue-500/20 border-blue-500' : 'border-gray-200'"
                                                class="relative w-full pl-9 pr-10 py-2.5 border rounded-xl bg-white text-left outline-none text-sm text-gray-700 transition-all shadow-sm flex items-center justify-between">
                                                <span class="truncate">
                                                    @php
                                                        $selectedClass = $classes->firstWhere('id', $class_id);
                                                    @endphp
                                                    {{ $selectedClass ? $selectedClass->name . ($currentSessionIsRegular ? '' : ' (' . ucfirst($selectedClass->shift_type) . ')') : 'No Class Assigned (Not a Class Teacher)' }}
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div x-show="open" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-[1001] mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-lg py-1.5 text-sm text-gray-700 max-h-60 overflow-y-auto animate-in fade-in duration-100">
                                                <button type="button" @click="selected = ''; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                    <span>No Class Assigned (Not a Class Teacher)</span>
                                                    <svg x-show="!selected" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                @foreach($classes as $class)
                                                    <button type="button" @click="selected = '{{ $class->id }}'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                        <span>{{ $class->name }}@if(!$currentSessionIsRegular) ({{ ucfirst($class->shift_type) }})@endif</span>
                                                        <svg x-show="selected == '{{ $class->id }}'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        @error('class_id') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Allowed Shifts --}}
                                    @if(!$currentSessionIsRegular)
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">Allowed Shifts</label>
                                        <div x-data="{ open: false, selected: @entangle('allowed_shifts').live }" class="relative mt-1">
                                            <button type="button" @click="open = !open" @click.away="open = false"
                                                :class="open ? 'ring-2 ring-blue-500/20 border-blue-500' : 'border-gray-200'"
                                                class="relative w-full pl-9 pr-10 py-2.5 border rounded-xl bg-white text-left outline-none text-sm text-gray-700 transition-all shadow-sm flex items-center justify-between">
                                                <span class="truncate">
                                                    <span x-show="selected === 'morning'">Morning Shift Only</span>
                                                    <span x-show="selected === 'evening'">Evening Shift Only</span>
                                                    <span x-show="selected === 'both'">Both Shifts (Morning & Evening)</span>
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div x-show="open" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-[1001] mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-lg py-1.5 text-sm text-gray-700 max-h-60 overflow-y-auto animate-in fade-in duration-100">
                                                <button type="button" @click="selected = 'both'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                    <span>Both Shifts (Morning & Evening)</span>
                                                    <svg x-show="selected === 'both'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="selected = 'morning'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                    <span>Morning Shift Only</span>
                                                    <svg x-show="selected === 'morning'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="selected = 'evening'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                    <span>Evening Shift Only</span>
                                                    <svg x-show="selected === 'evening'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        @error('allowed_shifts') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    
                                    {{-- Class Subject --}}
                                    @if($class_id)
                                    <div class="col-span-1 md:col-span-2">
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">Class Subject</label>
                                        <div x-data="{ open: false, selected: @entangle('class_subject').live }" class="relative mt-1">
                                            <button type="button" @click="open = !open" @click.away="open = false"
                                                :class="open ? 'ring-2 ring-blue-500/20 border-blue-500' : 'border-gray-200'"
                                                class="relative w-full pl-9 pr-10 py-2.5 border rounded-xl bg-white text-left outline-none text-sm text-gray-700 transition-all shadow-sm flex items-center justify-between">
                                                <span class="truncate" x-text="selected ? selected : 'Select Subject'"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.168.477 4.5 1.253" />
                                                </svg>
                                            </div>
                                            <div x-show="open" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-[1001] mt-1.5 w-full bg-white border border-gray-100 rounded-xl shadow-lg py-1.5 text-sm text-gray-700 max-h-60 overflow-y-auto animate-in fade-in duration-100">
                                                <button type="button" @click="selected = ''; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                    <span>Select Subject</span>
                                                    <svg x-show="!selected" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                @foreach($classTeacherSubjects as $sub)
                                                    <button type="button" @click="selected = '{{ $sub->name }}'; open = false" class="w-full text-left px-4 py-2 hover:bg-blue-50/50 hover:text-blue-600 font-medium transition-colors flex items-center justify-between">
                                                        <span>{{ $sub->name }}</span>
                                                        <svg x-show="selected === '{{ $sub->name }}'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        @error('class_subject') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                </div>

                                {{-- Subject Allocations Repeater --}}
                                <div class="space-y-3 mt-4">
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">Subject-wise Teaching Assignments</label>
                                    
                                    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                        @forelse($teachingAssignments as $index => $assignment)
                                            <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-sm relative group hover:border-blue-300 transition-all animate-in slide-in-from-top-1" wire:key="assignment-{{ $index }}">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pr-8">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Class</label>
                                                        <div class="relative">
                                                            <select wire:model.live="teachingAssignments.{{ $index }}.class_id" class="block w-full pl-3 pr-8 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white outline-none appearance-none shadow-sm">
                                                                <option value="">Select Class</option>
                                                                @foreach($classes as $c)
                                                                    <option value="{{ $c->id }}">{{ $c->name }}@if(!$currentSessionIsRegular) ({{ ucfirst($c->shift_type) }})@endif</option>
                                                                @endforeach
                                                            </select>
                                                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        @error("teachingAssignments.{$index}.class_id") <span class="text-red-500 text-[10px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Subject</label>
                                                        <div class="relative">
                                                            <select wire:model.live="teachingAssignments.{{ $index }}.subject_id" class="block w-full pl-3 pr-8 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white outline-none appearance-none disabled:bg-gray-50 disabled:text-gray-400 shadow-sm" {{ empty($assignment['class_id']) ? 'disabled' : '' }}>
                                                                <option value="">Select Subject</option>
                                                                @if(!empty($assignment['subjects']))
                                                                    @foreach($assignment['subjects'] as $sub)
                                                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        @error("teachingAssignments.{$index}.subject_id") <span class="text-red-500 text-[10px] mt-0.5 block font-medium">{{ $message }}</span> @enderror
                                                    </div>
                                                </div>
                                                
                                                <button type="button" wire:click="removeAssignment({{ $index }})" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-600 p-1.5 hover:bg-red-50 rounded-lg transition-all" title="Remove Assignment">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @empty
                                            <div class="text-center py-4 border border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                                                <p class="text-xs text-gray-400 font-medium">No subject assignments. Add one below if they teach specific classes/subjects.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    
                                    <button type="button" wire:click="addAssignment" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 mt-1 border border-dashed border-blue-300 text-blue-600 hover:text-blue-700 hover:bg-blue-50/40 hover:border-blue-400 rounded-xl text-xs font-bold transition-all active:scale-95 duration-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Subject Assignment
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100 rounded-b-2xl">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-semibold text-sm active:scale-95 duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all font-semibold text-sm shadow-md shadow-blue-200 active:scale-95 duration-150 flex items-center gap-2">
                            <span>{{ $isEditMode ? 'Update User' : 'Create User' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    {{-- PIN Modal --}}
    @if($isPinModalOpen)
    @teleport('body')
    <div class="fixed inset-0 z-[1000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closePinModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <form wire:submit.prevent="verifyPin">
                    <div class="bg-white px-6 pt-6 pb-6 rounded-t-2xl">
                        <div class="flex flex-col items-center justify-center text-center">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 shadow-inner">
                                <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                Security Verification Required
                            </h3>
                            <p class="text-sm text-gray-500 mt-2">
                                You are about to modify an Admin account. Please confirm your identity.
                            </p>
                        </div>
                        
                        <div class="mt-6">
                            @if(!$usePasswordForPin)
                                <label class="block text-sm font-medium text-gray-700 text-center">Enter Admin Action PIN</label>
                                <div class="mt-2 flex justify-center">
                                    <input type="password" wire:model.defer="pin" class="block w-48 text-center text-2xl tracking-widest rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="••••" autofocus />
                                </div>
                            @else
                                <label class="block text-sm font-medium text-gray-700 text-center">Enter Your Account Password</label>
                                <div class="mt-2 flex justify-center">
                                    <input type="password" wire:model.defer="pin" class="block w-64 rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Password" autofocus />
                                </div>
                            @endif
                            @error('pin') <span class="text-red-500 text-sm font-medium mt-2 block text-center">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-4 text-center">
                            @if(!$usePasswordForPin)
                                <button type="button" wire:click="$set('usePasswordForPin', true)" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Forgot PIN? Use Password
                                </button>
                            @else
                                <button type="button" wire:click="$set('usePasswordForPin', false)" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Use Admin Action PIN instead
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex justify-center gap-3">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-3 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                            Confirm Action
                        </button>
                        <button type="button" wire:click="closePinModal" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>
