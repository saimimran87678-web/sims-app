<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary timetable of classes | {{ $instituteName ?? 'School Timetable' }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 6mm 7mm;
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
            font-family: 'Arial Narrow', 'Liberation Sans Narrow', 'Roboto Condensed', 'Nimbus Sans L', Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.15;
            -webkit-text-size-adjust: 100%;
        }

        .sheet-page {
            width: 100%;
            max-width: 283mm;
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

        /* Institutional Title Block with Logo (First Page Only) */
        .school-header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 2.5mm;
            table-layout: fixed;
        }

        .school-header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .header-logo-cell {
            width: 70px;
            text-align: left;
        }

        .header-spacer-cell {
            width: 70px;
            text-align: right;
        }

        .school-logo {
            max-height: 40px;
            max-width: 65px;
            object-fit: contain;
            display: block;
        }

        .header-center-cell {
            text-align: center;
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
            font-size: 10pt;
            font-weight: 400;
            margin: 1.5px 0 0 0;
            line-height: 1.15;
            color: #111;
        }

        /* Continuation Header Block (Subsequent Pages) */
        .continuation-header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            border-bottom: 1.5px solid #000000;
            margin-bottom: 2.5mm;
            padding-bottom: 1mm;
        }

        .continuation-header-table td {
            border: none;
            padding: 1px 0 2px 0;
            vertical-align: middle;
        }

        .continuation-left {
            text-align: left;
        }

        .continuation-right {
            text-align: right;
            font-size: 7.5pt;
            font-weight: 600;
            color: #111;
        }

        .continuation-title {
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000;
        }

        .continuation-badge {
            font-size: 7.5pt;
            font-weight: 400;
            font-style: italic;
            color: #444;
            margin-left: 6px;
        }

        /* Table Structure with Zero Border Glitches */
        table.timetable-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            border: 1.25px solid #000000;
            background-color: #ffffff;
        }

        table.timetable-table th,
        table.timetable-table td {
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            border-top: none;
            border-left: none;
            padding: 2px 1.5px;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5pt;
            line-height: 1.12;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        table.timetable-table tr > th:last-child,
        table.timetable-table tr > td:last-child {
            border-right: none;
        }

        table.timetable-table tbody tr {
            height: 14mm;
        }

        table.timetable-table tbody td {
            height: 14mm;
        }

        .th-blank-class {
            background-color: #ffffff;
            border-bottom: 1px solid #000000;
        }

        .th-assembly-head {
            font-size: 7.2pt;
            font-weight: 700;
            background-color: #ffffff;
            padding: 3px 2px;
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
            padding: 3px 1px;
            line-height: 1.18;
            letter-spacing: 0.3px;
            white-space: nowrap;
            min-width: 44px;
        }

        .th-sum-head {
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
            margin-top: 1.5px;
            letter-spacing: -0.1px;
            white-space: nowrap;
            color: #111;
        }

        .td-class-name {
            font-size: 9pt;
            font-weight: 700;
            text-align: center;
            padding: 2px 2px;
            background-color: #ffffff;
            letter-spacing: 0.2px;
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
            line-height: 1.35;
            font-weight: 700;
        }

        .slot-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 26px;
        }

        .slot-item {
            padding: 0.5px 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .slot-item + .slot-item {
            border-top: 0.5px solid #000000;
            margin-top: 1px;
            padding-top: 1px;
        }

        .slot-teacher {
            font-size: 6.8pt;
            font-weight: 400;
            color: #111111;
            line-height: 1.05;
            white-space: normal;
        }

        .slot-subject {
            font-size: 7.2pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.1;
            white-space: normal;
            margin-top: 0.5px;
        }

        .slot-empty {
            min-height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-size: 1pt;
            visibility: hidden;
        }

        .td-sum-val {
            font-size: 8.5pt;
            font-weight: 600;
            text-align: center;
            color: #000000;
        }

        .page-footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 2.5mm;
            font-size: 6.8pt;
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
        $nonBreakCount = collect($nonAssemblyPeriods)->where('is_break', false)->count();
        $breakCount = collect($nonAssemblyPeriods)->where('is_break', true)->count();
        $hasAssembly = !empty($assemblyPeriod);

        $fixedWidthPercentage = 5.5 + ($hasAssembly ? 4.8 : 0) + ($breakCount * 3.8) + 4.4;
        $periodWidthPercentage = $nonBreakCount > 0 ? ((100 - $fixedWidthPercentage) / $nonBreakCount) : 11.5;
        $hasLogo = !empty($logoBase64) || (!empty($instituteLogo) && file_exists(public_path($instituteLogo)));
    @endphp

    @foreach($classPages as $pageIndex => $pageClasses)
    <div class="sheet-page">
        @if($pageIndex === 0)
        <table class="school-header-table">
            <tr>
                @if($hasLogo)
                <td class="header-logo-cell">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" class="school-logo">
                    @else
                        <img src="{{ asset($instituteLogo) }}" alt="Logo" class="school-logo">
                    @endif
                </td>
                @endif
                <td class="header-center-cell">
                    <div class="school-main-title">{{ $instituteName ?? \App\Models\Setting::getFormalInstituteName() }}</div>
                    <div class="school-sub-title">Summary timetable of classes</div>
                </td>
                @if($hasLogo)
                <td class="header-spacer-cell"></td>
                @endif
            </tr>
        </table>
        @else
        <table class="continuation-header-table">
            <tr>
                <td class="continuation-left">
                    <span class="continuation-title">Summary timetable of classes</span>
                    <span class="continuation-badge">(continued)</span>
                </td>
                <td class="continuation-right">
                    Page {{ $pageIndex + 1 }} of {{ count($classPages) }}
                </td>
            </tr>
        </table>
        @endif

        <table class="timetable-table">
            <colgroup>
                <col style="width: 5.5%;">

                @if($hasAssembly)
                    <col style="width: 4.8%; min-width: 50px;">
                @endif

                @foreach($nonAssemblyPeriods as $p)
                    @if($p->is_break)
                        <col style="width: 3.8%; min-width: 44px;">
                    @else
                        <col style="width: {{ number_format($periodWidthPercentage, 2) }}%;">
                    @endif
                @endforeach

                <col style="width: 4.4%;">
            </colgroup>

            <thead>
                <tr>
                    <th rowspan="2" class="th-blank-class"></th>

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
                @foreach($pageClasses as $cIndex => $cls)
                <tr>
                    <td class="td-class-name">
                        {{ $cls->name }}
                    </td>

                    @if($hasAssembly)
                        @if($loop->first)
                            <td rowspan="{{ count($pageClasses) }}" class="td-vertical-text">
                                <span class="vertical-char-stack">
                                    A<br>S<br>S<br>E<br>M<br>B<br>L<br>Y
                                </span>
                            </td>
                        @endif
                    @endif

                    @foreach($nonAssemblyPeriods as $p)
                        @if($p->is_break)
                            @if($loop->parent->first)
                                <td rowspan="{{ count($pageClasses) }}" class="td-vertical-text">
                                    <span class="vertical-char-stack">
                                        B<br>R<br>E<br>A<br>K
                                    </span>
                                </td>
                            @endif
                        @else
                            @php
                                $slots = $timetableGrid->get($cls->id . '_' . $p->period_no, collect());
                            @endphp
                            <td>
                                @if($slots->isNotEmpty())
                                    <div class="slot-container">
                                        @php
                                            $groupedTeacherSlots = $slots->groupBy('teacher_id');
                                        @endphp
                                        @foreach($groupedTeacherSlots as $tId => $tSlots)
                                            @php
                                                $firstSlot = $tSlots->first();
                                                if ($tSlots->count() > 1) {
                                                    $subText = $tSlots->map(function($ts) {
                                                        return \App\Models\Subject::formatAbbreviation($ts->subject_name, $ts->subject_code);
                                                    })->filter()->unique()->values()->implode(' + ');
                                                } else {
                                                    $subText = $firstSlot->subject_name ?? $firstSlot->subject_code ?? '';
                                                }
                                            @endphp
                                            <div class="slot-item">
                                                <div class="slot-teacher">{{ $firstSlot->teacher_name ?? '-' }}</div>
                                                <div class="slot-subject">{{ $subText }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="slot-container">
                                        <div class="slot-empty">&nbsp;</div>
                                    </div>
                                @endif
                            </td>
                        @endif
                    @endforeach

                    <td class="td-sum-val">
                        {{ $sumOfLessons[$cls->id] ?? 0 }}
                    </td>
                </tr>
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