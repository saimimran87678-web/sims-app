<div class="space-y-6">
    <x-slot name="header">Bulk Student Import</x-slot>

    {{-- File Upload Card --}}
    <div class="glass-card p-6 rounded-2xl border border-gray-100 shadow-sm bg-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-800">CSV Bulk Import</h3>
                <p class="text-xs text-gray-500 mt-0.5">Quickly import students into the system by uploading a CSV file.</p>
            </div>
            
            <div class="w-full md:w-64">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Target Academic Session</label>
                <select wire:model.live="selectedSessionId" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all py-2.5 px-3 text-sm">
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }} @if($session->is_active) (Current) @endif</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Help / Template Box --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-6 text-sm text-blue-900 space-y-3">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h4 class="font-bold text-blue-950">CSV Format Instructions</h4>
                    <p class="text-xs text-blue-800 mt-1">Please ensure your CSV file contains the following headers. The importer will attempt to automatically detect them:</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7 text-xs">
                <div>
                    <h5 class="font-semibold text-blue-950 uppercase tracking-wider text-[10px]">Required Columns:</h5>
                    <ul class="list-disc list-inside space-y-1 text-blue-800 mt-1">
                        <li><strong class="text-blue-950">Name</strong> — The full name of the student</li>
                        <li><strong class="text-blue-950">Class Name</strong> — Must match an existing class (e.g. <code class="bg-blue-100/80 px-1 rounded text-blue-900">Class 9A</code> or just <code class="bg-blue-100/80 px-1 rounded text-blue-900">9A</code>)</li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold text-blue-950 uppercase tracking-wider text-[10px]">Optional Columns:</h5>
                    <ul class="list-disc list-inside space-y-1 text-blue-800 mt-1">
                        <li><strong>Admission No</strong> (Generated if empty)</li>
                        <li><strong>Roll No</strong> (Assigned if provided)</li>
                        <li><strong>Father Name</strong>, <strong>Phone</strong>, <strong>Gender</strong>, <strong>Shift</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Drag-Drop Zone --}}
        <div class="flex items-center justify-center w-full">
            <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-2xl cursor-pointer bg-gray-50 hover:bg-gray-100/50 transition-colors duration-150 relative">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                    <p class="mb-2 text-sm text-gray-500 font-semibold">Click to upload or drag & drop CSV file</p>
                    <p class="text-xs text-gray-400">CSV or Text files up to 2MB</p>
                </div>
                <input type="file" wire:model="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".csv,text/csv,text/plain" />
            </label>
        </div>

        @error('file')
            <div class="mt-3 text-sm text-red-600 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ $message }}
            </div>
        @enderror

        {{-- Loading indicator --}}
        <div wire:loading wire:target="file" class="mt-4 text-sm text-blue-600 font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Uploading and parsing file...
        </div>
    </div>

    {{-- Session Flash Messages --}}
    @if (session()->has('message'))
        <div class="p-4 bg-green-50 border border-green-150 text-green-900 rounded-2xl flex items-center gap-2.5">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-red-50 border border-red-150 text-red-900 rounded-2xl flex items-center gap-2.5">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Preview Card --}}
    @if($showPreview)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Import Preview</h3>
                <p class="text-xs text-gray-500 mt-0.5">Please review the parsed rows before executing the bulk import.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button
                    wire:click="import"
                    class="inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition-all shadow-md active:scale-95 duration-150"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Confirm & Import
                </button>
                <button
                    wire:click="cancel"
                    class="px-4 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all active:scale-95 duration-150"
                >
                    Cancel
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Row</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Student Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Father Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Class (Input)</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Adm No</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Roll No</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Shift</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status / Validation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($previewRows as $row)
                    <tr class="hover:bg-gray-50/50 {{ $row['is_valid'] ? '' : 'bg-red-50/20' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-400">#{{ $row['row_num'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $row['name'] ?: '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $row['father_name'] ?: '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $row['class_id'] ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                {{ $row['class_name_input'] ?: '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-600">{{ $row['admission_no'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">{{ $row['roll_no'] ?: '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-600">{{ $row['shift_type'] }}</td>
                        <td class="px-6 py-4">
                            @if($row['is_valid'])
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-150">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Ready
                                </span>
                            @else
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-150">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Error
                                    </span>
                                    <div class="text-[11px] text-red-600 font-medium list-disc pl-1">
                                        @foreach($row['errors'] as $error)
                                            <div>• {{ $error }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
