<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.settings') }}" class="p-2 -ml-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors" title="Back to Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Academic Sessions</h1>
            </div>
            <p class="text-gray-500 ml-10">Manage school academic years</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="runAutoUpdate" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors shadow-lg shadow-purple-200">
                Auto-Generate Current
            </button>
            <button wire:click="create" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                + New Session
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($sessions as $session)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $session->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $session->start_date }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $session->end_date }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($session->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                Current Active
                            </span>
                        @elseif(\Carbon\Carbon::parse($session->start_date)->isFuture())
                             <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                Upcoming
                            </span>
                        @elseif(\Carbon\Carbon::parse($session->end_date)->isPast())
                             <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                Completed
                            </span>
                        @else
                             <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="edit({{ $session->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                        <button wire:click="delete({{ $session->id }})" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?') || event.stopImmediatePropagation()">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full text-left align-bottom transition-all transform bg-white shadow-xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-lg">
                <div class="px-6 py-6 bg-white sm:p-8">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modal-title">
                        {{ $sessionId ? 'Edit Session' : 'Create Session' }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session Name</label>
                            <input type="text" wire:model="name" placeholder="e.g. 2025-2026" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                @error('start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">End Date</label>
                                <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                @error('end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="is_active" id="is_active_session" class="rounded text-blue-600 focus:ring-blue-500">
                            <label for="is_active_session" class="ml-2 text-sm text-gray-700">Set as Active Session</label>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 flex flex-row-reverse sm:px-6 gap-2">
                    <button wire:click="store" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-xl shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save
                    </button>
                    <button wire:click="closeModal" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
