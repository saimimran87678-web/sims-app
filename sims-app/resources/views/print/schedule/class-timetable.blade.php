<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class {{ $class->name }} Timetable | {{ $instituteName ?? 'School Timetable' }}</title>
    <style>
        @page {
            size: a4 landscape;
            /* Balanced page margins: 10mm top gives clean breathing room in Dompdf & print without overflow */
            margin: 10mm 8mm 6mm 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html, body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000000;
            font-family: 'Arial Narrow', 'Liberation Sans Narrow', 'Roboto Condensed', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            -webkit-text-size-adjust: 100%;
        }

        /* Container strictly bounded to prevent multi-page overflow */
        .sheet-container {
            width: 100%;
            max-width: 281mm;
            margin: 0 auto;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            background-color: #ffffff;
        }

        @media screen {
            body {
                padding-top: 14px;
                padding-bottom: 14px;
                background-color: #f3f4f6;
            }
            .sheet-container {
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
                border: 1px solid #e5e7eb;
                padding: 2mm 6mm 4mm 6mm;
                background-color: #ffffff;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                width: 100%;
                background: #ffffff;
                padding: 0 !important;
                margin: 0 !important;
            }
            .sheet-container {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.timetable-table, tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Screen Floating Toolbar (suppressed in PDF and print) */
        .screen-toolbar {
            position: fixed;
            top: 12px;
            right: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 99999;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            padding: 6px 12px;
            border-radius: 6px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            border: 1px solid #d1d5db;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .btn-primary {
            background-color: #1e3a8a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #172554;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        /* Institutional Title Block */
        .school-header-block {
            text-align: center;
            padding-top: 1mm;
            padding-bottom: 0;
            margin-bottom: 2.5mm;
        }

        .school-main-title {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.15;
            color: #000000;
        }

        .school-sub-title {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 1px 0 0 0;
            letter-spacing: 0.4px;
            color: #000000;
        }

        /* Class In-Charge & Metadata Box */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000000;
            margin: 0 0 2.5mm 0;
            background-color: #ffffff;
        }

        table.meta-table td {
            padding: 3.5px 12px;
            font-size: 9.5pt;
            border: none;
            color: #000000;
            line-height: 1.2;
        }

        .meta-left {
            text-align: left;
        }

        .meta-right {
            text-align: right;
        }

        /* Main Timetable Matrix: Uniform 1.5px Borders */
        table.timetable-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.5px solid #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Enforce header height on the <th> tag for Dompdf compatibility */
        table.timetable-table thead th {
            background-color: #ffffff;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 7px 16px;
            height: 9.5mm;
            border: 1.5px solid #000000;
            color: #000000;
            vertical-align: middle;
            line-height: 1.2;
        }

        table.timetable-table tbody tr.period-row {
            height: 15mm;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        table.timetable-table tbody td {
            border: 1.5px solid #000000;
            padding: 0 16px;
            vertical-align: middle;
            font-size: 10pt;
            line-height: 1.2;
            word-break: break-word;
            height: 15mm;
        }

        /* Period Cell: Inline formatting works reliably in both Dompdf and Browser */
        .period-cell-content {
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
        }

        .period-title {
            display: inline;
            font-size: 11pt;
            font-weight: 700;
            color: #000000;
            letter-spacing: 0.2px;
            margin-right: 6px;
        }

        .period-interval {
            display: inline;
            font-size: 10pt;
            font-weight: 600;
            color: #111111;
            margin-right: 6px;
        }

        .period-dur {
            display: inline;
            font-size: 9pt;
            font-weight: 500;
            color: #333333;
        }

        /* Centered Special Activity Label */
        .span-activity-title {
            text-align: center;
            font-weight: 700;
            font-size: 11.5pt;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #000000;
        }

        /* Dompdf-Engine Safe Table Structure for Single Lessons */
        table.slot-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
            padding: 0;
            table-layout: auto;
        }

        table.slot-table td {
            border: none !important;
            padding: 0 !important;
            height: 15mm;
            vertical-align: middle;
        }

        .slot-td-left {
            text-align: left;
            vertical-align: middle;
            padding-right: 8px !important;
        }

        .slot-td-right {
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
            width: 1%;
        }

        .subj-title {
            font-size: 12.5pt;
            font-weight: 700;
            color: #000000;
            letter-spacing: 0.1px;
            line-height: 1.15;
        }

        .teacher-info {
            font-size: 11pt;
            font-weight: 500;
            color: #111111;
            line-height: 1.15;
        }

        .teacher-info strong {
            font-weight: 700;
            font-size: 11.5pt;
            color: #000000;
        }

        /* Divided Slots: Zero-padding wrapper with DIV table-cell partitioning */
        .td-split-cell {
            padding: 0 !important;
            height: 15mm;
            vertical-align: middle;
        }

        .split-wrapper {
            display: table;
            width: 100%;
            height: 15mm;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }

        .split-col {
            display: table-cell;
            vertical-align: middle;
            padding: 0 10px;
            height: 15mm;
            box-sizing: border-box;
        }

        /* Single uniform 1.5px vertical divider matching outer table */
        .split-col + .split-col {
            border-left: 1.5px solid #000000;
        }

        .empty-slot {
            color: #444444;
            font-weight: 500;
            text-align: center;
            font-size: 10.5pt;
            letter-spacing: 0.5px;
        }

        /* Footer */
        .page-footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 2.5mm;
            font-size: 8pt;
            color: #111111;
        }

        .page-footer-table td {
            border: none;
            padding: 0;
            line-height: 1.2;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
            font-weight: 400;
        }
    </style>
</head>
<body>

    @if(empty($isPdf))
    <div class="screen-toolbar no-print">
        <button onclick="triggerPrintAndDownload()" class="btn-action btn-primary" title="Print document">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Print Timetable</span>
        </button>
        <button onclick="window.close()" class="btn-action btn-secondary" title="Close window">
            ✕ Close
        </button>
    </div>
    @endif

    @php
        $calcDuration = function($row) {
            $startStr = $row['start_time'] ?? null;
            $endStr = $row['end_time'] ?? null;

            if ((empty($startStr) || empty($endStr)) && !empty($row['time_range']) && str_contains($row['time_range'], '-')) {
                $cleanRange = trim($row['time_range'], '() ');
                $parts = explode('-', $cleanRange);
                if (count($parts) === 2) {
                    $startStr = trim($parts[0]);
                    $endStr = trim($parts[1]);
                }
            }

            if (!empty($startStr) && !empty($endStr)) {
                try {
                    $toMinutes = function($timeStr) {
                        $timeStr = trim($timeStr);
                        $isPm = preg_match('/pm/i', $timeStr);
                        $isAm = preg_match('/am/i', $timeStr);
                        $clean = preg_replace('/[^0-9:]/', '', $timeStr);
                        $parts = explode(':', $clean);
                        $h = (int)($parts[0] ?? 0);
                        $m = (int)($parts[1] ?? 0);

                        if (!$isAm && ($isPm || ($h >= 1 && $h <= 6))) {
                            $h += 12;
                        }
                        return ($h * 60) + $m;
                    };

                    $startMin = $toMinutes($startStr);
                    $endMin = $toMinutes($endStr);

                    if ($endMin < $startMin) {
                        $endMin += 720;
                    }

                    $diff = $endMin - $startMin;
                    if ($diff > 0 && $diff <= 180) {
                        return $diff;
                    }
                } catch (\Exception $ex) {}
            }

            if (!empty($row['duration'])) {
                $val = abs((int)$row['duration']);
                if ($val > 0 && $val <= 180) {
                    return $val;
                }
            }

            return null;
        };
    @endphp

    <div class="sheet-container">
        <div>
            <div class="school-header-block">
                <div class="school-main-title">{{ $instituteName ?? 'ISLAMABAD MODEL COLLEGE FOR BOYS G-6/2, ISLAMABAD' }}</div>
                <div class="school-sub-title">CLASS TIMETABLE: {{ strtoupper($class->name) }}</div>
            </div>

            <table class="meta-table">
                <tr>
                    <td class="meta-left">
                        Class Teacher: <strong>{{ !empty($classTeacherName) ? $classTeacherName : 'Not Assigned' }}</strong>
                    </td>
                    <td class="meta-right">
                        Applicable from: <strong>{{ $effectiveDate }}</strong>
                    </td>
                </tr>
            </table>
        </div>

        <table class="timetable-table">
            <colgroup>
                {{-- Strict inline percentage widths for 100% Dompdf and Browser parity --}}
                <col style="width: 32%;">
                <col style="width: 68%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="width: 32%; text-align: left;">PERIOD & TIME INTERVAL</th>
                    <th style="width: 68%; text-align: left;">SUBJECT & ASSIGNED TEACHER</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periodRows as $row)
                    @php
                        $duration = $calcDuration($row);
                        $cleanRange = !empty($row['time_range']) ? trim($row['time_range'], '() ') : '';
                    @endphp

                    @if(!empty($row['is_assembly']))
                        <tr class="period-row">
                            <td>
                                <div class="period-cell-content">
                                    <span class="period-title">ASSEMBLY</span>
                                    @if($cleanRange)
                                        <span class="period-interval">({{ $cleanRange }})</span>
                                    @endif
                                    @if($duration)
                                        <span class="period-dur">[{{ $duration }} min]</span>
                                    @endif
                                </div>
                            </td>
                            <td class="span-activity-title">ASSEMBLY</td>
                        </tr>
                    @elseif(!empty($row['is_break']))
                        <tr class="period-row">
                            <td>
                                <div class="period-cell-content">
                                    <span class="period-title">BREAK</span>
                                    @if($cleanRange)
                                        <span class="period-interval">({{ $cleanRange }})</span>
                                    @endif
                                    @if($duration)
                                        <span class="period-dur">[{{ $duration }} min]</span>
                                    @endif
                                </div>
                            </td>
                            <td class="span-activity-title">BREAK</td>
                        </tr>
                    @else
                        @php
                            $slots = $row['slots'] ?? collect();
                        @endphp
                        <tr class="period-row">
                            <td>
                                <div class="period-cell-content">
                                    <span class="period-title">{{ $row['label'] }}</span>
                                    @if($cleanRange)
                                        <span class="period-interval">({{ $cleanRange }})</span>
                                    @endif
                                    @if($duration)
                                        <span class="period-dur">[{{ $duration }} min]</span>
                                    @endif
                                </div>
                            </td>
                            <td @if($slots->count() > 1) class="td-split-cell" @endif>
                                @if($slots->count() === 1)
                                    @php $slot = $slots->first(); @endphp
                                    {{-- Borderless 2-cell table guarantees baseline parity in both Dompdf and Browser --}}
                                    <table class="slot-table">
                                        <tr>
                                            <td class="slot-td-left">
                                                <span class="subj-title">{{ $slot->subject_name ?? '-' }}</span>
                                            </td>
                                            <td class="slot-td-right">
                                                <span class="teacher-info">
                                                    Teacher: <strong>{{ $slot->teacher_name ?? '-' }}</strong>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                @elseif($slots->count() > 1)
                                    {{-- Split wrapper with table-cell layout prevents nested page breaks --}}
                                    <div class="split-wrapper">
                                        @foreach($slots as $slot)
                                            <div class="split-col" style="width: {{ number_format(100 / $slots->count(), 2) }}%;">
                                                <table class="slot-table">
                                                    <tr>
                                                        <td class="slot-td-left">
                                                            <span class="subj-title" style="font-size: 11.5pt;">
                                                                {{ $slot->subject_name ?? 'Elective' }}
                                                            </span>
                                                        </td>
                                                        <td class="slot-td-right">
                                                            <span class="teacher-info" style="font-size: 10pt;">
                                                                Teacher: <strong>{{ $slot->teacher_name ?? '-' }}</strong>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-slot">— Free Period —</div>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <table class="page-footer-table">
            <tr>
                <td class="footer-left">Applicable from {{ $effectiveDate }}</td>
                <td class="footer-right">Adminova Timetables</td>
            </tr>
        </table>
    </div>

    @if(empty($isPdf))
    <script>
        function triggerDownloadPdf() {
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('format', 'pdf');
                url.searchParams.set('download', '1');
                url.searchParams.delete('autoprint');
                var dlFrame = document.createElement('iframe');
                dlFrame.style.display = 'none';
                dlFrame.src = url.toString();
                document.body.appendChild(dlFrame);
            } catch (e) {
                console.error('PDF auto-download failed', e);
            }
        }

        function triggerPrintAndDownload() {
            triggerDownloadPdf();
            setTimeout(function() {
                window.print();
            }, 350);
        }
    </script>
    @endif

    @if(!empty($autoprint))
    <script>
        window.addEventListener('load', function() {
            triggerDownloadPdf();
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
    @endif
</body>
</html>