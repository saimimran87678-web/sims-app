<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary timetable of teachers | {{ $instituteName }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 6mm 8mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }
        .sheet-page {
            width: 100%;
            box-sizing: border-box;
            page-break-inside: avoid;
            margin: 0 auto 15px auto;
        }
        .page-break {
            page-break-after: always;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .sheet-page {
                margin: 0 !important;
            }
        }

        .screen-toolbar {
            position: fixed;
            top: 10px;
            right: 15px;
            display: flex;
            gap: 8px;
            z-index: 9999;
            background: rgba(255,255,255,0.96);
            padding: 6px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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

        /* Centered Header matching sample */
        .school-header-block {
            text-align: center;
            margin-bottom: 5px;
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

        /* Matrix Table */
        table.timetable-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            table-layout: fixed;
        }
        table.timetable-table th,
        table.timetable-table td {
            border: 1px solid #000;
            padding: 2px 2px;
            text-align: center;
            vertical-align: middle;
            font-size: 8pt;
            line-height: 1.15;
            overflow: hidden;
            word-wrap: break-word;
        }

        /* Header row cells */
        .th-blank-teacher {
            width: 75px;
            background-color: #fff;
        }
        .th-assembly-head {
            width: 32px;
            font-size: 7.5pt;
            font-weight: bold;
            background-color: #fff;
            padding: 3px 1px;
        }
        .th-lessons-merged {
            font-size: 9pt;
            font-weight: bold;
            background-color: #fff;
            padding: 3px 0;
        }
        .th-sum-head {
            width: 44px;
            font-size: 7.5pt;
            font-weight: bold;
            background-color: #fff;
            padding: 3px 1px;
            line-height: 1.1;
        }

        .th-period-sub {
            font-size: 8pt;
            font-weight: bold;
            background-color: #fff;
            padding: 2px 1px;
        }
        .th-break-sub {
            width: 32px;
            font-size: 7.5pt;
            font-weight: bold;
            background-color: #fff;
            padding: 2px 1px;
        }
        .time-label {
            font-size: 6.5pt;
            font-weight: normal;
            display: block;
            margin-top: 1px;
        }

        /* Body rows */
        .td-teacher-name {
            font-size: 9pt;
            font-weight: bold;
            text-align: left;
            padding-left: 5px !important;
            height: 40px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .td-vertical-text {
            background-color: #fff;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            line-height: 1.35;
            letter-spacing: 1px;
        }

        /* Slot Content */
        .slot-container {
            width: 100%;
        }
        .slot-class {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .slot-subject {
            font-size: 7.5pt;
            font-weight: normal;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }
        .slot-divider {
            border-bottom: 0.5px dashed #888;
            padding: 1px 0;
        }
        .slot-divider:last-child {
            border-bottom: none;
        }

        .td-sum-val {
            font-size: 10pt;
            font-weight: normal;
            text-align: center;
        }

        /* Footer */
        table.footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 4px;
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

    @foreach($teacherPages as $pageIndex => $pageTeachers)
    <div class="sheet-page {{ !$loop->last ? 'page-break' : '' }}">
        {{-- Centered Header matching sample --}}
        <div class="school-header-block">
            <div class="school-main-title">{{ $instituteName }}</div>
            <div class="school-sub-title">Summary timetable of teachers</div>
        </div>

        {{-- Table --}}
        <table class="timetable-table">
            <thead>
                {{-- Row 1: Teacher, Assembly (rowspan 2), Lessons (merged header), Sum of lessons (rowspan 2) --}}
                <tr>
                    <th rowspan="2" class="th-blank-teacher">Teacher</th>

                    @if($assemblyPeriod)
                        <th rowspan="2" class="th-assembly-head">
                            ASSEMBLY
                            @if($assemblyPeriod->start_time && $assemblyPeriod->end_time)
                                <span class="time-label">{{ \Carbon\Carbon::parse($assemblyPeriod->start_time)->format('g:i') }} - {{ \Carbon\Carbon::parse($assemblyPeriod->end_time)->format('g:i') }}</span>
                            @endif
                        </th>
                    @endif

                    <th colspan="{{ count($nonAssemblyPeriods) }}" class="th-lessons-merged">
                        Lessons
                    </th>

                    <th rowspan="2" class="th-sum-head">
                        Sum of<br>lessons
                    </th>
                </tr>

                {{-- Row 2: Sub-columns for Lessons and Break --}}
                <tr>
                    @foreach($nonAssemblyPeriods as $p)
                        @if($p->is_break)
                            <th class="th-break-sub">
                                BREAK
                                @if($p->start_time && $p->end_time)
                                    <span class="time-label">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }} - {{ \Carbon\Carbon::parse($p->end_time)->format('g:i') }}</span>
                                @endif
                            </th>
                        @else
                            <th class="th-period-sub">
                                {{ $lessonOrdinals[$p->period_no] ?? ($p->period_no . 'th') }}
                                @if($p->start_time && $p->end_time)
                                    <span class="time-label">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }} - {{ \Carbon\Carbon::parse($p->end_time)->format('g:i') }}</span>
                                @endif
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($pageTeachers as $tIndex => $t)
                <tr>
                    {{-- Column 1: Teacher Name --}}
                    <td class="td-teacher-name" title="{{ $t->name }}">
                        {{ $t->name }}
                    </td>

                    {{-- Column 2: Assembly --}}
                    @if($assemblyPeriod)
                        @if($loop->first)
                            <td rowspan="{{ count($pageTeachers) }}" class="td-vertical-text">
                                A<br>S<br>S<br>E<br>M<br>B<br>L<br>Y
                            </td>
                        @endif
                    @endif

                    {{-- Period & Break Columns --}}
                    @foreach($nonAssemblyPeriods as $p)
                        @if($p->is_break)
                            @if($loop->parent->first)
                                <td rowspan="{{ count($pageTeachers) }}" class="td-vertical-text">
                                    B<br>R<br>E<br>A<br>K
                                </td>
                            @endif
                        @else
                            @php
                                $slots = $teacherGrid->get($t->id . '_' . $p->period_no, collect());
                            @endphp
                            <td>
                                @if($slots->count() === 1)
                                    @php $s = $slots->first(); @endphp
                                    <div class="slot-container">
                                        <div class="slot-class">{{ $s->class_name ?? '-' }}</div>
                                        <div class="slot-subject">{{ $s->subject_name ?? $s->subject_code ?? '' }}</div>
                                    </div>
                                @elseif($slots->count() > 1)
                                    <div class="slot-container">
                                        @foreach($slots as $s)
                                            <div class="slot-divider">
                                                <div class="slot-class">{{ $s->class_name ?? '-' }}</div>
                                                <div class="slot-subject">{{ $s->subject_name ?? $s->subject_code ?? '' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        @endif
                    @endforeach

                    {{-- Sum of lessons --}}
                    <td class="td-sum-val">
                        {{ $sumOfLessons[$t->id] ?? 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer --}}
        <table class="footer-table">
            <tr>
                <td class="footer-left">Applicable from {{ $effectiveDate }}</td>
                <td class="footer-right">Adminova Timetables</td>
            </tr>
        </table>
    </div>
    @endforeach

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
