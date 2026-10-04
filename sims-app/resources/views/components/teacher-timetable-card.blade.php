{{-- Reusable Teacher Timetable Card matching each teacher.pdf --}}
@props([
    'teacherData',
    'instituteName' => 'IMCB G-6/2, ISLAMABAD',
    'instituteLogo' => '',
    'logoBase64'    => null,
    'effectiveDate' => '17th Nov 2025',
])

@php
    $teacher = $teacherData['teacher'] ?? null;
    $rows = $teacherData['rows'] ?? [];
    $totalLessons = $teacherData['totalLessons'] ?? 0;
@endphp

<div class="teacher-card">
    {{-- Card Header --}}
    <table class="card-header-table">
        <tr>
            @if(!empty($logoBase64))
                <td class="logo-td">
                    <img src="{{ $logoBase64 }}" alt="Logo" class="teacher-card-logo">
                </td>
            @elseif(!empty($instituteLogo) && file_exists(public_path($instituteLogo)))
                <td class="logo-td">
                    <img src="{{ '/' . $instituteLogo }}" alt="Logo" class="teacher-card-logo">
                </td>
            @endif
            <td class="header-text-td">
                <div class="inst-title">{{ $instituteName }}</div>
                <div class="teacher-title">Teacher {{ $teacher?->name ?? 'Staff Member' }}</div>
            </td>
        </tr>
    </table>

    {{-- Period Table --}}
    <table class="card-table">
        <thead>
            <tr>
                <th class="time-th"></th>
                <th class="lessons-th"><em>Lessons</em></th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @if(!empty($row['is_assembly']))
                    <tr class="span-row">
                        <td class="time-td">
                            <em>ASSEMBLY</em>
                            <span class="time-sub">{{ $row['time_range'] ?? '' }}</span>
                        </td>
                        <td class="span-td">
                            <strong><em>ASSEMBLY</em></strong>
                        </td>
                    </tr>
                @elseif(!empty($row['is_break']))
                    <tr class="span-row">
                        <td class="time-td">
                            <em>BREAK</em>
                            <span class="time-sub">{{ $row['time_range'] ?? '' }}</span>
                        </td>
                        <td class="span-td">
                            <strong><em>BREAK</em></strong>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="time-td">
                            <em>{{ $row['period_label'] }}</em>
                            <span class="time-sub">{{ $row['time_range'] }}</span>
                        </td>
                        <td class="lesson-td">
                            @if(!empty($row['subject']) || !empty($row['class_name']))
                                <table class="lesson-split-table">
                                    <tr>
                                        <td class="subj-name-cell">{{ $row['subject'] ?? 'Teaching' }}</td>
                                        <td class="class-name-cell">{{ $row['class_name'] ?? '' }}</td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
            <tr class="sum-row">
                <td class="time-td sum-label">Sum of lessons</td>
                <td class="sum-count">{{ $totalLessons }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Card Footer --}}
    <table class="card-footer-table">
        <tr>
            <td class="footer-left-cell">Applicable from {{ $effectiveDate }}</td>
            <td class="footer-right-cell">Adminova Timetables</td>
        </tr>
    </table>
</div>

<style>
.teacher-card {
    border: 1.5px solid #000;
    background: #fff;
    padding: 3px 4px 2px 4px;
    box-sizing: border-box;
    page-break-inside: avoid;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
    width: 100%;
}
table.card-header-table {
    width: 100%;
    border-collapse: collapse;
    border: none;
    border-bottom: 1.5px solid #000;
    padding-bottom: 2px;
}
table.card-header-table td {
    border: none;
    padding: 1px 2px;
    vertical-align: middle;
}
.logo-td {
    width: 28px;
    text-align: left;
}
.teacher-card-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
}
.header-text-td {
    text-align: left;
}
.inst-title {
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 1.1;
    color: #000;
}
.teacher-title {
    font-size: 9pt;
    font-weight: bold;
    line-height: 1.1;
    color: #000;
    margin-top: 1px;
}

table.card-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2px;
}
table.card-table th, table.card-table td {
    border: 1px solid #000;
    padding: 1.5px 3px;
    box-sizing: border-box;
}
.time-th {
    width: 34%;
    border: none !important;
}
.lessons-th {
    width: 66%;
    font-size: 8pt;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #000 !important;
    border-top: none !important;
    border-right: none !important;
}
.time-td {
    width: 34%;
    font-size: 7.5pt;
    line-height: 1.1;
    vertical-align: middle;
}
.time-td em {
    font-style: italic;
    font-weight: bold;
    display: block;
}
.time-sub {
    font-size: 6.5pt;
    color: #111;
    display: block;
}
.span-td {
    text-align: center;
    font-size: 8pt;
    letter-spacing: 1px;
    vertical-align: middle;
}
.lesson-td {
    width: 66%;
    vertical-align: middle;
    padding: 0 !important;
}
table.lesson-split-table {
    width: 100%;
    border-collapse: collapse;
    border: none !important;
}
table.lesson-split-table td {
    border: none !important;
    padding: 1px 3px !important;
    vertical-align: middle;
}
.subj-name-cell {
    font-size: 7.5pt;
    font-weight: normal;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.class-name-cell {
    font-size: 8pt;
    font-weight: bold;
    text-align: right;
    white-space: nowrap;
}
.sum-row {
    font-weight: bold;
}
.sum-label {
    font-size: 7.5pt !important;
    font-weight: bold;
}
.sum-count {
    text-align: center;
    font-size: 9pt;
    font-weight: bold;
}
table.card-footer-table {
    width: 100%;
    border-collapse: collapse;
    border: none;
    border-top: 1px solid #000;
    margin-top: 2px;
}
table.card-footer-table td {
    border: none;
    padding: 1px 0;
    font-size: 6.5pt;
    font-weight: normal;
    color: #222;
}
.footer-left-cell {
    text-align: left;
}
.footer-right-cell {
    text-align: right;
}
</style>
