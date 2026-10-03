<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Teacher Attendance Register - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header .sub-header {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .header .meta-info {
            font-size: 10px;
            color: #64748b;
        }
        .summary-bar {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 12px;
            font-size: 10px;
        }
        .summary-item {
            font-weight: 600;
        }
        .summary-item span {
            color: #2563eb;
            font-weight: 700;
        }
        
        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 20px;
        }
        table.matrix-table th, table.matrix-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 2px;
            text-align: center;
        }
        table.matrix-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
        }
        table.matrix-table th.teacher-col, table.matrix-table td.teacher-col {
            text-align: left;
            padding-left: 6px;
            white-space: nowrap;
        }
        
        .day-weekend {
            background-color: #f8fafc;
            color: #94a3b8;
            font-weight: normal;
        }
        .day-holiday {
            background-color: #fef2f2;
            color: #ef4444;
            font-weight: bold;
        }
        .code-P {
            color: #15803d;
            font-weight: 700;
        }
        .code-L {
            color: #d97706;
            font-weight: 700;
            background: #fef3c7;
        }
        .code-SL {
            color: #b45309;
            font-weight: 700;
            background: #fef9c3;
        }
        .code-OD {
            color: #1d4ed8;
            font-weight: 700;
            background: #dbeafe;
        }
        .code-A {
            color: #b91c1c;
            font-weight: 800;
            background: #fee2e2;
        }
        .code-W {
            color: #94a3b8;
            font-size: 8px;
        }
        .code-H {
            color: #ef4444;
            font-size: 8px;
            font-weight: 700;
        }
        .code-dash {
            color: #cbd5e1;
        }

        .legend {
            margin-top: 10px;
            font-size: 9px;
            color: #475569;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            width: 180px;
            border-top: 1px solid #475569;
            padding-top: 5px;
            font-weight: 600;
            font-size: 10px;
        }

        .no-print {
            margin-bottom: 15px;
            text-align: right;
        }
        .btn-print {
            padding: 6px 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Print / Save as PDF</button>
    </div>

    <div class="header">
        @php
            $instituteLogo = \App\Models\Setting::get('institute_logo', \App\Models\Setting::getGlobal('institute_logo'));
            $instituteName = \App\Models\Setting::get('institute_name', \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2'));
            $instituteAddress = \App\Models\Setting::get('institute_address', \App\Models\Setting::getGlobal('institute_address'));
        @endphp

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
            <tr>
                @if($instituteLogo)
                    <td style="width: 60px; text-align: left; vertical-align: middle; padding-right: 12px;">
                        <img src="{{ '/' . $instituteLogo }}" style="height: 48px; max-width: 60px; object-fit: contain;">
                    </td>
                @endif
                <td style="text-align: center; vertical-align: middle;">
                    <h1 style="margin: 0 0 2px 0; font-size: 19px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">{{ $instituteName }}</h1>
                    @if($instituteAddress)
                        <div style="font-size: 10px; color: #475569; margin-bottom: 3px;">{{ $instituteAddress }}</div>
                    @endif
                    <div style="font-size: 12px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px;">
                        Monthly Teacher Attendance Register
                    </div>
                </td>
                @if($instituteLogo)
                    <td style="width: 60px;"></td>
                @endif
            </tr>
        </table>

        <div class="sub-header">
            Month: {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }} 
            &nbsp;&bull;&nbsp; Shift: {{ ucfirst($shiftType) }}
            @if(isset($session))
                &nbsp;&bull;&nbsp; Session: {{ $session->name }}
            @endif
        </div>
        <div class="meta-info">
            Generated on: {{ now()->format('l, d F Y - h:i A') }}
        </div>
    </div>

    <div class="summary-bar">
        <div class="summary-item">Total Teachers: <span>{{ $stats['total_teachers'] }}</span></div>
        <div class="summary-item">Working Days: <span>{{ $stats['working_days'] }}</span></div>
        <div class="summary-item">Average Attendance: <span>{{ $stats['avg_attendance'] }}%</span></div>
        <div class="summary-item">Total Leaves: <span>{{ $stats['total_leaves'] }}</span></div>
        <div class="summary-item">Total Absences: <span>{{ $stats['total_absences'] }}</span></div>
        <div class="summary-item">Substitutions Covered: <span>{{ $stats['total_substitutions'] }}</span></div>
    </div>

    <table class="matrix-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">S#</th>
                <th rowspan="2" class="teacher-col" style="min-width: 140px;">Teacher Name</th>
                <th colspan="{{ count($days) }}">Days of the Month</th>
                <th colspan="7">Monthly Summary</th>
            </tr>
            <tr>
                @foreach($days as $day)
                    <th class="{{ $day['is_weekend'] ? 'day-weekend' : ($day['is_holiday'] ? 'day-holiday' : '') }}" style="width: 16px;">
                        <div>{{ $day['day'] }}</div>
                        <div style="font-size: 7px; color: #64748b;">{{ substr($day['day_name'], 0, 1) }}</div>
                    </th>
                @endforeach
                <th style="width: 22px; background: #dcfce7; color: #166534;" title="Present">P</th>
                <th style="width: 22px; background: #fef3c7; color: #92400e;" title="Leave">L</th>
                <th style="width: 22px; background: #fef9c3; color: #854d0e;" title="Short Leave">SL</th>
                <th style="width: 22px; background: #dbeafe; color: #1e40af;" title="Official Duty">OD</th>
                <th style="width: 22px; background: #fee2e2; color: #991b1b;" title="Absent">A</th>
                <th style="width: 25px; background: #f3e8ff; color: #6b21a8;" title="Substitute Duties">Subs</th>
                <th style="width: 32px; background: #e0f2fe; color: #0369a1;" title="Attendance %">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($matrix as $idx => $row)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="teacher-col">
                        <strong>{{ $row['name'] }}</strong>
                    </td>

                    @foreach($days as $day)
                        @php
                            $c = $row['days'][$day['day']]['code'] ?? '-';
                        @endphp
                        <td class="{{ $day['is_weekend'] ? 'day-weekend' : ($day['is_holiday'] ? 'day-holiday' : '') }}">
                            @if($c === 'P')
                                <span class="code-P">P</span>
                            @elseif($c === 'L')
                                <span class="code-L">L</span>
                            @elseif($c === 'SL')
                                <span class="code-SL">SL</span>
                            @elseif($c === 'OD')
                                <span class="code-OD">OD</span>
                            @elseif($c === 'A')
                                <span class="code-A">A</span>
                            @elseif($c === 'W')
                                <span class="code-W">-</span>
                            @elseif($c === 'H')
                                <span class="code-H">H</span>
                            @else
                                <span class="code-dash">-</span>
                            @endif
                        </td>
                    @endforeach

                    <td style="font-weight: 700; color: #15803d; background: #f0fdf4;">{{ $row['present'] }}</td>
                    <td style="font-weight: 700; color: #d97706; background: #fffbeb;">{{ $row['leave'] }}</td>
                    <td style="font-weight: 700; color: #b45309; background: #fefce8;">{{ $row['short_leave'] }}</td>
                    <td style="font-weight: 700; color: #1d4ed8; background: #eff6ff;">{{ $row['official_duty'] }}</td>
                    <td style="font-weight: 700; color: #b91c1c; background: #fef2f2;">{{ $row['absent'] }}</td>
                    <td style="font-weight: 700; color: #6b21a8; background: #faf5ff;">{{ $row['substitutions'] }}</td>
                    <td style="font-weight: 800; color: #0369a1; background: #f0f9ff;">{{ $row['percentage'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($days) + 9 }}" style="text-align: center; padding: 20px; color: #94a3b8;">
                        No teachers enrolled or attendance records found for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend">
        <span style="font-weight: 700; margin-right: 5px;">Codes:</span>
        <span class="legend-item"><strong class="code-P">P:</strong> Present</span>
        <span class="legend-item"><strong class="code-L">L:</strong> Leave</span>
        <span class="legend-item"><strong class="code-SL">SL:</strong> Short Leave</span>
        <span class="legend-item"><strong class="code-OD">OD:</strong> Official Duty</span>
        <span class="legend-item"><strong class="code-A">A:</strong> Absent</span>
        <span class="legend-item"><strong class="code-H">H:</strong> Holiday</span>
        <span class="legend-item"><strong class="code-W">-:</strong> Weekend</span>
    </div>

    <div class="signatures">
        <div class="sign-box">Prepared By (Incharge)</div>
        <div class="sign-box">Checked By (Vice Principal)</div>
        <div class="sign-box">Approved By (Principal)</div>
    </div>
</body>
</html>
