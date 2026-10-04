<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Timetable - Class-Wise | Adminova Timetables</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
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
            font-size: 8pt;
        }

        /* Screen Toolbar */
        .screen-toolbar {
            position: fixed;
            top: 10px;
            right: 15px;
            display: flex;
            gap: 8px;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid #e5e7eb;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
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
            margin: 0 auto;
            padding: 4px;
        }

        /* Header matching Class wise timetable.pdf */
        .sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 20px;
            border-radius: 4px;
            background: #f9fafb;
        }

        .header-title-box {
            line-height: 1.2;
        }

        .inst-name {
            font-size: 13pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            margin: 0;
        }

        .sheet-subtitle {
            font-size: 9.5pt;
            font-weight: 600;
            color: #374151;
            margin: 2px 0 0 0;
        }

        .header-right {
            text-align: right;
            line-height: 1.25;
        }

        .system-brand {
            font-size: 10pt;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.3px;
        }

        .effective-tag {
            font-size: 8pt;
            color: #4b5563;
            margin-top: 2px;
        }

        /* Master Matrix Table */
        .master-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            table-layout: fixed;
        }

        .master-table th,
        .master-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5pt;
            overflow: hidden;
            word-wrap: break-word;
        }

        .master-table thead th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #000;
            height: 28px;
        }

        .th-class {
            width: 110px;
            text-align: left;
            padding-left: 6px;
            font-size: 8pt;
        }

        .th-period {
            font-size: 8pt;
        }

        .th-period-num {
            font-weight: 800;
            font-size: 8.5pt;
        }

        .th-period-time {
            font-size: 6.5pt;
            font-weight: normal;
            color: #374151;
            display: block;
            margin-top: 1px;
        }

        .th-sum {
            width: 60px;
            font-size: 7.5pt;
            font-weight: 700;
        }

        /* Vertical Assembly / Break Column Styling */
        .vertical-col-th {
            width: 24px;
            background-color: #e5e7eb !important;
            font-size: 7pt;
            padding: 2px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .vertical-band-cell {
            background-color: #f3f4f6;
            width: 24px;
            padding: 0 !important;
            text-align: center;
            vertical-align: middle;
        }

        .vertical-band-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            letter-spacing: 4px;
            font-weight: 800;
            font-size: 8.5pt;
            color: #4b5563;
            text-transform: uppercase;
            display: inline-block;
            margin: auto;
        }

        /* Class row header */
        .class-label-cell {
            text-align: left !important;
            padding-left: 6px !important;
            font-weight: 700;
            font-size: 8pt;
            background-color: #fafafa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .class-teacher-sub {
            display: block;
            font-size: 6.5pt;
            font-weight: normal;
            color: #4b5563;
        }

        /* Lesson slot cell */
        .slot-cell {
            height: 32px;
            line-height: 1.15;
            padding: 1px 2px;
        }

        .slot-single {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        .cell-teacher {
            font-weight: 700;
            font-size: 7pt;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .cell-subject {
            font-size: 6.8pt;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        /* Divided / Multi-subject slots */
        .slot-divided {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-around;
        }

        .divided-sub-slot {
            display: flex;
            flex-direction: column;
            border-bottom: 0.5px dashed #9ca3af;
            padding: 0.5px 0;
        }
        .divided-sub-slot:last-child {
            border-bottom: none;
        }

        .cell-sum {
            font-weight: 800;
            font-size: 8.5pt;
            background-color: #fafafa;
        }

        /* Footer */
        .sheet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            padding-top: 2px;
            border-top: 1px solid #d1d5db;
            font-size: 6.8pt;
            color: #6b7280;
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
                    <div class="sheet-subtitle">Summary timetable of classes</div>
                </div>
            </div>

            <div class="header-right">
                <div class="system-brand">Adminova Timetables</div>
                <div class="effective-tag">Applicable from: <strong>{{ $effectiveDate }}</strong></div>
            </div>
        </div>

        {{-- Master Timetable Table --}}
        <table class="master-table">
            <thead>
                <tr>
                    <th class="th-class">Class</th>

                    @foreach($periods as $p)
                        @if($p->is_assembly)
                            <th class="vertical-col-th">
                                <div>ASSEMBLY</div>
                                <span class="th-period-time">{{ $p->start_time ? \Carbon\Carbon::parse($p->start_time)->format('g:i') : '' }}</span>
                            </th>
                        @elseif($p->is_break)
                            <th class="vertical-col-th">
                                <div>BREAK</div>
                                <span class="th-period-time">{{ $p->start_time ? \Carbon\Carbon::parse($p->start_time)->format('g:i') : '' }}</span>
                            </th>
                        @else
                            <th class="th-period">
                                <span class="th-period-num">{{ $p->period_no }}</span>
                                <span class="th-period-time">
                                    {{ $p->start_time ? \Carbon\Carbon::parse($p->start_time)->format('g:i') : '' }} - {{ $p->end_time ? \Carbon\Carbon::parse($p->end_time)->format('g:i') : '' }}
                                </span>
                            </th>
                        @endif
                    @endforeach

                    <th class="th-sum">Sum of lessons</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classes as $cIndex => $cls)
                    <tr>
                        <td class="class-label-cell" title="{{ $cls->name }}">
                            {{ $cls->name }}
                            @if(!empty($cls->class_teacher_name))
                                <span class="class-teacher-sub">{{ $cls->class_teacher_name }}</span>
                            @endif
                        </td>

                        @foreach($periods as $p)
                            @if($p->is_assembly)
                                @if($cIndex === 0)
                                    <td rowspan="{{ count($classes) }}" class="vertical-band-cell">
                                        <div class="vertical-band-text">ASSEMBLY</div>
                                    </td>
                                @endif
                            @elseif($p->is_break)
                                @if($cIndex === 0)
                                    <td rowspan="{{ count($classes) }}" class="vertical-band-cell">
                                        <div class="vertical-band-text">BREAK</div>
                                    </td>
                                @endif
                            @else
                                @php
                                    $slots = $timetableGrid->get($cls->id . '_' . $p->period_no, collect());
                                @endphp
                                <td class="slot-cell">
                                    @if($slots->count() === 1)
                                        @php $slot = $slots->first(); @endphp
                                        <div class="slot-single">
                                            <span class="cell-teacher">{{ $slot->teacher_name ?? '-' }}</span>
                                            <span class="cell-subject">{{ $slot->subject_name ?? $slot->subject_code ?? '' }}</span>
                                        </div>
                                    @elseif($slots->count() > 1)
                                        <div class="slot-divided">
                                            @foreach($slots as $s)
                                                <div class="divided-sub-slot">
                                                    <span class="cell-teacher">{{ $s->teacher_name ?? '-' }}</span>
                                                    <span class="cell-subject">{{ $s->subject_name ?? $s->subject_code ?? '' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endif
                        @endforeach

                        <td class="cell-sum">{{ $sumOfLessons[$cls->id] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer --}}
        <div class="sheet-footer">
            <span>Generated on {{ now()->format('d/m/Y h:i A') }} • Single Universal Schedule Routine</span>
            <span>Adminova Timetables • Page 1 of 1</span>
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
