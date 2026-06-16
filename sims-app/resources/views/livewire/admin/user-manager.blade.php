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
                                        @if(!($user->session_is_active ?? true))
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
                            @if($user->class_id)
                                <div class="mb-1">
                                    <span class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs font-semibold">Class Teacher</span>
                                    <span class="font-medium text-gray-700 ml-1">{{ $user->class_name ?? 'Class '.$user->class_id }}</span>
                                    @if($user->class_subject)
                                        <span class="text-gray-500 text-xs ml-1">({{ $user->class_subject }})</span>
                                    @endif
                                </div>
                            @endif
                            @php $allocs = $userAllocations[$user->id] ?? collect([]); @endphp
                            @if($allocs->count() > 0)
                                <div class="text-xs text-gray-500">
                                    <span class="font-medium text-gray-600">Subjects:</span>
                                    @foreach($allocs as $a)
                                        <span class="ml-1">{{ $a->subject }} ({{ $a->class }})@if(!$loop->last),@endif</span>
                                    @endforeach
                                </div>
                            @endif
                            @if(!$user->class_id && $allocs->isEmpty())
                                <span class="text-gray-400">-</span>
                            @endif
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <button wire:click="edit({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-3 transition-transform hover:scale-110" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            @if($user->id !== auth()->id())
                <button
                    wire:click="toggleAccountStatus({{ $user->id }})"
                    class="{{ ($user->session_is_active ?? true) ? 'text-orange-500 hover:text-orange-700' : 'text-green-600 hover:text-green-800' }} mr-3 transition-transform hover:scale-110"
                    title="{{ ($user->session_is_active ?? true) ? 'Disable Account' : 'Enable Account' }}"
                >
                    @if($user->session_is_active ?? true)
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
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit="store">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            {{ $isEditMode ? 'Edit User' : 'Add New User' }}
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" wire:model="email" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div x-data="{ showPassword: false }">
                                <label class="block text-sm font-medium text-gray-700">
                                    Password
                                    @if($isEditMode) <span class="text-xs font-normal text-gray-500">(Leave blank to keep current)</span> @endif
                                </label>
                                <div class="relative mt-1">
                                    <input :type="showPassword ? 'text' : 'password'" wire:model="password" autocomplete="new-password" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 pr-10" />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Role</label>
                                    <select wire:model.live="role" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option value="teacher">Teacher</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    @error('role') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                @if($role === 'teacher')
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-4">
                                    {{-- Class Teacher --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Class Teacher For (Optional)</label>
                                        <select wire:model.live="class_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <option value="">No Class Assigned</option>
                                            @foreach($classes as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[10px] text-gray-400 mt-1">Assign if this teacher is a class teacher.</p>
                                        @error('class_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    {{-- Class Subject (New) --}}
                                    @if($class_id)
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Class Subject (Optional)</label>
                                        <select wire:model="class_subject" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <option value="">Select Subject</option>
                                            @foreach($classTeacherSubjects as $sub)
                                                <option value="{{ $sub->name }}">{{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[10px] text-gray-400 mt-1">Subject taught to their class (if Class Teacher).</p>
                                        @error('class_subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    @endif

                                    {{-- Subject Assignments --}}
                                    <div>
                                         <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Subject Assignments</label>
                                         <div class="space-y-2">
                                             @foreach($teachingAssignments as $index => $assignment)
                                                <div class="flex gap-2 items-start" wire:key="assignment-{{ $index }}">
                                                    <div class="flex-1">
                                                        <select wire:model.live="teachingAssignments.{{ $index }}.class_id" class="block w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500">
                                                            <option value="">Select Class</option>
                                                            @foreach($classes as $c)
                                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error("teachingAssignments.{$index}.class_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="flex-1">
                                                        <select wire:model="teachingAssignments.{{ $index }}.subject_id" class="block w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500" {{ empty($assignment['class_id']) ? 'disabled' : '' }}>
                                                            <option value="">Select Subject</option>
                                                            @if(!empty($assignment['subjects']))
                                                                @foreach($assignment['subjects'] as $sub)
                                                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                         @error("teachingAssignments.{$index}.subject_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </div>
                                                    <button type="button" wire:click="removeAssignment({{ $index }})" class="text-red-500 hover:text-red-700 p-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                                                    </button>
                                                </div>
                                             @endforeach
                                         </div>
                                         <button type="button" wire:click="addAssignment" class="mt-2 text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center gap-1">
                                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="16"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                                             Add Subject Assignment
                                         </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $isEditMode ? 'Update User' : 'Create User' }}
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
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
