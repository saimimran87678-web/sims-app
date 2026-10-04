<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class {{ $class->name }} Timetable | Adminova Timetables</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #111827;
            font-size: 9pt;
        }

        /* Screen Floating Toolbar */
        .screen-toolbar {
            position: fixed;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            border: 1px solid #e5e7eb;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }

        /* Container */
        .sheet-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 6px;
        }

        /* Header matching design standard */
        .sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .inst-logo {
            height: 44px;
            max-width: 55px;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
        }

        .logo-fallback {
            width: 44px;
            height: 44px;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border-radius: 4px;
            background: #f9fafb;
        }

        .header-title-box {
            line-height: 1.2;
        }

        .inst-name {
            font-size: 14pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            margin: 0;
        }

        .sheet-subtitle {
            font-size: 11pt;
            font-weight: 700;
            color: #1f2937;
            margin: 3px 0 0 0;
        }

        .header-right {
            text-align: right;
            line-height: 1.3;
        }

        .system-brand {
            font-size: 10.5pt;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.3px;
        }

        .effective-tag {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 2px;
        }

        /* Class Teacher banner */
        .meta-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 12px;
            margin-bottom: 12px;
        }

        .class-teacher-info {
            font-size: 9.5pt;
            color: #1e293b;
        }

        .class-teacher-info strong {
            font-size: 10.5pt;
            color: #0f172a;
            margin-left: 4px;
        }

        .class-info-badge {
            font-size: 9pt;
            font-weight: 600;
            color: #475569;
        }

        /* Table */
        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        .timetable-table th,
        .timetable-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: middle;
            font-size: 8.5pt;
        }

        .timetable-table thead th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
            text-align: left;
            font-size: 9pt;
        }

        .col-period { width: 14%; }
        .col-time { width: 18%; }
        .col-duration { width: 10%; text-align: center; }
        .col-subject { width: 30%; }
        .col-teacher { width: 28%; }

        .time-badge {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: #334155;
        }

        .duration-badge {
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }

        /* Assembly & Break Rows */
        .span-row {
            background-color: #f8fafc;
            font-weight: 700;
        }

        .span-row td {
            color: #334155;
        }

        .span-label {
            text-align: center;
            letter-spacing: 2px;
            font-weight: 800;
            font-size: 9.5pt;
            color: #475569;
            background-color: #f1f5f9;
        }

        /* Subject / Teacher info */
        .subj-title {
            font-weight: 700;
            font-size: 9.5pt;
            color: #0f172a;
        }

        .teacher-title {
            font-weight: 600;
            font-size: 9pt;
            color: #1e293b;
        }

        /* Clean Divided Split Container (Side-by-side or clean partitioned) */
        .divided-container {
            display: flex;
            width: 100%;
            gap: 12px;
        }

        .divided-partition {
            flex: 1;
            padding: 4px 6px;
            background: #f8fafc;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .divided-subject {
            font-weight: 700;
            font-size: 9pt;
            color: #0f172a;
        }

        .divided-teacher {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 1px;
        }

        .empty-slot {
            color: #94a3b8;
            font-style: italic;
        }

        /* Total lessons row */
        .total-row {
            background-color: #f1f5f9;
            font-weight: 800;
            font-size: 9pt;
        }

        .total-row td {
            border-top: 2px solid #000;
            padding: 7px 10px;
        }

        /* Footer */
        .sheet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    {{-- Screen Floating Actions --}}
    <div class="screen-toolbar no-print">
        <button onclick="window.print()" class="btn-action btn-primary">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf', 'download' => 1]) }}" class="btn-action btn-secondary" style="text-decoration:none;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PDF
        </a>
        <button onclick="window.close()" class="btn-action btn-secondary">
            ✕ Close
        </button>
    </div>

    <div class="sheet-container">
        {{-- Header matching sample --}}
        <div class="sheet-header">
            <div class="header-left">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" class="inst-logo">
                @elseif(!empty($instituteLogo) && file_exists(public_path($instituteLogo)))
                    <img src="{{ '/' . $instituteLogo }}" alt="Logo" class="inst-logo">
                @else
                    <div class="logo-fallback">🏛️</div>
                @endif
                <div class="header-title-box">
                    <h1 class="inst-name">{{ $instituteName }}</h1>
                    <div class="sheet-subtitle">Class {{ $class->name }} Timetable</div>
                </div>
            </div>

            <div class="header-right">
                <div class="system-brand">Adminova Timetables</div>
                <div class="effective-tag">Applicable from: <strong>{{ $effectiveDate }}</strong></div>
            </div>
        </div>

        {{-- Class Teacher & Class Meta Strip --}}
        <div class="meta-strip">
            <div class="class-teacher-info">
                Class In-charge / Teacher: 
                <strong>{{ !empty($classTeacherName) ? $classTeacherName : 'Not Assigned' }}</strong>
            </div>
            <div class="class-info-badge">
                Single Universal Schedule Routine • Working Days
            </div>
        </div>

        {{-- Class Timetable Table --}}
        <table class="timetable-table">
            <thead>
                <tr>
                    <th class="col-period">Period</th>
                    <th class="col-time">Timing</th>
                    <th class="col-duration">Duration</th>
                    <th class="col-subject">Subject</th>
                    <th class="col-teacher">Teacher</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periodRows as $row)
                    @if($row['is_assembly'])
                        <tr class="span-row">
                            <td><strong>ASSEMBLY</strong></td>
                            <td class="time-badge">{{ $row['time_range'] }}</td>
                            <td class="duration-badge">{{ $row['duration'] }} min</td>
                            <td colspan="2" class="span-label">MORNING ASSEMBLY</td>
                        </tr>
                    @elseif($row['is_break'])
                        <tr class="span-row">
                            <td><strong>BREAK</strong></td>
                            <td class="time-badge">{{ $row['time_range'] }}</td>
                            <td class="duration-badge">{{ $row['duration'] }} min</td>
                            <td colspan="2" class="span-label">RECESS / BREAK</td>
                        </tr>
                    @else
                        @php
                            $slots = $row['slots'];
                        @endphp
                        <tr>
                            <td><strong>{{ $row['label'] }}</strong></td>
                            <td class="time-badge">{{ $row['time_range'] }}</td>
                            <td class="duration-badge">{{ $row['duration'] }} min</td>

                            @if($slots->count() === 1)
                                @php $slot = $slots->first(); @endphp
                                <td>
                                    <div class="subj-title">{{ $slot->subject_name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="teacher-title">{{ $slot->teacher_name ?? '-' }}</div>
                                </td>
                            @elseif($slots->count() > 1)
                                {{-- Clean side-by-side divided partition without any DIVIDED CLASS PARTITION tag --}}
                                <td colspan="2" style="padding: 4px;">
                                    <div class="divided-container">
                                        @foreach($slots as $slot)
                                            <div class="divided-partition">
                                                <div class="divided-subject">{{ $slot->subject_name ?? 'Elective' }}</div>
                                                <div class="divided-teacher">Teacher: <strong>{{ $slot->teacher_name ?? '-' }}</strong></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            @else
                                <td colspan="2" class="empty-slot">-</td>
                            @endif
                        </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Sum of Lessons</td>
                    <td colspan="2"><strong>{{ $totalLessons }} Lessons / Day</strong></td>
                </tr>
            </tfoot>
        </table>

        {{-- Footer --}}
        <div class="sheet-footer">
            <span>Generated on {{ now()->format('d/m/Y h:i A') }} • Single Universal Schedule Routine</span>
            <span>Adminova Timetables • Class {{ $class->name }}</span>
        </div>
    </div>

    @if(!empty($autoprint))
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 350);
        });
    </script>
    @endif
</body>
</html>
