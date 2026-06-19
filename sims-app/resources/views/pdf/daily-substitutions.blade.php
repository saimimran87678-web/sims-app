<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Arrangement Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.4;
            padding: 20px;
        }
        .header {
            margin-bottom: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #000;
        }
        .main-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 12px;
            font-weight: bold;
            background-color: #f2f2f2;
            color: #000;
            border: 1px solid #000;
        }
        
        .teacher-row > td {
            padding: 15px;
            vertical-align: top;
            border: 1px solid #000;
        }
        
        /* Left Column */
        .teacher-col {
            width: 35%;
        }
        .teacher-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 4px;
            color: #000;
        }
        .teacher-status {
            font-size: 11px;
            color: #000; /* 'Status:' text is black */
        }
        .status-val-Absent { color: #dc2626; font-weight: bold; } /* Red */
        .status-val-Leave { color: #16a34a; font-weight: bold; } /* Green */
        .status-val-Official { color: #2563eb; font-weight: bold; } /* Blue */

        /* Right Column */
        .arrangements-col {
            width: 65%;
        }
        .arrangement-item {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
        }
        .arrangement-item:last-child {
            margin-bottom: 0;
        }
        .period-no {
            width: 24px;
            height: 24px;
            line-height: 24px;
            font-weight: bold;
            color: #000;
            background: #e5e5e5;
            text-align: center;
            border: 1px solid #000;
            border-radius: 4px;
            margin-right: 15px;
            font-size: 12px;
            flex-shrink: 0;
        }
        .arrangement-details {
            flex: 1;
            font-size: 12px;
            color: #000;
        }
        .arrangement-details strong {
            font-weight: bold;
            color: #000;
        }
        .substitute-name {
            color: #0066cc;
            font-weight: bold;
        }
        .unassigned {
            color: #dc2626;
            font-style: italic;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            font-size: 11px;
            color: #999;
            text-align: right;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .teacher-row { page-break-inside: avoid; }
            .main-table th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
            .period-no { background: #e5e5e5 !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="downloadPdf()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">Download PDF</button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; cursor: pointer; margin-left: 10px;">Close Window</button>
    </div>

    <div id="report-content" style="padding: 40px; background: white; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
    <div class="header">
        <h1>Teacher Arrangement Report</h1>
        <p>{{ \Carbon\Carbon::parse($date)->format('l, j M Y') }}</p>
    </div>

    @if(empty($data))
        <p style="margin-top: 50px; text-align: center; color: #64748b; font-style: italic;">No arrangements recorded for this date.</p>
    @else
        <table class="main-table">
            <thead>
                <tr>
                    <th class="teacher-col">Absent Teacher</th>
                    <th class="arrangements-col">Arrangements / Substitutions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $teacher)
                    @php
                        $statusColorClass = 'status-val-Absent';
                        if ($teacher['status'] === 'Leave') $statusColorClass = 'status-val-Leave';
                        if (str_contains($teacher['status'], 'Duty')) $statusColorClass = 'status-val-Official';
                    @endphp
                    <tr class="teacher-row">
                        <td class="teacher-col">
                            <div class="teacher-name">{{ $teacher['teacher_name'] }}</div>
                            <div class="teacher-status">Status: <span class="{{ $statusColorClass }}">{{ $teacher['status'] }}</span></div>
                        </td>
                        <td class="arrangements-col">
                            @foreach($teacher['periods'] as $period)
                                <div class="arrangement-item">
                                    <div class="period-no">{{ $period['period_no'] }}</div>
                                    <div class="arrangement-details">
                                        <strong>{{ $period['class_name'] }} - {{ $period['subject_name'] }} : </strong>
                                        @if($period['substitute_name'] === 'Unassigned')
                                            <span class="unassigned">Unassigned</span>
                                        @else
                                            <span class="substitute-name">{{ $period['substitute_name'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i') }}
    </div>
    </div> <!-- end report-content -->

    <script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        function downloadPdf() {
            var element = document.getElementById('report-content');
            var opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Teacher_Arrangement_{{ $date }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // New Promise-based usage:
            html2pdf().set(opt).from(element).save();
        }

        // Auto-download on load
        window.onload = function() {
            setTimeout(function() {
                downloadPdf();
            }, 500);
        };
    </script>
</body>
</html>
