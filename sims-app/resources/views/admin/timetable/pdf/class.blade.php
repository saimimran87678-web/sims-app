<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Class Timetable - {{ $class->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
        .period-time { font-size: 10px; color: #555; display: block; }
        .subject { font-weight: bold; font-size: 12px; }
        .teacher-name { font-size: 11px; font-style: italic; }
        .break { background-color: #ffe0b2; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin-bottom: 5px;">Class {{ $class->name }}</h1>
        <p style="font-size: 10px; margin: 0;">{{ $template->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 100px;"></th> <!-- Period Time Col -->
                <th>
                    <div style="font-weight: bold; font-size: 16px; font-style: italic;">Lessons</div>
                </th>
                <th style="width: 160px;">Teacher</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalLessons = 0;
            @endphp
            @foreach($periods as $period)
                <tr>
                    <!-- Period Info -->
                    <td style="height: 40px; vertical-align: middle;">
                        <div style="font-weight: bold; font-size: 12px;">{{ $period->label ?? $period->period_no }}</div>
                        @if(!$period->is_break)
                            <span class="period-time" style="font-style: italic;">{{ $period->start_time?->format('g:i') }} - {{ $period->end_time?->format('g:i') }}</span>
                        @endif
                    </td>

                    @if($period->is_break || $period->is_assembly)
                        <td colspan="2" style="text-align: center; font-weight: bold; font-style: italic; font-size: 16px; vertical-align: middle;">
                            {{ strtoupper($period->label) }}
                        </td>
                    @else
                        @php
                            // Use Monday as the representative day since schedule is propagated all days
                            $day = 'Monday';
                            $entries = $timetable->where('day', $day)->where('period_no', $period->period_no)->all();
                            
                            if (count($entries) > 0) {
                                $totalLessons++;
                            }
                        @endphp

                        <td style="height: 40px; vertical-align: middle; text-align: left; padding: 0 10px;">
                            @if($period->period_no == 0)
                                <span style="font-size: 10px; color: #888; font-style: italic; display:block; margin-bottom: 2px;">Assembly</span>
                            @endif
                            @if(count($entries) > 0)
                                @foreach($entries as $entry)
                                    <span class="subject" style="font-size: 16px; display:block;">{{ $entry->subject->name ?? '' }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td style="height: 40px; vertical-align: middle; text-align: center;">
                            @if(count($entries) > 0)
                                @foreach($entries as $entry)
                                    <span class="teacher-name" style="font-size: 14px; font-weight: bold; display:block;">
                                        {{ $entry->teacher->name ?? '' }}
                                    </span>
                                @endforeach
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
            
            <!-- Sum Row -->
            <tr>
                <td colspan="2" style="font-weight: bold; background-color: #f0f0f0; text-align: right; padding-right: 15px;">Sum of lessons</td>
                <td style="font-weight: bold; font-size: 20px; text-align: center;">{{ $totalLessons }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
