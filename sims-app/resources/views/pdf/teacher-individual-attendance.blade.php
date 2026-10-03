<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Attendance Report - {{ $teacher->name ?? 'Staff' }} - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
            background: #fff;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .institute-name {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }
        .institute-sub {
            font-size: 11px;
            color: #475569;
        }
        .report-title {
            font-size: 13px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        .profile-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .profile-item {
            font-size: 11px;
        }
        .profile-item strong {
            color: #0f172a;
        }

        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            border-spacing: 6px;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-cell {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
            width: 12.5%;
        }
        .kpi-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }
        .kpi-val {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .kpi-val.green { color: #16a34a; }
        .kpi-val.amber { color: #d97706; }
        .kpi-val.blue  { color: #2563eb; }
        .kpi-val.red   { color: #dc2626; }
        .kpi-val.purple{ color: #7c3aed; }

        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 25px;
        }
        table.attendance-table th, table.attendance-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            vertical-align: middle;
        }
        table.attendance-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            text-align: left;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }
        .badge-present   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-leave     { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-shortleave{ background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-official  { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-absent    { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-weekend   { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .badge-holiday   { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .badge-unmarked  { background: #f8fafc; color: #94a3b8; }

        .remark-text {
            font-size: 10px;
            color: #0f172a;
            font-weight: 600;
        }
        .remark-empty {
            color: #cbd5e1;
            font-style: italic;
        }
        .duty-badge {
            background: #ede9fe;
            color: #5b21b6;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
            margin-right: 4px;
            margin-bottom: 2px;
        }

        .signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            width: 170px;
            border-top: 1px solid #475569;
            padding-top: 6px;
            font-weight: 600;
            font-size: 10px;
            color: #334155;
        }

        .no-print {
            margin-bottom: 18px;
            text-align: right;
        }
        .btn {
            padding: 7px 15px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 700;
            margin-left: 6px;
        }
        .btn-print { background: #16a34a; color: white; }
        .btn-dl    { background: #2563eb; color: white; }
        .btn-close { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <div>
            @if(!empty($excludeWeekends))
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; border: 1px solid #bbf7d0;">
                    ✓ Weekends Excluded (Optimized)
                </span>
            @endif
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-print">Print Dossier</button>
            <button onclick="downloadPdf()" class="btn btn-dl">Download PDF</button>
            <button onclick="window.close()" class="btn btn-close">Close</button>
        </div>
    </div>

    <div id="report-content" style="max-width: 820px; margin: 0 auto; background: white; padding: 10px;">
        {{-- Institute Header --}}
        <div class="header">
            @php
                $instituteLogo = \App\Models\Setting::get('institute_logo', \App\Models\Setting::getGlobal('institute_logo'));
                $instituteName = \App\Models\Setting::get('institute_name', \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2'));
                $instituteAddress = \App\Models\Setting::get('institute_address', \App\Models\Setting::getGlobal('institute_address'));
            @endphp
            <table>
                <tr>
                    @if($instituteLogo)
                        <td style="width: 60px; vertical-align: middle; padding-right: 14px;">
                            <img src="{{ '/' . $instituteLogo }}" style="height: 52px; max-width: 60px; object-fit: contain;">
                        </td>
                    @endif
                    <td style="text-align: left; vertical-align: middle;">
                        <h1 class="institute-name">{{ $instituteName }}</h1>
                        @if($instituteAddress)
                            <div class="institute-sub">{{ $instituteAddress }}</div>
                        @endif
                        <div class="report-title">
                            Teacher Monthly Attendance &amp; Remarks Dossier
                            @if(!empty($excludeWeekends))
                                <span style="font-size: 10px; font-weight: 600; color: #16a34a; text-transform: none; margin-left: 6px;">(Weekends Excluded)</span>
                            @endif
                        </div>
                    </td>
                    <td style="text-align: right; vertical-align: middle; width: 180px;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 12px;">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Shift: {{ ucfirst($shiftType ?? 'Regular') }}</div>
                        @if(isset($session))
                            <div style="font-size: 10px; color: #64748b;">Session: {{ $session->name }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Teacher Profile Details --}}
        <div class="profile-bar">
            <div class="profile-item">
                <span style="color: #64748b;">Staff Member:</span> <strong>{{ $teacher->name ?? 'N/A' }}</strong>
            </div>
            <div class="profile-item">
                <span style="color: #64748b;">Email:</span> <strong>{{ $teacher->email ?? 'N/A' }}</strong>
            </div>
            <div class="profile-item">
                <span style="color: #64748b;">Role:</span> <strong>Teacher</strong>
            </div>
            <div class="profile-item">
                <span style="color: #64748b;">Working Days:</span> <strong>{{ $summary['working_days'] ?? 0 }}</strong>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-cell">
                    <div class="kpi-title">Present</div>
                    <div class="kpi-val green">{{ $summary['present'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-title">Leave</div>
                    <div class="kpi-val amber">{{ $summary['leave'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-title">Official Duty</div>
                    <div class="kpi-val blue">{{ $summary['official_duty'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-title">Short Leave</div>
                    <div class="kpi-val amber">{{ $summary['short_leave'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-title">Absent</div>
                    <div class="kpi-val red">{{ $summary['absent'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-title">Substitutions</div>
                    <div class="kpi-val purple">{{ $summary['substitutions'] ?? 0 }}</div>
                </div>
                <div class="kpi-cell" style="width: 25%; background: #eff6ff; border-color: #bfdbfe;">
                    <div class="kpi-title" style="color: #1e40af;">Attendance Rate</div>
                    <div class="kpi-val blue" style="font-size: 18px;">{{ $summary['percentage'] ?? 0 }}%</div>
                </div>
            </div>
        </div>

        {{-- Day by Day Attendance Register with Remarks --}}
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">Day</th>
                    <th style="width: 130px;">Date &amp; Day</th>
                    <th style="width: 85px; text-align: center;">Status</th>
                    <th style="min-width: 220px;">Daily Remarks / Notes</th>
                    <th style="min-width: 160px;">Substitutions Taken</th>
                </tr>
            </thead>
            <tbody>
                @forelse($days as $d)
                    @php
                        $st = $d['status'];
                        if ($st === 'Present')         $badgeClass = 'badge-present';
                        elseif ($st === 'Leave')       $badgeClass = 'badge-leave';
                        elseif ($st === 'Short Leave') $badgeClass = 'badge-shortleave';
                        elseif ($st === 'Official Duty')$badgeClass = 'badge-official';
                        elseif ($st === 'Absent')      $badgeClass = 'badge-absent';
                        elseif ($st === 'Weekend')     $badgeClass = 'badge-weekend';
                        elseif ($st === 'Holiday')     $badgeClass = 'badge-holiday';
                        else                           $badgeClass = 'badge-unmarked';
                    @endphp
                    <tr style="{{ $d['is_weekend'] ? 'background-color: #fafafa;' : '' }}">
                        <td style="text-align: center; font-weight: 700; color: #64748b;">
                            {{ $d['day'] }}
                        </td>
                        <td style="white-space: nowrap;">
                            <strong>{{ $d['formatted_date'] }}</strong>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge {{ $badgeClass }}">{{ $st }}</span>
                        </td>
                        <td>
                            @if(!empty($d['remarks']))
                                <span class="remark-text">&ldquo;{{ $d['remarks'] }}&rdquo;</span>
                            @else
                                <span class="remark-empty">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($d['substitutions']))
                                @foreach($d['substitutions'] as $sub)
                                    <span class="duty-badge">
                                        P{{ $sub['period_no'] }}: {{ $sub['class_name'] }} (for {{ $sub['absent_teacher_name'] }})
                                    </span>
                                @endforeach
                            @else
                                <span class="remark-empty">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 25px; color: #94a3b8;">
                            No records found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Signatures Area --}}
        <div class="signatures">
            <div class="sign-box">
                Teacher's Signature
            </div>
            <div class="sign-box">
                Attendance Incharge Signature
            </div>
            <div class="sign-box">
                Principal / Head Signature
            </div>
        </div>

        <div style="margin-top: 25px; text-align: right; font-size: 9px; color: #94a3b8;">
            Generated from SIMS on {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    {{-- html2pdf for Direct Client-side PDF Download --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        function downloadPdf() {
            var element = document.getElementById('report-content');
            var opt = {
                margin:       [8, 8, 8, 8],
                filename:     'Teacher_Attendance_{{ Str::slug($teacher->name ?? "Staff") }}_{{ $month }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, allowTaint: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
