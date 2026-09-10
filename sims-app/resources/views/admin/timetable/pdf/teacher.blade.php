<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Teacher Timetable - {{ $teacher->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
        .period-time { font-size: 10px; color: #555; display: block; }
        .subject { font-weight: bold; font-size: 12px; }
        .class-name { font-size: 11px; font-style: italic; }
        .break { background-color: #ffe0b2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teacher {{ $teacher->name }}</h1>
        <p style="font-size: 10px; margin: 0;">{{ $template->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 100px;"></th> <!-- Period Time Col -->
                <th>
                    <div style="font-weight: bold; font-size: 16px; font-style: italic;">Lessons</div>
                </th>
                <th style="width: 120px;">Class</th>
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
                            // Use Monday as the representative day
                            $day = 'Monday';
                            $entries = $timetable->where('day', $day)->where('period_no', $period->period_no)->all();
                            $overrideContent = null;

                            // Manual Overrides Logic
                            // General Management
                            $managementTeachers = ['Muhammad Amin', 'Rana Zahid'];
                            if (in_array($teacher->name, $managementTeachers) && $period->period_no == 1) {
                                $overrideContent = ['class' => 'School', 'subject' => 'Management', 'is_bold' => false];
                            }
                            // Mr. Owais - Management + Arrangement
                            if ($teacher->name === 'Muhammad Owais Ur Rehman' && $period->period_no == 1) {
                                $overrideContent = ['class' => '', 'subject' => 'Management + Arrangement', 'is_bold' => false];
                            }
                            // Mr. Owais - P3 12A Comp Sci (Days 4-5 = Thu, Fri only)
                            if ($teacher->name === 'Muhammad Owais Ur Rehman' && $period->period_no == 3 && in_array($day, ['Thursday', 'Friday'])) {
                                $overrideContent = ['class' => '12A', 'subject' => 'Comp Sci', 'is_bold' => true];
                            }
                            // Mr. Ranjhan - P3 12A Pakstudy (Days 1-3 = Mon, Tue, Wed only)
                            if ($teacher->name === 'Ranjhan Khan' && $period->period_no == 3 && in_array($day, ['Monday', 'Tuesday', 'Wednesday'])) {
                                $overrideContent = ['class' => '12A', 'subject' => 'Pakstudy', 'is_bold' => true];
                            }

                            if ($overrideContent) {
                                $totalLessons++;
                            } elseif (count($entries) > 0) {
                                $totalLessons++;
                            }
                        @endphp

                        <td style="height: 40px; vertical-align: middle; text-align: left; padding: 0 10px;">
                            @if($period->period_no == 0)
                                <span style="font-size: 10px; color: #888; font-style: italic; display:block; margin-bottom: 2px;">Assembly</span>
                            @endif
                            @if($overrideContent)
                                <span class="subject" style="font-size: 16px;">{{ $overrideContent['subject'] }}</span>
                            @elseif(count($entries) > 0)
                                @foreach($entries as $entry)
                                    <span class="subject" style="font-size: 16px; display:block;">{{ $entry->subject->name ?? '' }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td style="height: 40px; vertical-align: middle; text-align: center;">
                            @if($overrideContent)
                                @if($overrideContent['class'])
                                    <span class="class-name" style="font-size: 14px; font-weight: bold;">{{ $overrideContent['class'] }}</span>
                                @endif
                            @elseif(count($entries) > 0)
                                @foreach($entries as $entry)
                                    <span class="class-name" style="font-size: 14px; font-weight: bold; display:block;">
                                        {{ $entry->class->name ?? '' }}
                                        @if($entry->room && $entry->room !== $entry->class->name)
                                            ({{ $entry->room }})
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
            
            <!-- Sum Row -->
            <tr>
                 @php
                    // Hardcoded Count Reset
                    if ($teacher->name === 'Rana Zahid') {
                        $totalLessons = 5;
                    } elseif ($teacher->name === 'Muhammad Amin') {
                        $totalLessons = 2;
                    }
                @endphp
                <td colspan="2" style="font-weight: bold; background-color: #f0f0f0; text-align: right; padding-right: 15px;">Sum of lessons</td>
                <td style="font-weight: bold; font-size: 20px; text-align: center;">{{ $totalLessons }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
