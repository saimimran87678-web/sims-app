<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class {{ $class->name }} Timetable | {{ $instituteName }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            font-size: 8.5pt;
        }

        @media print {
            .no-print {
                display: none !important;
            }
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
            border-radius: 6px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
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
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .sheet-container {
            width: 100%;
            margin: 0 auto;
            page-break-inside: avoid;
        }

        /* Header matching design standard */
        .school-header-block {
            text-align: center;
            margin-bottom: 6px;
        }
        .school-main-title {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .school-sub-title {
            font-size: 11.5pt;
            font-weight: normal;
            margin: 0;
            line-height: 1.2;
        }

        /* Meta strip using table for 100% DomPDF compatibility */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
            background-color: #f8fafc;
            margin-bottom: 8px;
        }
        table.meta-table td {
            padding: 5px 10px;
            font-size: 9pt;
            border: none;
        }
        .meta-left {
            text-align: left;
            color: #000;
        }
        .meta-right {
            text-align: right;
            color: #475569;
        }

        /* Timetable Table */
        table.timetable-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            table-layout: fixed;
        }

        table.timetable-table th,
        table.timetable-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5pt;
            line-height: 1.2;
        }

        table.timetable-table thead th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #000;
            text-align: left;
            font-size: 9pt;
        }

        .col-period { width: 14%; }
        .col-time { width: 18%; }
        .col-duration { width: 10%; text-align: center; }
        .col-subject { width: 30%; }
        .col-teacher { width: 28%; }

        .time-badge {
            font-weight: bold;
            color: #000;
        }

        .duration-badge {
            font-size: 8pt;
            color: #444;
            text-align: center;
        }

        /* Assembly & Break Rows */
        .span-row {
            background-color: #fbfbfb;
            font-weight: bold;
        }

        .span-label {
            text-align: center;
            letter-spacing: 2px;
            font-weight: bold;
            font-size: 9pt;
            color: #333;
            background-color: #f3f4f6;
        }

        .subj-title {
            font-weight: bold;
            font-size: 9pt;
            color: #000;
        }

        .teacher-title {
            font-size: 8.5pt;
            color: #000;
        }

        /* Split container using table for DomPDF */
        table.split-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        table.split-table td {
            border: none;
            padding: 2px 4px;
            vertical-align: top;
        }
        .split-card {
            border: 1px solid #ccc;
            padding: 3px 5px;
            background: #fafafa;
        }

        .empty-slot {
            color: #888;
            font-style: italic;
        }

        .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        /* Footer */
        table.footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 6px;
            font-size: 7.5pt;
            color: #000;
        }
        table.footer-table td {
            border: none;
            padding: 0;
        }
        .footer-left {
            text-align: left;
        }
        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>

    @if(empty($isPdf))
    <div class="screen-toolbar no-print">
        <button onclick="triggerPrintAndDownload()" class="btn-action btn-primary">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <button onclick="window.close()" class="btn-action btn-secondary">
            ✕ Close
        </button>
    </div>
    @endif

    <div class="sheet-container">
        {{-- Centered Header --}}
        <div class="school-header-block">
            <div class="school-main-title">{{ $instituteName }}</div>
            <div class="school-sub-title">Class {{ $class->name }} Timetable</div>
        </div>

        {{-- Class Teacher & Class Meta Strip --}}
        <table class="meta-table">
            <tr>
                <td class="meta-left">
                    Class In-charge / Teacher: 
                    <strong>{{ !empty($classTeacherName) ? $classTeacherName : 'Not Assigned' }}</strong>
                </td>
                <td class="meta-right">
                    Applicable from: <strong>{{ $effectiveDate }}</strong>
                </td>
            </tr>
        </table>

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
                    @if(!empty($row['is_assembly']))
                        <tr class="span-row">
                            <td><strong>ASSEMBLY</strong></td>
                            <td class="time-badge">{{ $row['time_range'] }}</td>
                            <td class="duration-badge">{{ $row['duration'] }} min</td>
                            <td colspan="2" class="span-label">MORNING ASSEMBLY</td>
                        </tr>
                    @elseif(!empty($row['is_break']))
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
                                <td colspan="2" style="padding: 2px;">
                                    <table class="split-table">
                                        <tr>
                                            @foreach($slots as $slot)
                                                <td>
                                                    <div class="split-card">
                                                        <div class="subj-title">{{ $slot->subject_name ?? 'Elective' }}</div>
                                                        <div class="teacher-title">Teacher: <strong>{{ $slot->teacher_name ?? '-' }}</strong></div>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    </table>
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
        <table class="footer-table">
            <tr>
                <td class="footer-left">Applicable from {{ $effectiveDate }} • Single Universal Schedule Routine</td>
                <td class="footer-right">Adminova Timetables • Class {{ $class->name }}</td>
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

        @if(!empty($autoprint))
        window.addEventListener('load', function() {
            triggerDownloadPdf();
            setTimeout(function() {
                window.print();
            }, 550);
        });
        @endif
    </script>
    @endif
</body>
</html>
