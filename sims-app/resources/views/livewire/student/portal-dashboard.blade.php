<div class="space-y-5 sm:space-y-6 smooth-scroll-container">
    {{-- 1. FAMILY SIBLING SWITCHER BAR (If multiple siblings enrolled under same Father/Guardian) --}}
    @if($familySiblings->count() > 1)
        <div class="glass-card p-3.5 sm:p-4 rounded-2xl border-l-4 border-l-indigo-600 bg-gradient-to-r from-indigo-50/80 via-purple-50/40 to-white shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-sm shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-gray-800">Family Unit (Enrolled Siblings)</h2>
                        <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Switch student profile to view other siblings enrolled in the institution:</p>
                    </div>
                </div>

                {{-- Sibling Selector Pills --}}
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($familySiblings as $sibling)
                        @php
                            $sibStatus = strtolower($sibling->status ?? 'active');
                            $isSibGrad = in_array($sibStatus, ['graduated', 'passed_out']);
                            $isSibSlc = in_array($sibStatus, ['withdrawn', 'slc_issued']);
                            $isSibActive = ($sibStatus === 'active');
                        @endphp
                        <button 
                            wire:click="switchChild({{ $sibling->id }})"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 cursor-pointer {{ $student->id === $sibling->id ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30 ring-2 ring-indigo-300 font-bold' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 shadow-2xs' }}"
                        >
                            <span class="w-2 h-2 rounded-full 
                                @if($student->id === $sibling->id) bg-white 
                                @elseif($isSibActive) bg-emerald-500 
                                @elseif($isSibGrad) bg-purple-500 
                                @elseif($isSibSlc) bg-amber-500 
                                @else bg-gray-400 @endif">
                            </span>
                            <span class="truncate max-w-[120px] sm:max-w-none">{{ $sibling->name }}</span>
                            @if($isSibGrad)
                                <span class="text-[10px] px-1.5 py-0.2 rounded font-bold {{ $student->id === $sibling->id ? 'bg-indigo-500 text-white' : 'bg-purple-100 text-purple-700' }}">
                                    Graduated
                                </span>
                            @elseif($isSibSlc)
                                <span class="text-[10px] px-1.5 py-0.2 rounded font-bold {{ $student->id === $sibling->id ? 'bg-indigo-500 text-white' : 'bg-amber-100 text-amber-800' }}">
                                    SLC Issued
                                </span>
                            @elseif(!$isSibActive)
                                <span class="text-[10px] px-1.5 py-0.2 rounded font-bold {{ $student->id === $sibling->id ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($sibling->status) }}
                                </span>
                            @else
                                <span class="text-[10px] {{ $student->id === $sibling->id ? 'text-indigo-200' : 'text-gray-400' }}">({{ $sibling->class->name ?? 'Class' }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($isDeparted)
        {{-- DEPARTED / ALUMNI NOTICE SCREEN (ACTIVE DATA WITHHELD) --}}
        <div class="glass-card p-6 sm:p-8 rounded-3xl border shadow-sm text-center max-w-3xl mx-auto my-6 
            {{ $isGraduated ? 'bg-gradient-to-b from-indigo-50/70 via-white to-purple-50/40 border-indigo-200' : ($isSlc ? 'bg-gradient-to-b from-amber-50/70 via-white to-orange-50/40 border-amber-200' : 'bg-gradient-to-b from-rose-50/70 via-white to-gray-50/40 border-rose-200') }}">
            
            {{-- Icon Badge --}}
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl mx-auto flex items-center justify-center shadow-lg mb-5 
                {{ $isGraduated ? 'bg-gradient-to-br from-indigo-600 to-purple-700 text-white shadow-indigo-500/25' : ($isSlc ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-amber-500/25' : 'bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-rose-500/25') }}">
                @if($isGraduated)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                    </svg>
                @elseif($isSlc)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                @endif
            </div>

            {{-- Title & Status Pill --}}
            <div class="mb-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider 
                    {{ $isGraduated ? 'bg-indigo-100 text-indigo-800 border border-indigo-300' : ($isSlc ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-rose-100 text-rose-800 border border-rose-300') }}">
                    @if($isGraduated)
                        🎓 Graduated Alumni
                    @elseif($isSlc)
                        📄 School Leaving Certificate (SLC) Issued
                    @else
                        ⚠️ {{ ucfirst($student->status) }} Profile
                    @endif
                </span>
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 mt-2">
                    @if($isGraduated)
                        Congratulations, {{ $student->name }}!
                    @else
                        Student Profile Inactive
                    @endif
                </h2>
            </div>

            {{-- Explanatory Notice --}}
            <p class="text-sm text-gray-600 max-w-xl mx-auto leading-relaxed mb-6">
                @if($isGraduated)
                    You have successfully <strong>Graduated</strong> from this institution. As a graduated student, live student panel access (daily attendance, current class schedules, upcoming exam datesheets, and active fee generation) is no longer available. Your permanent graduation record and historical transcripts remain safely preserved in the school archive.
                @elseif($isSlc)
                    A <strong>School Leaving Certificate (SLC)</strong> has been formally issued for this student record. As you are no longer actively enrolled in this institution, access to active student panel operations is restricted.
                @else
                    This student record is currently marked as <strong>{{ ucfirst($student->status) }}</strong>. Active student panel access is restricted. Please contact the institution's administration office for assistance.
                @endif
            </p>

            {{-- Institutional Profile Summary Box --}}
            <div class="bg-white/80 rounded-2xl p-4 sm:p-5 border border-gray-200 text-left max-w-md mx-auto shadow-2xs space-y-2.5 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                    <span class="text-gray-400 font-semibold uppercase">Student Name</span>
                    <span class="font-bold text-gray-800">{{ $student->name }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                    <span class="text-gray-400 font-semibold uppercase">Admission No</span>
                    <span class="font-mono font-bold text-gray-800">{{ $student->admission_no }}</span>
                </div>
                @if($student->father_name)
                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                    <span class="text-gray-400 font-semibold uppercase">Father's Name</span>
                    <span class="font-medium text-gray-700">{{ $student->father_name }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                    <span class="text-gray-400 font-semibold uppercase">Class at {{ $isGraduated ? 'Graduation' : 'Departure' }}</span>
                    <span class="font-bold text-indigo-600">{{ $student->terminal_class?->name ?? $student->class?->name ?? 'Unassigned' }}</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-gray-400 font-semibold uppercase">Status</span>
                    <span class="font-bold {{ $isGraduated ? 'text-indigo-700' : 'text-amber-700' }}">{{ $isGraduated ? 'Graduated' : ($isSlc ? 'SLC Issued' : ucfirst($student->status)) }}</span>
                </div>
            </div>

            {{-- Sibling Switch Callout --}}
            @if($hasActiveSiblings && $activeSibling)
                <div class="mt-6 p-4 rounded-2xl bg-indigo-50/90 border border-indigo-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-indigo-900">Active Enrolled Sibling Available</h4>
                            <p class="text-[11px] text-indigo-700 mt-0.5"><strong>{{ $activeSibling->name }}</strong> is actively enrolled in <strong>{{ $activeSibling->class->name ?? 'Class' }}</strong>.</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        wire:click="switchChild({{ $activeSibling->id }})"
                        class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-indigo-600/30 whitespace-nowrap cursor-pointer flex items-center justify-center gap-1.5"
                    >
                        <span>Switch to {{ explode(' ', $activeSibling->name)[0] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            @endif

            {{-- Logout Button --}}
            <div class="mt-6 flex justify-center">
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 transition-colors flex items-center gap-1.5 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Sign Out of Portal</span>
                    </button>
                </form>
            </div>
        </div>
    @else

    {{-- 2. STUDENT HERO WELCOME & SUMMARY BAR --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    Welcome back, {{ explode(' ', $student->name)[0] }}! 👋
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $student->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1 animate-pulse"></span>
                    {{ ucfirst($student->status) }}
                </span>
                @if($familySiblings->count() > 1)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                        Sibling #{{ $student->sibling_rank + 1 }} of {{ $familySiblings->count() }}
                    </span>
                @endif
            </div>
            <p class="text-gray-500 text-xs sm:text-sm mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1">
                <span>Class: <strong class="text-indigo-600 font-semibold">{{ $student->terminal_class?->name ?? $student->class?->name ?? 'Not Assigned' }}</strong></span>
                <span>•</span>
                <span>Roll #: <strong class="text-gray-800 font-mono">{{ $student->roll_no ?? 'N/A' }}</strong></span>
                <span>•</span>
                <span>Adm #: <strong class="text-gray-800 font-mono">{{ $student->admission_no }}</strong></span>
                @if($student->father_name)
                    <span class="hidden md:inline">•</span>
                    <span class="hidden md:inline">Father: <strong class="text-gray-700">{{ $student->father_name }}</strong></span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <div class="hidden sm:flex bg-white px-3.5 py-2 rounded-xl shadow-2xs border border-gray-200 items-center gap-2 text-xs font-medium text-gray-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" x2="16" y1="2" y2="6"/>
                    <line x1="8" x2="8" y1="2" y2="6"/>
                    <line x1="3" x2="21" y1="10" y2="10"/>
                </svg>
                <span>{{ now()->format('l, F j') }}</span>
            </div>

            @if($latestInvoice && $latestInvoice->access_token)
                <a 
                    href="{{ route('public.voucher.pdf', $latestInvoice->access_token) }}" 
                    target="_blank"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm shadow-indigo-600/20 transition-all cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Fee Challan (PDF)</span>
                </a>
            @endif
        </div>
    </div>

    {{-- 3. KEY METRICS STAT CARDS (2 cols on Mobile, 4 cols on Desktop) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- Monthly Attendance Rate -->
        <div class="glass-card p-3.5 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-white shadow-md bg-gradient-to-br from-blue-500 to-indigo-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 font-medium truncate">Attendance Rate</p>
                <h3 class="text-lg sm:text-2xl font-bold text-gray-800">{{ $attendanceRate }}%</h3>
                <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 truncate">{{ $presentDays }}/{{ $totalDays }} days</p>
            </div>
        </div>

        <!-- Current Fee Dues -->
        <div class="glass-card p-3.5 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-white shadow-md bg-gradient-to-br {{ $pendingFee > 0 ? 'from-amber-500 to-orange-600' : 'from-emerald-500 to-teal-600' }} shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 font-medium truncate">Fee Balance</p>
                <h3 class="text-lg sm:text-2xl font-bold {{ $pendingFee > 0 ? 'text-amber-600' : 'text-emerald-600' }} truncate">
                    {{ $pendingFee > 0 ? 'Rs. ' . number_format($pendingFee) : 'Rs. 0' }}
                </h3>
                <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 truncate">
                    {{ $pendingFee > 0 ? ($latestInvoice ? 'Due ' . $latestInvoice->due_date?->format('d M') : 'Pending Dues') : 'Cleared' }}
                </p>
            </div>
        </div>

        <!-- Today's Attendance Status -->
        <div class="glass-card p-3.5 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-white shadow-md bg-gradient-to-br {{ $todayAttendance && $todayAttendance->normalized_status === 'present' ? 'from-emerald-500 to-green-600' : ($todayAttendance && $todayAttendance->normalized_status === 'late' ? 'from-amber-500 to-orange-500' : ($todayAttendance && $todayAttendance->normalized_status === 'leave' ? 'from-blue-500 to-indigo-600' : ($todayAttendance ? 'from-rose-500 to-red-600' : 'from-slate-400 to-gray-500'))) }} shrink-0">
                @if($todayAttendance && $todayAttendance->normalized_status === 'present')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @elseif($todayAttendance && $todayAttendance->normalized_status === 'late')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @elseif($todayAttendance && $todayAttendance->normalized_status === 'absent')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @elseif($todayAttendance && $todayAttendance->normalized_status === 'leave')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                @else
                    {{-- Default Attendance Calendar Checkmark (Pending / Daily Roll Call) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 16l2 2 4-4" />
                    </svg>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 font-medium truncate">Today's Attendance</p>
                <h3 class="text-base sm:text-xl font-bold text-gray-800 truncate">
                    {{ $todayAttendance ? $todayAttendance->status_label : 'Pending' }}
                </h3>
                <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 truncate">
                    {{ $todayAttendance ? ($classTeacherName ? 'Marked by ' . $classTeacherName : 'Marked Today') : 'Daily Roll Call' }}
                </p>
            </div>
        </div>

        <!-- Sibling / Family Concession -->
        <div class="glass-card p-3.5 sm:p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-white shadow-md bg-gradient-to-br from-purple-500 to-purple-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] sm:text-xs text-gray-500 font-medium truncate">Concession</p>
                <h3 class="text-base sm:text-xl font-bold text-gray-800 capitalize truncate">
                    {{ str_replace('_', ' ', $student->sibling_concession_status ?? 'Standard') }}
                </h3>
                <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 truncate">
                    {{ $student->custom_concession_amount ? 'Rs. ' . number_format($student->custom_concession_amount) . ' Off' : 'Policy' }}
                </p>
            </div>
        </div>
    </div>

    {{-- 4. SEGMENTED NAVIGATION TAB BAR (Mobile Touch Scrollable) --}}
    <div class="bg-white p-1 sm:p-1.5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-1 sm:gap-1.5 overflow-x-auto scrollbar-none">
        <button 
            wire:click="setTab('overview')"
            type="button" 
            class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap shrink-0 cursor-pointer {{ $activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span>Overview</span>
        </button>

        <button 
            wire:click="setTab('timetable')"
            type="button" 
            class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap shrink-0 cursor-pointer {{ $activeTab === 'timetable' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Timetable</span>
        </button>

        <button 
            wire:click="setTab('attendance')"
            type="button" 
            class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap shrink-0 cursor-pointer {{ $activeTab === 'attendance' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>Attendance</span>
        </button>

        <button 
            wire:click="setTab('results')"
            type="button" 
            class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap shrink-0 cursor-pointer {{ $activeTab === 'results' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Results</span>
        </button>

        <button 
            wire:click="setTab('fees')"
            type="button" 
            class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 whitespace-nowrap shrink-0 cursor-pointer {{ $activeTab === 'fees' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span>Fees & Challans</span>
        </button>
    </div>

    {{-- 5. TAB CONTENT SECTIONS --}}

    {{-- TAB 1: OVERVIEW --}}
    @if($activeTab === 'overview')
        <div class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                {{-- Today's Schedule (2 cols on large screen) --}}
                <div class="lg:col-span-2 glass-card p-4 sm:p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-gray-800">Today's Class Schedule</h3>
                                <p class="text-[11px] sm:text-xs text-gray-400">{{ ucfirst($selectedDay) }} timetable for {{ $student->class->name ?? 'Class' }}</p>
                            </div>
                        </div>
                        <button wire:click="setTab('timetable')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors cursor-pointer shrink-0">
                            Full Week →
                        </button>
                    </div>

                    @if($todayClasses->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            @foreach($todayClasses as $period)
                                <div class="p-3.5 sm:p-4 rounded-xl border border-gray-200/90 bg-gray-50/50 hover:bg-white hover:border-indigo-200 hover:shadow-xs transition-all">
                                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1.5">
                                        <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                            Period {{ $period->period_no }}
                                        </span>
                                        <span class="font-mono text-gray-500 font-medium text-[11px] sm:text-xs">
                                            {{ $period->start_time ? substr($period->start_time, 0, 5) : '' }} - {{ $period->end_time ? substr($period->end_time, 0, 5) : '' }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-800 truncate">{{ $period->subject->name ?? 'Subject' }}</h4>
                                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 text-xs">
                                        <span class="text-gray-500 truncate">
                                            Teacher: <strong class="text-gray-700">{{ $period->teacher->name ?? 'Assigned' }}</strong>
                                            @if(!empty($period->is_substitute_today))
                                                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Substitute Today</span>
                                            @endif
                                        </span>
                                        @if($period->room)
                                            <span class="px-2 py-0.5 bg-white border border-gray-200 rounded text-[10px] font-mono text-gray-600 shrink-0">
                                                Room {{ $period->room }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center text-gray-400 text-xs sm:text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <p>No lectures scheduled for {{ ucfirst($selectedDay) }}.</p>
                        </div>
                    @endif
                </div>

                {{-- Digital Profile & Identification Card (1 col) --}}
                <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-800">Student Profile</h3>
                            <p class="text-[11px] sm:text-xs text-gray-400">Institutional ID & Bio</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 p-0.5 shadow-md shrink-0">
                            @if($student->profile_photo_path)
                                <img src="{{ asset('storage/' . $student->profile_photo_path) }}" alt="{{ $student->name }}" class="w-full h-full object-cover rounded-[10px]">
                            @else
                                <div class="w-full h-full bg-white rounded-[10px] flex items-center justify-center text-indigo-700 text-lg sm:text-xl font-bold">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-sm sm:text-base font-bold text-gray-800 truncate">{{ $student->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $student->class->name ?? 'Class N/A' }}</p>
                            <span class="inline-block mt-1 font-mono text-[10px] sm:text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                ADM: {{ $student->admission_no }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-gray-100 text-xs">
                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-400">Father's Name</span>
                            <span class="font-semibold text-gray-700 truncate max-w-[150px] text-right">{{ $student->father_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-400">Emergency Phone</span>
                            <span class="font-mono font-semibold text-gray-700">{{ $student->emergency_contact ?? $student->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-400">Shift</span>
                            <span class="font-semibold text-gray-700 capitalize">{{ session('selected_shift_type', 'Morning') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 pt-2">
                            <div>
                                <span class="text-gray-600 font-semibold block text-[11px]">WhatsApp Alerts</span>
                                <span class="text-[10px] text-gray-400">Fee & attendance notices</span>
                            </div>
                            <button 
                                wire:click="toggleWhatsAppOptIn" 
                                type="button"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer {{ $whatsappOptIn ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border border-gray-300 hover:bg-gray-200' }}"
                            >
                                <span class="w-1.5 h-1.5 rounded-full {{ $whatsappOptIn ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
                                <span>{{ $whatsappOptIn ? 'Opted In' : 'Muted' }}</span>
                            </button>
                        </div>
                        @if(session()->has('whatsapp_message'))
                            <div class="text-[11px] font-medium text-emerald-700 bg-emerald-50 p-2 rounded-lg border border-emerald-200 mt-1">
                                {{ session('whatsapp_message') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 2: TIMETABLE --}}
    @if($activeTab === 'timetable')
        <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-5 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base sm:text-lg font-bold text-gray-800">Weekly Class Timetable</h3>
                        @if($isUniversalSchedule)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                <span>🗓️</span> Universal Routine
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500">
                        Class: {{ $student->class->name ?? 'N/A' }}
                        @if($isUniversalSchedule)
                            · Synchronized across all {{ count($schoolDays) }} active school days
                        @endif
                    </p>
                </div>

                {{-- Day Selector Pills (Synced with App Working Days Setting) --}}
                <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-xl overflow-x-auto scrollbar-none">
                    @foreach($schoolDays as $day)
                        <button 
                            wire:click="selectDay('{{ $day }}')"
                            type="button" 
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer whitespace-nowrap shrink-0 {{ $selectedDay === $day ? 'bg-white text-indigo-600 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            {{ ucfirst(substr($day, 0, 3)) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Period Grid for Selected Day --}}
            @if($todayClasses->isNotEmpty())
                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3">Period</th>
                                <th class="px-4 py-3">Timings</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Teacher</th>
                                <th class="px-4 py-3">Classroom</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($todayClasses as $period)
                                <tr class="hover:bg-indigo-50/20 transition-colors">
                                    <td class="px-4 py-3 font-bold text-indigo-600">Period {{ $period->period_no }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">
                                        {{ $period->start_time ? substr($period->start_time, 0, 5) : '-' }} - {{ $period->end_time ? substr($period->end_time, 0, 5) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $period->subject->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $period->teacher->name ?? '-' }}</span>
                                            @if(!empty($period->is_substitute_today))
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Substitute Today</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-mono">
                                            {{ $period->room ?? 'Standard Room' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="block sm:hidden space-y-2.5">
                    @foreach($todayClasses as $period)
                        <div class="p-3.5 rounded-xl border border-gray-200 bg-gray-50/60 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                    Period {{ $period->period_no }}
                                </span>
                                <span class="font-mono text-gray-500 text-[11px]">
                                    {{ $period->start_time ? substr($period->start_time, 0, 5) : '-' }} - {{ $period->end_time ? substr($period->end_time, 0, 5) : '-' }}
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-gray-800">{{ $period->subject->name ?? '-' }}</h4>
                            <div class="flex items-center justify-between text-xs text-gray-500 pt-1 border-t border-gray-100">
                                <span>Teacher: <strong class="text-gray-700">{{ $period->teacher->name ?? '-' }}</strong>
                                    @if(!empty($period->is_substitute_today))
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Substitute Today</span>
                                    @endif
                                </span>
                                <span class="font-mono text-[10px] bg-white border border-gray-200 px-1.5 py-0.5 rounded">
                                    {{ $period->room ?? 'Room' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-xs sm:text-sm">
                    No classes scheduled for {{ ucfirst($selectedDay) }}.
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: ATTENDANCE LEDGER --}}
    @if($activeTab === 'attendance')
        <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-5 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Monthly Attendance Ledger</h3>
                    <p class="text-xs text-gray-500">Official log for {{ \Carbon\Carbon::now()->format('F Y') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ $presentDays }} Present</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $lateDays }} Late</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>{{ $absentDays }} Absent</span>
                    </span>
                </div>
            </div>

            {{-- Today's Attendance Spotlight --}}
            <div class="p-3.5 sm:p-4 rounded-xl border border-gray-200/90 bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-sm bg-gradient-to-br {{ $todayAttendance && $todayAttendance->normalized_status === 'present' ? 'from-emerald-500 to-green-600' : ($todayAttendance && $todayAttendance->normalized_status === 'late' ? 'from-amber-500 to-orange-500' : ($todayAttendance && $todayAttendance->normalized_status === 'leave' ? 'from-blue-500 to-indigo-600' : ($todayAttendance ? 'from-rose-500 to-red-600' : 'from-slate-400 to-gray-500'))) }} shrink-0">
                        @if($todayAttendance && $todayAttendance->normalized_status === 'present')
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($todayAttendance && $todayAttendance->normalized_status === 'late')
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($todayAttendance && $todayAttendance->normalized_status === 'absent')
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($todayAttendance && $todayAttendance->normalized_status === 'leave')
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @else
                            {{-- Calendar Check Icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 16l2 2 4-4" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Today's Attendance ({{ \Carbon\Carbon::now()->format('D, d M Y') }})</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $todayAttendance && $todayAttendance->normalized_status === 'present' ? 'bg-emerald-100 text-emerald-800' : ($todayAttendance && $todayAttendance->normalized_status === 'late' ? 'bg-amber-100 text-amber-800' : ($todayAttendance && $todayAttendance->normalized_status === 'leave' ? 'bg-blue-100 text-blue-800' : ($todayAttendance ? 'bg-rose-100 text-rose-800' : 'bg-gray-200 text-gray-700'))) }}">
                                {{ $todayAttendance ? $todayAttendance->status_label : 'Pending' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">
                            {{ $todayAttendance ? ($classTeacherName ? 'Marked by ' . $classTeacherName : 'Verified in roll call') : 'Roll call in progress by class teacher' }}
                        </p>
                    </div>
                </div>
                <div class="text-right text-xs text-gray-400 font-mono hidden sm:block">
                    Official Session Log
                </div>
            </div>

            {{-- Attendance Table --}}
            @if($monthlyAttendances->isNotEmpty())
                <div class="overflow-x-auto rounded-xl border border-gray-200 smooth-scroll-container">
                    <table class="w-full text-left text-sm min-w-[500px]">
                        <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Day</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Verification Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($monthlyAttendances as $att)
                                <tr class="hover:bg-indigo-50/20 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($att->date)->format('d M, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($att->date)->format('l') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $att->normalized_status === 'present' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($att->normalized_status === 'late' ? 'bg-amber-50 text-amber-700 border border-amber-200' : ($att->normalized_status === 'leave' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-rose-50 text-rose-700 border border-rose-200')) }}">
                                            {{ $att->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 font-medium">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200 text-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>{{ $att->source }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-xs sm:text-sm">
                    No attendance records logged for this month yet.
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 4: EXAMS & RESULTS --}}
    @if($activeTab === 'results')
        <div class="space-y-5 sm:space-y-6">
            {{-- Exam Marks Report Cards --}}
            @if($examMarks->isNotEmpty())
                @foreach($examMarks as $examName => $marks)
                    @php
                        $aggregate = app(\App\Services\GradeCalculationService::class)->calculateAggregate(
                            $marks,
                            $student->class?->academic_session_id
                        );
                        $totalObtained = $aggregate['total_obtained'];
                        $totalMax = $aggregate['total_max'];
                        $percentage = $aggregate['percentage'];
                        $grade = $aggregate['grade'];
                        $firstMark = $marks->first();
                        $publishedAt = $firstMark?->published_at ? \Carbon\Carbon::parse($firstMark->published_at)->format('d M Y') : null;
                    @endphp
                    <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 sm:pb-4 border-b border-gray-100">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-800">{{ $examName }}</h3>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-gray-500">Class: {{ $student->class->name ?? 'N/A' }}</p>
                                    @if($publishedAt)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Official Result (Published {{ $publishedAt }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="px-2.5 sm:px-3 py-1 bg-indigo-50 border border-indigo-200 rounded-xl text-xs font-bold text-indigo-700">
                                    {{ $totalObtained }} / {{ $totalMax }} ({{ $percentage }}%)
                                </div>
                                <div class="px-2.5 sm:px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-black text-emerald-700">
                                    Grade: {{ $grade }}
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-200 smooth-scroll-container">
                            <table class="w-full text-left text-sm min-w-[420px]">
                                <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Subject</th>
                                        <th class="px-4 py-3">Max</th>
                                        <th class="px-4 py-3">Marks</th>
                                        <th class="px-4 py-3">Performance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($marks as $row)
                                        @php
                                            $eval = app(\App\Services\GradeCalculationService::class)->calculateFromMark(
                                                is_null($row->marks_obtained) ? null : (float)$row->marks_obtained,
                                                (float)$row->max_marks,
                                                (bool)$row->is_absent,
                                                $student->class?->academic_session_id
                                            );
                                        @endphp
                                        <tr class="hover:bg-indigo-50/20 transition-colors">
                                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $row->subject_name }}</td>
                                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $row->max_marks }}</td>
                                            <td class="px-4 py-3 font-bold font-mono text-xs {{ $eval['status'] === 'absent' ? 'text-rose-600' : 'text-gray-800' }}">
                                                {{ $eval['status'] === 'absent' ? 'ABS' : ($row->marks_obtained ?? '-') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($eval['status'] === 'scored')
                                                <div class="flex items-center gap-2 sm:gap-3">
                                                    <div class="w-16 sm:w-24 bg-gray-100 rounded-full h-1.5 sm:h-2 overflow-hidden">
                                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-full rounded-full" style="width: {{ min(100, $eval['percentage']) }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-bold {{ $eval['is_passing'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                                        {{ $eval['percentage'] }}% ({{ $eval['grade'] }})
                                                    </span>
                                                </div>
                                                @elseif($eval['status'] === 'absent')
                                                    <span class="text-xs font-bold text-rose-600">Absent</span>
                                                @else
                                                    <span class="text-xs font-medium text-gray-400">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="glass-card p-10 text-center text-gray-400 text-xs sm:text-sm rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    No examination results published for this student yet.
                </div>
            @endif

            {{-- Upcoming/Published Datesheet Schedules --}}
            @if($datesheets->isNotEmpty())
                <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-800">Examination Datesheet</h3>
                            <p class="text-xs text-gray-500">Official schedule published by examination cell</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold">
                            {{ $datesheets->count() }} Papers Scheduled
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($datesheets as $ds)
                            <div class="p-3.5 rounded-xl bg-gray-50/80 border border-gray-200 hover:border-indigo-200 transition-all">
                                <div class="flex items-center justify-between text-[10px] font-mono mb-1">
                                    <span class="text-indigo-600 font-bold">{{ \Carbon\Carbon::parse($ds->exam_date)->format('D, d M Y') }}</span>
                                    <span class="text-gray-400 uppercase font-semibold text-[9px]">{{ $ds->exam_name }}</span>
                                </div>
                                <h4 class="text-sm font-bold text-gray-800">{{ $ds->subject_name }}</h4>
                                <div class="flex items-center justify-between text-xs text-gray-500 mt-2 pt-1.5 border-t border-gray-200/60 font-mono">
                                    <span>Time: {{ $ds->start_time }}{{ $ds->end_time ? ' - ' . $ds->end_time : '' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 5: FEES & CHALLANS --}}
    @if($activeTab === 'fees')
        <div class="glass-card p-4 sm:p-6 rounded-2xl space-y-5 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Fee Invoices & Bank Challans</h3>
                    <p class="text-xs text-gray-500">Official 3-part bank deposit vouchers (Cashier, School, Student)</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">Concession:</span>
                    <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold capitalize">
                        {{ str_replace('_', ' ', $student->sibling_concession_status ?? 'Standard') }}
                    </span>
                </div>
            </div>

            @if($feeRecords->isNotEmpty())
                <div class="overflow-x-auto rounded-xl border border-gray-200 smooth-scroll-container">
                    <table class="w-full text-left text-sm min-w-[650px]">
                        <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3">Billing Period</th>
                                <th class="px-4 py-3">Due Date</th>
                                <th class="px-4 py-3">Total Amount</th>
                                <th class="px-4 py-3">Paid</th>
                                <th class="px-4 py-3">Balance</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Official Challan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($feeRecords as $fee)
                                <tr class="hover:bg-indigo-50/20 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-800 font-mono">{{ $fee->period }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">
                                        {{ $fee->due_date ? $fee->due_date->format('d M, Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-gray-800">Rs. {{ number_format($fee->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 font-mono text-emerald-600">Rs. {{ number_format($fee->paid_amount, 2) }}</td>
                                    <td class="px-4 py-3 font-mono font-bold {{ $fee->balance > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                        Rs. {{ number_format($fee->balance, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $fee->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($fee->status === 'partial' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                            {{ ucfirst($fee->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($fee->access_token)
                                            <a 
                                                href="{{ route('public.voucher.pdf', $fee->access_token) }}" 
                                                target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-all cursor-pointer whitespace-nowrap"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Print Challan</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-xs sm:text-sm">
                    No fee challans generated for this student yet.
                </div>
            @endif
        </div>
    @endif
    @endif
</div>
