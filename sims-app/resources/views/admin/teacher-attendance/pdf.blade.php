<!DOCTYPE html>
<html>
<head>
    <title>Teacher Arrangement</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #000; }
        .header p { margin: 5px 0; color: #666; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; border: 1.5px solid #444; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; font-size: 12px; height: 30px; }
        
        .absent-cell { width: 30%; vertical-align: top; }
        .arrangement-cell { width: 70%; vertical-align: top; padding: 10px 15px; }

        .teacher-name { font-size: 14px; font-weight: bold; margin-bottom: 4px; color: #000; }
        .status-line { font-size: 11px; }
        .status-label { font-weight: normal; }
        .status-value { font-weight: bold; color: #000; }

        .arrangement-row { margin-bottom: 10px; line-height: 1.5; display: block; clear: both; }
        .period-box { 
            display: inline-block;
            background-color: #e0e0e0;
            color: #333;
            width: 26px;
            height: 26px;
            text-align: center;
            line-height: 26px;
            font-weight: bold;
            border-radius: 4px;
            margin-right: 10px;
            font-size: 13px;
        }
        .class-subject { font-size: 13px; color: #333; font-weight: bold; }
        .substitute-name { color: #1a73e8; font-weight: bold; font-size: 13px; }
        .not-assigned { color: #d93025; font-weight: bold; font-size: 13px; }
        
        .footer { margin-top: 30px; font-size: 9px; color: #999; text-align: center; }

        .status-leave { color: #28a745; }        /* Green */
        .status-official-duty { color: #007bff; } /* Blue */
        .status-short-leave { color: #d39e00; }   /* Dark Orange/Mustard */
        .status-absent { color: #dc3545; }        /* Red */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Teacher Arrangement Report</h1>
            <p>{{ $date }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Absent Teacher</th>
                    <th>Arrangements / Substitutions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absentees as $record)
                <tr>
                    <td class="absent-cell">
                        <div class="teacher-name">{{ $record->teacher->name }}</div>
                        <div class="status-line">
                            @if($record->status !== 'present')
                                <span class="status-label">Status:</span>
                                @php
                                    $statusColorClass = match($record->status) {
                                        'leave' => 'status-leave',
                                        'official_duty' => 'status-official-duty',
                                        'short_leave' => 'status-short-leave',
                                        default => 'status-absent',
                                    };
                                @endphp
                                <span class="status-value {{ $statusColorClass }}">{{ ucwords(str_replace('_', ' ', $record->status)) }}</span>
                            @else
                                <span class="status-label" style="color: #666; font-style: italic;">(Present with Arrangements)</span>
                            @endif
                        </div>
                        @if($record->remarks)
                            <div style="font-style: italic; color: #666; margin-top: 6px; font-size: 10px;">
                                "{{ $record->remarks }}"
                            </div>
                        @endif
                    </td>
                    <td class="arrangement-cell">
                        @php 
                            $teacherSubs = $groupedSubstitutions->get($record->user_id);
                        @endphp

                        @if($teacherSubs && $teacherSubs->count() > 0)
                            @foreach($teacherSubs as $sub)
                                <div class="arrangement-row">
                                    <span class="period-box">
                                        {{-- We use the number part of the label if it's like "Period 1" --}}
                                        @php
                                            $label = $periodLabels[$sub->period_no] ?? $sub->period_no;
                                            $shortLabel = preg_replace('/[^0-9]/', '', $label);
                                            // Fallback to label if no numbers found
                                            if (!$shortLabel) $shortLabel = $label;
                                        @endphp
                                        {{ $shortLabel }}
                                    </span>
                                    <span class="class-subject">
                                        {{ $sub->class_name }} - {{ $sub->subject_name }} : 
                                    </span>
                                    @if($sub->substitute_name)
                                        <span class="substitute-name">
                                            {{ $sub->substitute_name }}
                                        </span>
                                    @else
                                        <span class="not-assigned">Not Assigned</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div style="color: #999; font-style: italic; font-size: 11px;">No arrangements scheduled.</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" style="text-align: center; padding: 20px; color: #666;">
                        All teachers are present today.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if(!empty($dailyNote))
            <div style="margin-top: 20px; border: 1px solid #444; padding: 10px; background-color: #f9f9f9;">
                <div style="font-weight: bold; margin-bottom: 5px; font-size: 13px;">Note:</div>
                <div style="font-size: 13px; color: #000; font-weight: bold; white-space: pre-wrap;">{{ $dailyNote }}</div>
            </div>
        @endif

        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
