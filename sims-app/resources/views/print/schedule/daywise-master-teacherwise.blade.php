<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary timetable of teachers - {{ $isSingleDay ? $targetDays[0] : 'All Days' }} | {{ $instituteName ?? 'School Timetable' }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 5mm 6mm; /* Tight print margins matching aSc Timetables */
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
            /* Condensed font stack to match aSc high-density metrics */
            font-family: 'Arial Narrow', 'Liberation Sans Narrow', 'Roboto Condensed', 'Nimbus Sans L', Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.15;
            -webkit-text-size-adjust: 100%;
        }

        .sheet-page {
            width: 100%;
            max-width: 285mm;
            margin: 0 auto 16px auto;
            page-break-inside: avoid;
            page-break-after: always;
            break-inside: avoid;
            break-after: page;
        }

        .sheet-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                width: 100%;
                background: #fff;
            }
            .sheet-page {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }

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

        .school-header-block {
            text-align: center;
            margin-bottom: 4px;
            padding-top: 1px;
        }

        .school-main-title {
            font-size: 13.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 0;
            line-height: 1.15;
            color: #000;
        }

        .school-sub-title {
            font-size: 10.5pt;
            font-weight: 400;
            margin: 1px 0 0 0;
            line-height: 1.15;
            color: #111;
        }

        .day-title-badge {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }

        table.timetable-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Strictly bound by colgroup for stable columns */
            border: 1.25px solid #000000;
            background-color: #ffffff;
        }

        table.timetable-table th,
        table.timetable-table td {
            border: 0.75px solid #000000;
            padding: 1.5px 1.5px;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5pt;
            line-height: 1.12;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .th-blank-teacher {
            background-color: #ffffff;
            border-bottom: 0.75px solid #000000;
            font-size: 8pt;
            font-weight: 700;
        }

        .th-assembly-head {
            font-size: 7.2pt;
            font-weight: 700;
            background-color: #ffffff;
            padding: 4px 2px;
            line-height: 1.25;
            letter-spacing: 0.4px;
            white-space: nowrap;
            min-width: 50px;
        }

        .th-lessons-merged {
            font-size: 8.5pt;
            font-weight: 700;
            background-color: #ffffff;
            padding: 2.5px 0;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .th-period-sub {
            font-size: 7.5pt;
            font-weight: 700;
            background-color: #ffffff;
            padding: 2px 1px;
            line-height: 1.08;
        }

        .th-break-sub {
            font-size: 7pt;
            font-weight: 700;
            background-color: #ffffff;
            padding: 2px 1px;
            line-height: 1.05;
        }

        .time-label {
            font-size: 6.2pt;
            font-weight: 400;
            display: block;
            margin-top: 2px;
            letter-spacing: -0.1px;
            white-space: nowrap;
            color: #111;
        }

        .td-teacher-name {
            font-size: 8.2pt;
            font-weight: 700;
            text-align: center;
            padding: 3px 2px;
            background-color: #ffffff;
            letter-spacing: 0.1px;
            white-space: normal; /* Enables multi-line wrap for long teacher names */
            line-height: 1.1;
        }

        .td-vertical-text {
            background-color: #ffffff;
            font-size: 6.8pt;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            line-height: 1.25;
            letter-spacing: 0.5px;
            padding: 2px 1px;
        }

        .vertical-char-stack {
            display: inline-block;
            line-height: 1.25;
            font-weight: 700;
        }

        .slot-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 24px;
        }

        .slot-item {
            padding: 1px 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Subtle hairline divider between split elective assignments */
        .slot-item + .slot-item {
            border-top: 0.5px solid #000000;
            margin-top: 1.5px;
            padding-top: 1.5px;
        }

        .slot-class {
            font-size: 7.8pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.05;
            white-space: normal;
        }

        .slot-subject {
            font-size: 7.0pt;
            font-weight: 400;
            color: #111111;
            line-height: 1.1;
            white-space: normal;
            margin-top: 0.5px;
        }

        .th-sum-head {
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.1;
            padding: 2px 1px;
            background-color: #ffffff;
        }

        .td-sum-val {
            font-size: 8.5pt;
            font-weight: 700;
            background-color: #ffffff;
            letter-spacing: 0.2px;
        }

        .page-footer-table {
            width: 100%;
            margin-top: 5px;
            font-size: 7pt;
            color: #111111;
            border-collapse: collapse;
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
        // Count non-break and break periods to compute precise column ratios
        $nonBreakCount = collect($nonAssemblyPeriods)->where('is_break', false)->count();
        $breakCount = collect($nonAssemblyPeriods)->where('is_break', true)->count();
        $hasAssembly = !empty($assemblyPeriod);

        // Teacher column gets 8.0% to provide room for longer names
        $fixedWidthPercentage = 8.0 + ($hasAssembly ? 4.8 : 0) + ($breakCount * 3.8) + 4.4;
        $periodWidthPercentage = $nonBreakCount > 0 ? ((100 - $fixedWidthPercentage) / $nonBreakCount) : 11.0;
    @endphp

    @foreach($daysData as $dayPacket)
        @php
            $currentDay = $dayPacket['day'];
            $teacherGrid = $dayPacket['teacherGrid'];
            $sumOfLessons = $dayPacket['sumOfLessons'];
        @endphp

        @foreach($teacherPages as $pageIndex => $pageTeachers)
        <div class="sheet-page">
            <div class="school-header-block">
                <div class="school-main-title">{{ $instituteName ?? 'ISLAMABAD MODEL COLLEGE FOR BOYS G-6/2, ISLAMABAD' }}</div>
                <div class="school-sub-title">
                    Summary timetable of teachers &mdash; <span class="day-title-badge">{{ strtoupper($currentDay) }}</span>
                </div>
            </div>

            <table class="timetable-table">
                <colgroup>
                    {{-- Column 1: Teacher Name --}}
                    <col style="width: 8.0%; min-width: 75px;">

                    {{-- Column 2: Assembly --}}
                    @if($hasAssembly)
                        <col style="width: 4.8%; min-width: 50px;">
                    @endif

                    {{-- Lesson Periods & Break Columns --}}
                    @foreach($nonAssemblyPeriods as $p)
                        @if($p->is_break)
                            <col style="width: 3.8%; min-width: 44px;">
                        @else
                            <col style="width: {{ number_format($periodWidthPercentage, 2) }}%;">
                        @endif
                    @endforeach

                    {{-- Column Last: Sum of Lessons --}}
                    <col style="width: 4.4%;">
                </colgroup>

                <thead>
                    {{-- Header Row 1: Merged Lessons & Anchors --}}
                    <tr>
                        <th rowspan="2" class="th-blank-teacher">Teacher</th>

                        @if($hasAssembly)
                            <th rowspan="2" class="th-assembly-head">
                                ASSEMBLY
                                @if(!empty($assemblyPeriod->start_time) && !empty($assemblyPeriod->end_time))
                                    <span class="time-label">{{ \Carbon\Carbon::parse($assemblyPeriod->start_time)->format('g:i') }}-{{ \Carbon\Carbon::parse($assemblyPeriod->end_time)->format('g:i') }}</span>
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

                    {{-- Header Row 2: Period Ordinals and Times --}}
                    <tr>
                        @foreach($nonAssemblyPeriods as $p)
                            @if($p->is_break)
                                <th class="th-break-sub">
                                    BREAK
                                    @if(!empty($p->start_time) && !empty($p->end_time))
                                        <span class="time-label">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }}-{{ \Carbon\Carbon::parse($p->end_time)->format('g:i') }}</span>
                                    @endif
                                </th>
                            @else
                                <th class="th-period-sub">
                                    {{ $lessonOrdinals[$p->period_no] ?? ($p->period_no . 'th') }}
                                    @if(!empty($p->start_time) && !empty($p->end_time))
                                        <span class="time-label">{{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }}-{{ \Carbon\Carbon::parse($p->end_time)->format('g:i') }}</span>
                                    @endif
                                </th>
                            @endif
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($pageTeachers as $tIndex => $t)
                    <tr>
                        {{-- Teacher Identifier --}}
                        <td class="td-teacher-name" title="{{ $t->name }}">
                            {{ $t->name }}
                        </td>

                        {{-- Assembly Spanned Column --}}
                        @if($hasAssembly)
                            @if($loop->first)
                                <td rowspan="{{ count($pageTeachers) }}" class="td-vertical-text">
                                    <span class="vertical-char-stack">
                                        A<br>S<br>S<br>E<br>M<br>B<br>L<br>Y
                                    </span>
                                </td>
                            @endif
                        @endif

                        {{-- Period Columns --}}
                        @foreach($nonAssemblyPeriods as $p)
                            @if($p->is_break)
                                {{-- Break Spanned Column --}}
                                @if($loop->parent->first)
                                    <td rowspan="{{ count($pageTeachers) }}" class="td-vertical-text">
                                        <span class="vertical-char-stack">
                                            B<br>R<br>E<br>A<br>K
                                        </span>
                                    </td>
                                @endif
                            @else
                                {{-- Period Lesson Slot --}}
                                @php
                                    $slots = $teacherGrid->get($t->id . '_' . $p->period_no, collect());
                                @endphp
                                <td>
                                    @if($slots->isNotEmpty())
                                        <div class="slot-container">
                                            @php
                                                $groupedClassSlots = $slots->groupBy('class_id');
                                            @endphp
                                            @foreach($groupedClassSlots as $cId => $cSlots)
                                                @php
                                                    $firstSlot = $cSlots->first();
                                                    if ($cSlots->count() > 1) {
                                                        $subText = $cSlots->map(function($cs) {
                                                            return \App\Models\Subject::formatAbbreviation($cs->subject_name, $cs->subject_code);
                                                        })->filter()->unique()->values()->implode(' + ');
                                                    } else {
                                                        $subText = $firstSlot->subject_name ?? $firstSlot->subject_code ?? '';
                                                    }
                                                @endphp
                                                <div class="slot-item">
                                                    <div class="slot-class">{{ $firstSlot->class_name ?? '-' }}</div>
                                                    <div class="slot-subject">{{ $subText }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endif
                        @endforeach

                        {{-- Sum of Lessons --}}
                        <td class="td-sum-val">
                            {{ $sumOfLessons[$t->id] ?? 0 }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="page-footer-table">
                <tr>
                    <td class="footer-left">Applicable from {{ $effectiveDate }} &bull; Day: <strong>{{ $currentDay }}</strong></td>
                    <td class="footer-right">Adminova Timetables</td>
                </tr>
            </table>
        </div>
        @endforeach
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

        window.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('autoprint') === '1') {
                triggerPrintAndDownload();
            }
        });
    </script>
    @endif
</body>
</html>
