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
    <div class="card-header">
        <div class="logo-box">
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="Logo" class="teacher-card-logo">
            @elseif(!empty($instituteLogo) && file_exists(public_path($instituteLogo)))
                <img src="{{ '/' . $instituteLogo }}" alt="Logo" class="teacher-card-logo">
            @else
                <div class="teacher-card-logo-fallback">🏛️</div>
            @endif
        </div>
        <div class="header-text">
            <div class="inst-title">{{ $instituteName }}</div>
            <div class="teacher-title">Teacher {{ $teacher?->name ?? 'Staff Member' }}</div>
        </div>
    </div>

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
                @if($row['is_assembly'])
                    <tr class="span-row">
                        <td class="time-td">
                            <em>ASSEMBLY</em>
                            <span class="time-sub">{{ $row['time_range'] }}</span>
                        </td>
                        <td class="span-td">
                            <strong><em>ASSEMBLY</em></strong>
                        </td>
                    </tr>
                @elseif($row['is_break'])
                    <tr class="span-row">
                        <td class="time-td">
                            <em>BREAK</em>
                            <span class="time-sub">{{ $row['time_range'] }}</span>
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
                                <div class="lesson-split">
                                    <span class="subj-name">{{ $row['subject'] ?? 'Teaching' }}</span>
                                    <span class="class-name">{{ $row['class_name'] ?? '' }}</span>
                                </div>
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
    <div class="card-footer">
        <span class="footer-left">Applicable from {{ $effectiveDate }}</span>
        <span class="footer-right">Adminova Timetables</span>
    </div>
</div>

<style>
.teacher-card {
    border: 1.5px solid #000;
    background: #fff;
    padding: 3px 5px 2px 5px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    page-break-inside: avoid;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #000;
}
.card-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding-bottom: 2px;
    border-bottom: 1.5px solid #000;
}
.logo-box {
    width: 26px;
    height: 26px;
    max-width: 28px;
    max-height: 28px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.teacher-card-logo {
    width: 26px;
    height: 26px;
    max-width: 28px;
    max-height: 28px;
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}
.teacher-card-logo-fallback {
    font-size: 16px;
    line-height: 1;
}
.header-text {
    flex: 1;
    min-width: 0;
    line-height: 1.15;
}
.inst-title {
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.teacher-title {
    font-size: 9.5pt;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.card-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2px;
    flex: 1;
}
.card-table th, .card-table td {
    border: 1px solid #000;
    padding: 1.5px 3px;
    box-sizing: border-box;
}
.time-th {
    width: 32%;
    border: none !important;
}
.lessons-th {
    width: 68%;
    font-size: 8.5pt;
    font-weight: 600;
    text-align: center;
    border-bottom: 1px solid #000 !important;
    border-top: none !important;
    border-right: none !important;
}
.time-td {
    width: 32%;
    font-size: 7.5pt;
    line-height: 1.15;
    vertical-align: middle;
}
.time-td em {
    font-style: italic;
    font-weight: 700;
    display: block;
}
.time-sub {
    font-size: 6.5pt;
    font-family: monospace, sans-serif;
    color: #111;
    display: block;
}
.span-td {
    text-align: center;
    font-size: 9pt;
    letter-spacing: 1px;
    vertical-align: middle;
}
.lesson-td {
    width: 68%;
    vertical-align: middle;
    padding: 1px 4px !important;
}
.lesson-split {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
.subj-name {
    font-size: 8pt;
    font-weight: 500;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 65%;
}
.class-name {
    font-size: 8.5pt;
    font-weight: 800;
    text-align: right;
    white-space: nowrap;
}
.sum-row {
    font-weight: 700;
}
.sum-label {
    font-size: 7.5pt !important;
    font-weight: 700;
}
.sum-count {
    text-align: center;
    font-size: 9.5pt;
    font-weight: 900;
}
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 6.5pt;
    font-weight: 600;
    color: #333;
    padding-top: 2px;
    border-top: 1px solid #000;
    margin-top: 2px;
}
</style>
