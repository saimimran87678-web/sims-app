<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Teachers Attendance Reports - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</title>
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
            background: #f1f5f9;
        }
        .header {
            margin-bottom: 14px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .institute-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .institute-sub {
            font-size: 10px;
            color: #475569;
        }
        .report-title {
            font-size: 12px;
            font-weight: 800;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }
        
        .profile-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .profile-item {
            font-size: 10.5px;
        }
        .profile-item strong {
            color: #0f172a;
        }

        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-spacing: 5px;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-cell {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
            width: 12.5%;
        }
        .kpi-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-val {
            font-size: 15px;
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
            font-size: 9.5px;
            margin-bottom: 20px;
        }
        table.attendance-table th, table.attendance-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: middle;
        }
        table.attendance-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.3px;
            text-align: left;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8.5px;
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
            font-size: 9.5px;
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
            font-size: 8.5px;
            font-weight: 600;
            display: inline-block;
            margin-right: 4px;
            margin-bottom: 2px;
        }

        .signatures {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            width: 160px;
            border-top: 1px solid #475569;
            padding-top: 5px;
            font-weight: 600;
            font-size: 9.5px;
            color: #334155;
        }

        .teacher-page {
            max-width: 820px;
            margin: 0 auto 30px auto;
            background: white;
            padding: 22px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            page-break-after: always;
            break-after: page;
        }
        .teacher-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }

        .no-print {
            position: sticky;
            top: 10px;
            z-index: 100;
            max-width: 820px;
            margin: 0 auto 20px auto;
            background: white;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .btn-print { background: #7c3aed; color: white; }
        .btn-close { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .teacher-page {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .teacher-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $instituteLogo = \App\Models\Setting::get('institute_logo', \App\Models\Setting::getGlobal('institute_logo'));
        $instituteName = \App\Models\Setting::getFormalInstituteName();
        $instituteAddress = \App\Models\Setting::get('institute_address', \App\Models\Setting::getGlobal('institute_address'));
    @endphp

    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 10px;">
            <strong style="font-size: 13px; color: #0f172a;">All Teachers Monthly Dossiers ({{ count($allTeachersData) }} Teachers)</strong>
            @if(!empty($excludeWeekends))
                <span style="display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #166534; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 10px; border: 1px solid #bbf7d0;">
                    ✓ Weekends Excluded
                </span>
            @endif
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-print">Print All Dossiers</button>
            <button onclick="window.close()" class="btn btn-close">Close</button>
        </div>
    </div>

    @forelse($allTeachersData as $index => $tData)
        @php
            $teacher = $tData['teacher'] ?? null;
            $summary = $tData['summary'] ?? [];
            $days = $tData['days'] ?? [];
        @endphp
        <div class="teacher-page">
            {{-- Institute Header --}}
            <div class="header">
                <table>
                    <tr>
                        @if($instituteLogo)
                            <td style="width: 55px; vertical-align: middle; padding-right: 12px;">
                                <img src="{{ '/' . $instituteLogo }}" style="height: 48px; max-width: 55px; object-fit: contain;">
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
                                    <span style="font-size: 9.5px; font-weight: 600; color: #16a34a; text-transform: none; margin-left: 6px;">(Weekends Excluded)</span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align: right; vertical-align: middle; width: 180px;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</div>
                            <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">Shift: {{ ucfirst($shiftType ?? 'Regular') }}</div>
                            @if(isset($session))
                                <div style="font-size: 9.5px; color: #64748b;">Session: {{ $session->name }}</div>
                            @endif
                            <div style="font-size: 8.5px; color: #94a3b8; margin-top: 2px;">Staff {{ $index + 1 }} of {{ count($allTeachersData) }}</div>
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
                        <div class="kpi-val blue" style="font-size: 16px;">{{ $summary['percentage'] ?? 0 }}%</div>
                    </div>
                </div>
            </div>

            {{-- Day by Day Attendance Register with Remarks --}}
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width: 28px; text-align: center;">Day</th>
                        <th style="width: 120px;">Date &amp; Day</th>
                        <th style="width: 80px; text-align: center;">Status</th>
                        <th style="min-width: 220px;">Daily Remarks / Notes</th>
                        <th style="min-width: 150px;">Substitutions Taken</th>
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
                            <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
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

            <div style="margin-top: 18px; text-align: right; font-size: 8.5px; color: #94a3b8;">
                Generated from SIMS on {{ now()->format('Y-m-d H:i') }} &bull; Staff {{ $index + 1 }} of {{ count($allTeachersData) }}
            </div>
        </div>
    @empty
        <div style="max-width: 820px; margin: 40px auto; background: white; padding: 40px; border-radius: 12px; text-align: center; color: #64748b;">
            <h3 style="margin-top: 0; color: #0f172a;">No Teachers Found</h3>
            <p>No active teachers are enrolled for this academic session and shift.</p>
        </div>
    @endforelse
</body>
</html>
