<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Master Timetable - {{ ucfirst($mode) }} View</title>
    <style>
        body { font-family: sans-serif; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
        .period-time { font-size: 11px; color: #555; display: block; }
        .day-header { background-color: #e0e0e0; font-weight: bold; text-align: left; padding: 5px; }
        .subject { font-size: 13px; margin-top: 2px; }
        .detail { font-size: 14px; font-weight: bold; color: #000; }
        .break { background-color: #ffe0b2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Master Timetable ({{ ucfirst($mode) }} View) Session 2026-27</h1>
    </div>

    @foreach($days as $day)
        <div @if(!$loop->last) style="page-break-after: always;" @endif>
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">{{ $mode === 'class' ? 'Class' : 'Teacher' }}</th>
                        @foreach($periods as $period)
                            <th class="{{ $period->is_break ? 'break' : '' }}">
                                {{ $period->label ?? $period->period_no }}
                                <span class="period-time">{{ $period->start_time?->format('h:i') }} - {{ $period->end_time?->format('h:i') }}</span>
                            </th>
                        @endforeach
                        <th style="width: 70px;">Sum of lesson</th>
                    </tr>
                </thead>
                <tbody>
                    @php $skipCells = []; @endphp
                    @foreach($items as $itemIndex => $item)
                        <tr>
                            <td style="font-weight: bold; text-align: left;">{{ str_replace('Class ', '', $item->name) }}</td>
                            @php
                                $lessonCount = 0;
                            @endphp
                            @foreach($periods as $period)
                                @if(isset($skipCells[$item->id][$period->period_no]))
                                    @continue
                                @endif
                                @php 
                                    $entries = [];
                                    $overrideContent = null;
                                        
                                    // 1. Check for Regular Entry
                                    if ($mode === 'class') {
                                        $entries = $timetable->filter(function ($t) use ($item, $day, $period) {
                                            return ($t->class_id == $item->id || $t->merged_class_id == $item->id)
                                                && $t->day === $day
                                                && $t->period_no == $period->period_no;
                                        })->values()->all();
                                    } else {
                                        $entries = $timetable->where('teacher_id', $item->id)
                                            ->where('day', $day)
                                            ->where('period_no', $period->period_no)
                                            ->all();
                                    }

                                    // 2. Check for Manual Overrides (Higher Priority)
                                    if ($mode === 'teacher') {
                                        // Management Logic (Period 1 for specific teachers)
                                        // General Management
                                        $managementTeachers = ['Muhammad Amin', 'Rana Zahid'];
                                        if (in_array($item->name, $managementTeachers) && $period->period_no == 1) {
                                            $overrideContent = [
                                                'detail' => '', 
                                                'subject' => 'Management',
                                                'is_bold' => false // Subject style
                                            ];
                                        }

                                        // Mr. Owais - Management + Arrangement
                                        if ($item->name === 'Muhammad Owais Ur Rehman' && $period->period_no == 1) {
                                            $overrideContent = [
                                                'detail' => '', 
                                                'subject' => 'Management + Arrangement',
                                                'is_bold' => false // Subject style
                                            ];
                                        }
                                        
                                        // Temporary: Mr. Muhammad Owais Ur Rehman - Period 3 - 12A Computer Science (Days 4-5 = Thu, Fri)
                                        if ($item->name === 'Muhammad Owais Ur Rehman' && $period->period_no == 3 && in_array($day, ['Thursday', 'Friday'])) {
                                            $overrideContent = [
                                                'detail' => '12A',
                                                'subject' => 'Computer Science',
                                                'is_bold' => true // Class style
                                            ];
                                        }

                                        // Temporary: Mr. Ranjhan Khan - Period 3 - 12A Pakstudy (Days 1-3 = Mon, Tue, Wed)
                                        // User clarification: "periods are not 1,2,3 but these are the days"
                                        // Context implies Period 3 for him as well.
                                        if ($item->name === 'Ranjhan Khan' && $period->period_no == 3 && in_array($day, ['Monday', 'Tuesday', 'Wednesday'])) {
                                            $overrideContent = [
                                                'detail' => '12A',
                                                'subject' => 'Pakstudy',
                                                'is_bold' => true // Class style
                                            ];
                                        }
                                    }

                                    // 3. Increment Count & Calculate Rowspan
                                    $hasContent = false;
                                    if ($overrideContent) {
                                        $lessonCount++;
                                        $hasContent = true;
                                    } elseif (count($entries) > 0) {
                                        $lessonCount++;
                                        $hasContent = true;
                                    }
                                    
                                    $rowspan = 1;
                                    if ($mode === 'class' && count($entries) > 0) {
                                        $hasMerged = false;
                                        foreach($entries as $e) {
                                            if(!empty($e->merged_class_id)) $hasMerged = true;
                                        }
                                        if ($hasMerged) {
                                            $currentIds = collect($entries)->pluck('id')->sort()->values()->toJson();
                                            for ($nextIndex = $itemIndex + 1; $nextIndex < count($items); $nextIndex++) {
                                                $nextItem = $items[$nextIndex];
                                                $nextEntries = $timetable->filter(function ($t) use ($nextItem, $day, $period) {
                                                    return ($t->class_id == $nextItem->id || $t->merged_class_id == $nextItem->id)
                                                        && $t->day === $day
                                                        && $t->period_no == $period->period_no;
                                                })->values()->all();
                                                $nextIds = collect($nextEntries)->pluck('id')->sort()->values()->toJson();
                                                if ($currentIds === $nextIds && $currentIds !== '[]') {
                                                    $rowspan++;
                                                    $skipCells[$nextItem->id][$period->period_no] = true;
                                                } else {
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <td rowspan="{{ $rowspan }}" class="{{ $period->is_break ? 'break' : '' }}">
                                    @if($period->is_break || $period->is_assembly)
                                        {{ $period->label }}
                                    @else
                                        @if($period->period_no == 0)
                                            <div style="font-size: 10px; color: #888; font-style: italic; margin-bottom: 2px;">Assembly</div>
                                        @endif
                                        
                                        
                                        @if($overrideContent)
                                            {{-- Render Override Content --}}
                                            @if($overrideContent['detail'])
                                                <div class="detail" style="font-weight: bold;">{{ $overrideContent['detail'] }}</div>
                                            @endif
                                            <div class="subject" style="{{ $overrideContent['is_bold'] ? '' : 'font-size: 14px; color: #000;' }}">{{ $overrideContent['subject'] }}</div>
                                        @elseif(count($entries) > 0)
                                            {{-- Render Regular Entries --}}
                                            @foreach($entries as $entry)
                                                @if(!$loop->first) <hr style="margin: 3px 0; border: 0; border-top: 1px dashed #ccc;"> @endif
                                                <div class="detail">
                                                    {{ str_replace('Class ', '', $mode === 'class' ? ($entry->teacher->name ?? '') : ($entry->class->name ?? '')) }}
                                                </div>
                                                <div class="subject">{{ $entry->subject->name ?? '' }}</div>
                                                
                                                @php
                                                    $detailName = $mode === 'class' ? ($entry->teacher->name ?? '') : ($entry->class->name ?? '');
                                                @endphp
                                                @if($entry->room && $entry->room !== $detailName)
                                                    <div class="detail" style="font-weight: normal; font-size: 12px;">({{ $entry->room }})</div>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            @php
                                // Hardcoded Count Reset
                                if ($mode === 'teacher') {
                                    if ($item->name === 'Rana Zahid') {
                                        $lessonCount = 5;
                                    } elseif ($item->name === 'Muhammad Amin') {
                                        $lessonCount = 2;
                                    }
                                }
                            @endphp
                            <td style="font-weight: bold;">{{ $lessonCount }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
