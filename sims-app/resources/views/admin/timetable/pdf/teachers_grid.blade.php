<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>All Teachers Timetable</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .page { width: 100%; height: 100%; page-break-after: always; padding: 10px; box-sizing: border-box; }
        .page:last-child { page-break-after: auto; }
        
        .grid-container {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px; /* Gap between grid items */
        }
        
        .grid-item {
            width: 33.33%; /* 3 Columns */
            vertical-align: top;
            /* border: 1px solid #ddd; */
        }

        /* Inner Table Styling (Mini Version of Teacher PDF) */
        .teacher-table { width: 100%; border-collapse: collapse; margin-bottom: 0px; }
        .teacher-table th, .teacher-table td { border: 1px solid #000; padding: 2px; text-align: center; }
        .teacher-table th { background-color: #f0f0f0; }
        
        .header { text-align: center; margin-bottom: 5px; }
        .header h2 { margin: 0; font-size: 14px; }
        .header p { margin: 0; font-size: 9px; }

        .subject { font-weight: bold; font-size: 11px; }
        .class-name { font-size: 10px; font-style: italic; margin-left: 8px; }
        .period-time { font-size: 9px; color: #555; display: block; font-style: italic; }
        .break { background-color: #ffe0b2; }
    </style>
</head>
<body>
    @foreach($teachers->chunk(6) as $chunk)
        <div class="page">
            <table class="grid-container">
                @foreach($chunk->chunk(3) as $row)
                    <tr>
                        @foreach($row as $teacher)
                            <td class="grid-item">
                                <!-- Individual Teacher Schedule -->
                                <div class="header">
                                    <h2>{{ $teacher->name }}</h2>
                                </div>

                                <table class="teacher-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 30px;">Pd</th>
                                            <th>Lessons</th>
                                            <th style="width: 60px;">Class</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalLessons = 0;
                                        @endphp
                                        @foreach($periods as $period)
                                            <tr>
                                                <td style="font-weight: bold;">
                                                    {{ $period->label ?? $period->period_no }}
                                                    @if(!$period->is_break)
                                                        <br><span class="period-time">{{ $period->start_time?->format('H:i') }}-{{ $period->end_time?->format('H:i') }}</span>
                                                    @endif
                                                </td>
                                                
                                                @if($period->is_break || $period->is_assembly)
                                                    <td colspan="2" style="text-align: center; font-weight: bold; font-style: italic; vertical-align: middle; height: 25px;">
                                                        {{ strtoupper($period->label) }}
                                                    </td>
                                                @else
                                                    @php
                                                        $day = 'Monday'; // Base Day
                                                        $entries = $timetable->where('teacher_id', $teacher->id)
                                                            ->where('day', $day)
                                                            ->where('period_no', $period->period_no)
                                                            ->all();
                                                        
                                                        $overrideContent = null;

                                                        // Check for Teacher Duty from Database (e.g. Management)
                                                        $duty = ($teacherDuties ?? collect())->first(function ($d) use ($teacher, $day, $period) {
                                                            return $d->teacher_id == $teacher->id 
                                                                && $d->day === $day 
                                                                && $d->period_no == $period->period_no;
                                                        });
                                                        if ($duty) {
                                                            $overrideContent = ['class' => 'School', 'subject' => $duty->duty_name];
                                                        }
                                                        if ($teacher->name === 'Muhammad Owais Ur Rehman' && $period->period_no == 3 && in_array($day, ['Thursday', 'Friday'])) {
                                                            $overrideContent = ['class' => '12A', 'subject' => 'Comp Sci'];
                                                        }
                                                        if ($teacher->name === 'Ranjhan Khan' && $period->period_no == 3 && in_array($day, ['Monday', 'Tuesday', 'Wednesday'])) {
                                                            $overrideContent = ['class' => '12A', 'subject' => 'Pakstudy'];
                                                        }

                                                        if ($overrideContent) $totalLessons++;
                                                        elseif (count($entries) > 0) $totalLessons++;
                                                    @endphp
                                                    
                                                    <td style="text-align: left; height: 25px; vertical-align: middle;">
                                                        @if($period->period_no == 0)
                                                            <span style="font-size: 8px; color: #888; font-style: italic; display:block; margin-bottom: 1px;">Assembly</span>
                                                        @endif
                                                        @if($overrideContent)
                                                            <span class="subject">{{ $overrideContent['subject'] }}</span>
                                                        @elseif(count($entries) > 0)
                                                            @foreach($entries as $entry)
                                                                <span class="subject" style="display:block;">{{ $entry->subject->name ?? '' }}</span>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                    <td style="text-align: center; vertical-align: middle; font-size: 10px; font-style: italic;">
                                                        @if($overrideContent)
                                                            {{ $overrideContent['class'] }}
                                                        @elseif(count($entries) > 0)
                                                            @foreach($entries as $entry)
                                                                <span style="display:block;">
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
                                        <tr>
                                            <td colspan="2" style="font-weight: bold; background: #eee; text-align: right; padding-right: 5px;">Sum</td>
                                            <td style="font-weight: bold; text-align: center;">{{ $totalLessons }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        @endforeach
                        
                        <!-- Fill empty cells if row < 3 -->
                        @for($i = $row->count(); $i < 3; $i++)
                            <td class="grid-item"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
