<div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Result Report</h1>
            <p class="text-gray-500">Generate exam result reports for classes</p>
        </div>
    </div>

    {{-- Controls --}}
    <div class="glass-card p-6 rounded-2xl">
        <form wire:submit="generate" class="flex flex-col md:flex-row gap-4 items-end">
            {{-- Session --}}
            @can('reports.view-sessions')
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Session</label>
                <select wire:model.live="selectedSessionId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    @foreach($academicSessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }} @if($session->is_active) (Current) @endif</option>
                    @endforeach
                </select>
            </div>
            @endrole

            {{-- Exam --}}
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                <select wire:model.live="selectedExamId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="">Select Exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Class --}}
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select wire:model.live="selectedClassId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="w-full md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                    <option value="">All Statuses</option>
                </select>
            </div>

            {{-- Generate --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="px-6 rounded-xl text-white font-semibold text-sm flex items-center justify-center gap-2 shadow-md hover:shadow-lg transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-150 disabled:opacity-50 h-[42px] cursor-pointer"
                style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);"
            >
                <svg wire:loading.remove class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                
                <svg wire:loading class="animate-spin h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <span wire:loading.remove>Generate Report</span>
                <span wire:loading>Processing...</span>
            </button>
        </form>

        @if(session()->has('error'))
            <div class="mt-4 p-4 text-red-700 bg-red-50 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
        @if(session()->has('message'))
            <div class="mt-4 p-4 text-green-700 bg-green-50 rounded-lg">
                {{ session('message') }}
            </div>
        @endif
    </div>

    {{-- Results Table --}}
    @if(!empty($reportData))
        <div class="glass-card rounded-2xl overflow-hidden" id="result-report-container"
            data-report='@json($reportData)'
            data-headers='@json($columnHeaders)'
            data-maxmarks='@json($subjectMaxMarks)'
            data-examname="{{ $examName }}"
            data-classname="{{ $className }}"
            data-formal-name="{{ \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS')) }}"
            data-address="{{ \App\Models\Setting::getGlobal('institute_address', '') }}"
            data-logo="{{ \App\Models\Setting::getGlobal('institute_logo') ? '/' . \App\Models\Setting::getGlobal('institute_logo') : '' }}"
        >
            <div class="p-6 border-b border-gray-100 flex justify-between items-center flex-wrap gap-4">
                <h3 class="font-bold text-gray-800">
                    Result Broadsheet - {{ $examName }} - {{ $className }}
                </h3>
                <div class="flex gap-2 flex-wrap">
                    <button 
                        onclick="window.printGazette()"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium text-sm flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Print Gazette
                    </button>
                    <button 
                        onclick="window.printAllCards()"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors font-medium text-sm flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print All Cards
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Roll</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            @foreach($columnHeaders as $subjectId => $subjectName)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ $subjectName }}<br>
                                    <span class="text-gray-400 font-normal">({{ $subjectMaxMarks[$subjectId] ?? 100 }})</span>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Print</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reportData as $index => $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $row['roll_no'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $row['name'] }}
                            </td>
                            @foreach($columnHeaders as $subjectId => $subjectName)
                                @php
                                    $subjectData = $row['subjects'][$subjectId] ?? null;
                                    $isNotEnrolled = $subjectData['not_enrolled'] ?? false;
                                    $score = $subjectData['score'] ?? null;
                                    $maxMarks = $subjectMaxMarks[$subjectId] ?? 100;
                                    $passingPct = $subjectPassingMarks[$subjectId] ?? 33;
                                    $passingScore = ($maxMarks * $passingPct) / 100;
                                    $isFailing = !$isNotEnrolled && $score !== null && $score < $passingScore;
                                    $isAbsent = !$isNotEnrolled && in_array($subjectName, $row['absent_subjects'] ?? []);
                                @endphp
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center 
                                    {{ $isAbsent ? 'text-red-600 font-bold' : ($isFailing ? 'bg-red-100 text-red-900 font-bold' : ($isNotEnrolled ? 'text-gray-400 bg-gray-50/50' : 'text-gray-700')) }}">
                                    {{ $isAbsent ? 'A' : ($isNotEnrolled ? '-' : ($score !== null ? $score : '-')) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold text-gray-900">
                                {{ $row['total_obtained'] }}/{{ $row['max_total'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold text-blue-600">
                                {{ $row['percentage'] }}%
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold 
                                {{ $row['grade'] === 'A+' || $row['grade'] === 'A' ? 'text-green-600' : '' }}
                                {{ $row['grade'] === 'B' || $row['grade'] === 'B+' ? 'text-blue-600' : '' }}
                                {{ $row['grade'] === 'C' || $row['grade'] === 'D' ? 'text-yellow-600' : '' }}
                                {{ $row['grade'] === 'F' ? 'text-red-600' : '' }}
                            ">
                                {{ $row['grade'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold {{ $row['summary'] === 'Pass' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $row['summary'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                <button 
                                    onclick="window.printSingleCard({{ $index }})"
                                    class="text-gray-500 hover:text-gray-700 transition-colors"
                                    title="Print Card"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($selectedClassId && $selectedExamId && !$isLoading)
        <div class="glass-card p-12 text-center rounded-2xl">
            <p class="text-gray-500">No data found. Make sure marks have been entered for this exam and class.</p>
        </div>
    @endif
</div>

@script
<script>
    window.getReportData = function() {
        const container = document.getElementById('result-report-container');
        if (!container) return null;
        
        return {
            reportData: JSON.parse(container.dataset.report || '[]'),
            columnHeaders: JSON.parse(container.dataset.headers || '{}'),
            subjectMaxMarks: JSON.parse(container.dataset.maxmarks || '{}'),
            examName: container.dataset.examname || '',
            className: container.dataset.classname || '',
            formalName: container.dataset.formalName || '',
            address: container.dataset.address || '',
            logo: container.dataset.logo || ''
        };
    }

    window.getCardHTML = function(student, data) {
        let subjectRows = '';
        for (const [subjectId, subjectName] of Object.entries(data.columnHeaders)) {
            const subjectData = student.subjects[subjectId] || {};
            if (subjectData.not_enrolled) {
                continue;
            }
            const isAbsent = (student.absent_subjects || []).includes(subjectName);
            const isFailed = subjectData.is_failed || false;
            
            const score = isAbsent ? 'A' : (subjectData.score !== null && subjectData.score !== undefined ? subjectData.score : '-');
            const max = data.subjectMaxMarks[subjectId] || 100;
            let pct = '-';
            let grade = '-';
            
            if (!isAbsent && score !== '-') {
                const p = (parseFloat(score) / max) * 100;
                if (p >= 0) { 
                    pct = p.toFixed(1) + '%';
                    if (p >= 90) grade = 'A+';
                    else if (p >= 80) grade = 'A';
                    else if (p >= 70) grade = 'B+';
                    else if (p >= 60) grade = 'B';
                    else if (p >= 50) grade = 'C';
                    else grade = 'F';
                }
            } else if (isAbsent) {
                grade = 'Absent';
            }
            
            let cellStyle = 'color: #334155;';
            if (isAbsent) {
                cellStyle = 'color: red; font-weight: bold;';
            } else if (isFailed) {
                cellStyle = 'background-color: #fee2e2; color: #7f1d1d; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact;'; 
            }
            
            subjectRows += '<tr>' +
                '<td style="text-align: left; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155;">' + subjectName + '</td>' +
                '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155; width: 20%;">' + max + '</td>' +
                '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; ' + cellStyle + ' width: 20%;">' + score + '</td>' +
                '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155; width: 15%;">' + pct + '</td>' +
                '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155; width: 15%;">' + grade + '</td>' +
            '</tr>';
        }

        return '<div class="page" style="page-break-after: always; padding: 30px 40px; font-family: \'Segoe UI\', system-ui, -apple-system, sans-serif; color: #1e293b; max-width: 850px; margin: 0 auto; box-sizing: border-box;">' +
            '<div style="text-align: center; margin-bottom: 25px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 20px;">' +
                (data.logo ? '<div style="margin-bottom: 12px;"><img src="' + data.logo + '" style="height: 55px; max-width: 150px; object-fit: contain;"></div>' : '') +
                '<div style="font-size: 22px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">' + (data.formalName || 'SIMS') + '</div>' +
                (data.address ? '<div style="font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500;">' + data.address + '</div>' : '') +
                '<div style="font-size: 14px; font-weight: 700; color: #1e3a8a; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 10px;">' + data.examName + '</div>' +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 8px 16px; margin-bottom: 25px; font-size: 13px; color: #334155; padding: 12px 16px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Name:</span><strong style="color: #0f172a;">' + student.name + '</strong></div>' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Roll No:</span><strong style="color: #0f172a;">' + (student.roll_no || '-') + '</strong></div>' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Father Name:</span><strong style="color: #0f172a;">' + (student.father_name || '-') + '</strong></div>' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Admission No:</span><strong style="color: #0f172a;">' + (student.admission_no || '-') + '</strong></div>' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Class:</span><strong style="color: #0f172a;">' + data.className + '</strong></div>' +
                '<div><span style="color: #64748b; font-weight: 600; display: inline-block; width: 110px;">Position:</span><strong style="color: #0f172a;">' + (student.position || '-') + '</strong></div>' +
            '</div>' +
            '<table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden;">' +
                '<thead><tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">' +
                    '<th style="text-align: left; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Subject</th>' +
                    '<th style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: 20%;">Max Marks</th>' +
                    '<th style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: 20%;">Obtained</th>' +
                    '<th style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: 15%;">%</th>' +
                    '<th style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: 15%;">Grade</th>' +
                '</tr></thead>' +
                '<tbody>' + subjectRows + '</tbody>' +
                '<tfoot><tr style="font-weight: bold; background-color: #f8fafc; border-top: 2px solid #cbd5e1;">' +
                    '<td style="text-align: left; padding: 10px 12px; border: 1px solid #cbd5e1; color: #0f172a; font-size: 12px;">Total</td>' +
                    '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #0f172a; font-size: 12px;">' + student.max_total + '</td>' +
                    '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #0f172a; font-size: 12px;">' + student.total_obtained + '</td>' +
                    '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; color: #1e3a8a; font-size: 12px;">' + student.percentage + '%</td>' +
                    '<td style="text-align: center; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155;">' + student.grade + '</td>' +
                '</tr></tfoot>' +
            '</table>' +
            '<div style="margin-top: 20px; padding: 10px; background-color: ' + (student.summary === 'Pass' ? '#ecfdf5' : '#fef2f2') + '; border: 1px solid ' + (student.summary === 'Pass' ? '#a7f3d0' : '#fecaca') + '; color: ' + (student.summary === 'Pass' ? '#065f46' : '#991b1b') + '; border-radius: 6px; text-align: center; font-size: 14px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">' +
                'Result: ' + student.summary +
            '</div>' +
            '<div style="margin-top: 60px; display: flex; justify-content: space-between;">' +
                '<div style="border-top: 1.5px solid #cbd5e1; padding-top: 6px; width: 180px; text-align: center; font-size: 12px; font-weight: 600; color: #475569;">Class Teacher</div>' +
                '<div style="border-top: 1.5px solid #cbd5e1; padding-top: 6px; width: 180px; text-align: center; font-size: 12px; font-weight: 600; color: #475569;">Principal</div>' +
            '</div>' +
        '</div>';
    }

    window.printSingleCard = function(index) {
        const data = window.getReportData();
        if (!data) return;
        const student = data.reportData[index];
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Report Card - ' + student.name + '</title></head><body>' + window.getCardHTML(student, data) + '</body></html>');
        printWindow.document.close();
        printWindow.print();
    }

    window.printAllCards = function() {
        const data = window.getReportData();
        if (!data) return;
        const printWindow = window.open('', '_blank');
        let allCards = '';
        data.reportData.forEach(function(student) {
            allCards += window.getCardHTML(student, data);
        });
        printWindow.document.write('<html><head><title>All Report Cards - ' + data.className + '</title><style>@media print { .page { page-break-after: always; } .page:last-child { page-break-after: auto; } }</style></head><body>' + allCards + '</body></html>');
        printWindow.document.close();
        setTimeout(function() { printWindow.print(); }, 500);
    }

    window.printGazette = function() {
        const data = window.getReportData();
        if (!data) return;
        let subjectHeaders = '';
        let subjectNames = [];
        for (const [subjectId, subjectName] of Object.entries(data.columnHeaders)) {
            subjectHeaders += '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">' + subjectName + '</th>';
            subjectNames.push({id: subjectId, name: subjectName});
        }

        let studentRows = '';
        data.reportData.forEach(function(student) {
            let subjectCells = '';
            subjectNames.forEach(function(item) {
                const subjectData = student.subjects[item.id] || {};
                const isNotEnrolled = subjectData.not_enrolled || false;
                const isAbsent = !isNotEnrolled && (student.absent_subjects || []).includes(item.name);
                const isFailed = !isNotEnrolled && (subjectData.is_failed || false);
                
                const score = isNotEnrolled ? '-' : (isAbsent ? 'A' : (subjectData.score !== null && subjectData.score !== undefined ? subjectData.score : '-'));
                
                let cellStyle = '';
                if (isAbsent) {
                    cellStyle = 'color: red; font-weight: bold;';
                } else if (isFailed) {
                    cellStyle = 'background-color: #fee2e2; color: #7f1d1d; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact;'; 
                } else if (isNotEnrolled) {
                    cellStyle = 'color: #999; background-color: #fafafa;';
                }
                
                subjectCells += '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px;' + cellStyle + '">' + score + '</td>';
            });

            const failedSubjects = student.failed_subjects || [];
            const absentSubjects = student.absent_subjects || [];

            studentRows += '<tr>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px;">' + (student.admission_no || '-') + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px;">' + (student.roll_no || '-') + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: left; font-size: 11px;">' + student.name + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: left; font-size: 11px;">' + (student.father_name || '-') + '</td>' +
                subjectCells +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px; font-weight: bold;">' + student.total_obtained + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px; font-weight: bold;">' + student.percentage + '%</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px;">' + (student.position || '-') + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px;">' + student.grade + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px; font-weight: bold; color: red;">' + (failedSubjects.length > 0 ? failedSubjects.length : '-') + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px; font-weight: bold; color: orange;">' + (absentSubjects.length > 0 ? absentSubjects.length : '-') + '</td>' +
                '<td style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 11px; font-weight: bold; color: ' + (student.summary === 'Pass' ? 'green' : 'red') + ';">' + student.summary + '</td>' +
            '</tr>';
        });

        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Result Gazette - ' + data.className + '</title>' +
            '<style>@page { size: auto; margin: 5mm; } body { font-family: sans-serif; padding: 10px; font-size: 11px; } @media print { body { -webkit-print-color-adjust: exact; } table { width: 100%; font-size: inherit; } }</style></head>' +
            '<body>' +
            '<div style="text-align: center; margin-bottom: 20px;">' +
                (data.logo ? '<div style="margin-bottom: 10px;"><img src="' + data.logo + '" style="height: 50px; max-width: 120px; object-fit: contain;"></div>' : '') +
                '<div style="font-size: 20px; font-weight: bold; text-transform: uppercase;">' + (data.formalName || 'SIMS') + '</div>' +
                (data.address ? '<div style="font-size: 14px; margin-bottom: 5px;">' + data.address + '</div>' : '') +
                '<div style="font-size: 16px; margin-top: 5px;">Result Gazette - ' + data.examName + ' - ' + data.className + '</div>' +
            '</div>' +
            '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">' +
                '<thead><tr>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Adm. No</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Roll</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Name</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Father Name</th>' +
                    subjectHeaders +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Total</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">%</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Pos</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Grade</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Failed</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Absent</th>' +
                    '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: inherit; background-color: #f0f0f0;">Result</th>' +
                '</tr></thead>' +
                '<tbody>' + studentRows + '</tbody>' +
            '</table></body></html>');
        printWindow.document.close();
        setTimeout(function() { printWindow.print(); }, 500);
    }
</script>
@endscript
