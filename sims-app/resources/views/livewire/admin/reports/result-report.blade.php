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

            {{-- Generate --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 disabled:opacity-50 h-[42px]"
            >
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
            className: container.dataset.classname || ''
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
            
            let cellStyle = '';
            if (isAbsent) {
                cellStyle = 'color: red; font-weight: bold;';
            } else if (isFailed) {
                cellStyle = 'background-color: #fee2e2; color: #7f1d1d; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact;'; 
            }
            
            subjectRows += '<tr>' +
                '<td style="text-align: left; padding: 8px; border: 1px solid #ddd;">' + subjectName + '</td>' +
                '<td style="text-align: center; padding: 8px; border: 1px solid #ddd;">' + max + '</td>' +
                '<td style="text-align: center; padding: 8px; border: 1px solid #ddd; ' + cellStyle + '">' + score + '</td>' +
                '<td style="text-align: center; padding: 8px; border: 1px solid #ddd;">' + pct + '</td>' +
                '<td style="text-align: center; padding: 8px; border: 1px solid #ddd;">' + grade + '</td>' +
            '</tr>';
        }

        return '<div class="page" style="page-break-after: always; padding: 40px; font-family: sans-serif;">' +
            '<div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">' +
                '<div style="font-size: 24px; font-weight: bold; text-transform: uppercase;">ISLAMABAD MODEL COLLEGE FOR BOYS (VI-X)</div>' +
                '<div style="font-size: 16px; margin-bottom: 10px;">G-6/2 ISLAMABAD</div>' +
                '<div style="font-size: 18px; color: #555;">' + data.examName + '</div>' +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">' +
                '<div><strong>Name:</strong> ' + student.name + '</div>' +
                '<div><strong>Roll No:</strong> ' + (student.roll_no || '-') + '</div>' +
                '<div><strong>Father Name:</strong> ' + (student.father_name || '-') + '</div>' +
                '<div><strong>Admission No:</strong> ' + (student.admission_no || '-') + '</div>' +
                '<div><strong>Class:</strong> ' + data.className + '</div>' +
                '<div><strong>Position:</strong> ' + (student.position || '-') + '</div>' +
            '</div>' +
            '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">' +
                '<thead><tr style="background-color: #f5f5f5;">' +
                    '<th style="text-align: left; padding: 10px; border: 1px solid #ddd;">Subject</th>' +
                    '<th style="text-align: center; padding: 10px; border: 1px solid #ddd;">Max Marks</th>' +
                    '<th style="text-align: center; padding: 10px; border: 1px solid #ddd;">Obtained</th>' +
                    '<th style="text-align: center; padding: 10px; border: 1px solid #ddd;">%</th>' +
                    '<th style="text-align: center; padding: 10px; border: 1px solid #ddd;">Grade</th>' +
                '</tr></thead>' +
                '<tbody>' + subjectRows + '</tbody>' +
                '<tfoot><tr style="font-weight: bold; background-color: #f9f9f9;">' +
                    '<td style="text-align: left; padding: 10px; border: 1px solid #ddd;">Total</td>' +
                    '<td style="text-align: center; padding: 10px; border: 1px solid #ddd;">' + student.max_total + '</td>' +
                    '<td style="text-align: center; padding: 10px; border: 1px solid #ddd;">' + student.total_obtained + '</td>' +
                    '<td style="text-align: center; padding: 10px; border: 1px solid #ddd;">' + student.percentage + '%</td>' +
                    '<td style="text-align: center; padding: 10px; border: 1px solid #ddd;">' + student.grade + '</td>' +
                '</tr></tfoot>' +
            '</table>' +
            '<div style="margin-top: 20px; padding: 10px; background-color: ' + (student.summary === 'Pass' ? '#d4edda' : '#f8d7da') + '; border-radius: 5px; text-align: center;">' +
                '<strong>Result: ' + student.summary + '</strong>' +
            '</div>' +
            '<div style="margin-top: 60px; display: flex; justify-content: space-between;">' +
                '<div style="border-top: 1px solid #333; padding-top: 5px; width: 150px; text-align: center;">Class Teacher</div>' +
                '<div style="border-top: 1px solid #333; padding-top: 5px; width: 150px; text-align: center;">Principal</div>' +
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
                '<div style="font-size: 20px; font-weight: bold; text-transform: uppercase;">ISLAMABAD MODEL COLLEGE FOR BOYS (VI-X)</div>' +
                '<div style="font-size: 14px; margin-bottom: 5px;">G-6/2 ISLAMABAD</div>' +
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
