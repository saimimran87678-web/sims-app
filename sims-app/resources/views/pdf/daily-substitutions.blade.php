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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 900px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div id="download-status" style="display: none; padding: 8px 16px; border-radius: 8px; color: white; font-weight: 600; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); transition: all 0.3s ease;">
        </div>
        <div style="margin-left: auto;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #16a34a; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 4px rgba(22,163,74,0.2);">Print / Save PDF</button>
            <button onclick="downloadPdf()" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-left: 8px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">Download PDF</button>
            <button onclick="window.close()" style="padding: 8px 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; cursor: pointer; margin-left: 8px;">Close Window</button>
        </div>
    </div>

    <div id="report-content" style="padding: 40px; background: white; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
    {{-- Dynamic Institute Header --}}
    <div style="margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; font-family: 'Helvetica', 'Arial', sans-serif;">
        @php
            $logoPath = \App\Models\Setting::get('institute_logo', \App\Models\Setting::getGlobal('institute_logo'));
            $instituteName = \App\Models\Setting::get('institute_name', \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2'));
            $address = \App\Models\Setting::get('institute_address', \App\Models\Setting::getGlobal('institute_address'));
        @endphp
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                @if($logoPath)
                    <td style="width: 65px; text-align: left; vertical-align: middle; padding-right: 15px;">
                        <img src="{{ '/' . $logoPath }}" style="height: 55px; max-width: 65px; object-fit: contain;" crossorigin="anonymous">
                    </td>
                @endif
                <td style="text-align: left; vertical-align: middle;">
                    <div style="font-size: 20px; font-weight: bold; text-transform: uppercase; color: #0f172a; line-height: 1.2; letter-spacing: 0.5px;">{{ $instituteName }}</div>
                    @if($address)
                        <div style="font-size: 11px; color: #475569; margin-top: 3px; font-weight: 500;">{{ $address }}</div>
                    @endif
                </td>
                <td style="text-align: right; vertical-align: middle; width: 220px;">
                    <div style="font-size: 11px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px;">Teacher Arrangement Report</div>
                    <div style="font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 3px;">{{ \Carbon\Carbon::parse($date)->format('l, j M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Closed / Merged Classroom Notices --}}
    @if(!empty($closedClasses) || !empty($mergedClasses))
        <div style="margin-bottom: 20px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 15px;">
            <div style="font-size: 11px; font-weight: bold; color: #1e293b; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px;">
                Special Classroom Arrangements &amp; Status for Today
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    @if(!empty($closedClasses))
                        <td style="vertical-align: top; width: 50%; padding-right: 10px;">
                            <span style="font-size: 11px; font-weight: bold; color: #b91c1c;">● Closed Classrooms:</span>
                            <ul style="margin: 4px 0 0 0; padding-left: 18px; font-size: 11px; color: #334155;">
                                @foreach($closedClasses as $c)
                                    <li><strong>{{ $c['class_name'] }}</strong>: {{ $c['reason'] ?: 'Closed for today' }}</li>
                                @endforeach
                            </ul>
                        </td>
                    @endif
                    @if(!empty($mergedClasses))
                        <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                            <span style="font-size: 11px; font-weight: bold; color: #4338ca;">● Combined / Merged Classrooms:</span>
                            <ul style="margin: 4px 0 0 0; padding-left: 18px; font-size: 11px; color: #334155;">
                                @foreach($mergedClasses as $m)
                                    <li><strong>{{ $m['source_class_name'] }}</strong> merged into <strong>{{ $m['target_class_name'] }}</strong> ({{ $m['periods_count'] }} period(s))</li>
                                @endforeach
                            </ul>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    @if(empty($data))
        <p style="margin-top: 30px; text-align: center; color: #64748b; font-style: italic;">No teacher substitutions recorded for this date.</p>
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
                            @if(!empty($teacher['remarks']))
                                <div style="font-size: 10px; color: #64748b; font-style: italic; margin-top: 3px;">&ldquo;{{ $teacher['remarks'] }}&rdquo;</div>
                            @endif
                        </td>
                        <td class="arrangements-col">
                            @foreach($teacher['periods'] as $period)
                                <div class="arrangement-item">
                                    <div class="period-no">{{ $period['period_no'] }}</div>
                                    <div class="arrangement-details">
                                        <strong>{{ $period['class_name'] }} - {{ $period['subject_name'] }} : </strong>
                                        @if(!empty($period['is_closed']))
                                            <span style="color: #b91c1c; font-weight: bold; background: #fee2e2; padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $period['substitute_name'] }}</span>
                                        @elseif(!empty($period['is_merged_away']))
                                            <span style="color: #4338ca; font-weight: bold; background: #e0e7ff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $period['substitute_name'] }}</span>
                                        @elseif($period['substitute_name'] === 'Unassigned')
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

    {{-- Client-side PDF Generation with Local Bundle first + CDN fallback --}}
    <script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        if (typeof html2pdf === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"><\/script>');
        }
    </script>
    <script>
        var isGenerating = false;

        function downloadPdf() {
            if (isGenerating) return;
            isGenerating = true;

            var statusBox = document.getElementById('download-status');
            if (statusBox) {
                statusBox.style.display = 'inline-block';
                statusBox.style.background = '#1e3a8a';
                statusBox.innerHTML = '<span style="display:inline-block; animation:spin 1s linear infinite; margin-right:8px;">⏳</span> Preparing and downloading Teacher Arrangement PDF... Please wait...';
            }

            var element = document.getElementById('report-content');
            var opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Teacher_Arrangement_{{ $date }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, allowTaint: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            if (typeof html2pdf === 'function') {
                html2pdf().set(opt).from(element).save().then(function() {
                    isGenerating = false;
                    if (statusBox) {
                        statusBox.style.background = '#16a34a';
                        statusBox.innerHTML = '✅ Teacher Arrangement PDF downloaded successfully! You can also print or keep this tab open.';
                        setTimeout(function() {
                            statusBox.style.opacity = '0';
                            setTimeout(function() { statusBox.style.display = 'none'; statusBox.style.opacity = '1'; }, 500);
                        }, 5000);
                    }
                }).catch(function(err) {
                    console.error('PDF generation error:', err);
                    isGenerating = false;
                    if (statusBox) {
                        statusBox.style.background = '#b91c1c';
                        statusBox.innerHTML = '⚠️ Note: Direct browser download encountered an issue. Please use the "Print / Save PDF" button above.';
                    }
                });
            } else {
                isGenerating = false;
                window.print();
            }
        }

        @if(!isset($autoDownload) || $autoDownload)
        // Automatically start browser-side PDF rendering & download once assets are loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                downloadPdf();
            }, 400);
        });
        @endif
    </script>
</body>
</html>
