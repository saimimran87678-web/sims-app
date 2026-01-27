<!DOCTYPE html>
<html>
<head>
    <title>Teacher Arrangement</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        
        .section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; margin-top: 20px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .status-absent { color: red; font-weight: bold; }
        .status-leave { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teacher Arrangement Report</h1>
        <p>{{ $date }}</p>
    </div>

    <!-- Absentees Section -->
    <div class="section-title">Absent / On Leave Teachers</div>
    @if($absentees->isEmpty())
        <p>No teachers absent or on leave today.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absentees as $record)
                <tr>
                    <td>{{ $record->teacher->name }}</td>
                    <td class="{{ $record->status == 'absent' ? 'status-absent' : 'status-leave' }}">
                        {{ ucfirst($record->status) }}
                    </td>
                    <td>{{ $record->remarks }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Substitutions Section -->
    <div class="section-title">Substitution Arrangements</div>
    @if($substitutions->isEmpty())
        <p>No substitutions assigned.</p>
    @else
        @php
            $groupedSubstitutions = $substitutions->groupBy(function($item) {
                return $item->teacherAttendance->teacher->name ?? 'Unknown Teacher';
            });
        @endphp

        @foreach($groupedSubstitutions as $teacherName => $teacherSubs)
            <div style="margin-top: 15px; page-break-inside: avoid;">
                <h3 style="margin-bottom: 5px; font-size: 14px; color: #444;">{{ $teacherName }} <span style="font-weight: normal; font-size: 12px; color: #666;">(Absent/Leave)</span></h3>
                <table style="margin-top: 5px;">
                    <thead>
                        <tr>
                            <th width="10%">Period</th>
                            <th width="50%">Class & Subject</th>
                            <th width="40%">Substitute Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teacherSubs as $sub)
                        <tr>
                            <td style="text-align: center;">{{ $sub->timetable->period_no }}</td>
                            <td>
                                {{ $sub->timetable->class->name ?? '' }} - 
                                {{ $sub->timetable->subject->name ?? '' }}
                                @if($sub->timetable->subject2) / {{ $sub->timetable->subject2->name }} @endif
                            </td>
                            <td>
                                @if($sub->substituteTeacher)
                                    <b>{{ $sub->substituteTeacher->name }}</b>
                                @else
                                    <span style="color: #999;">Not Assigned</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
    
    <div style="margin-top: 30px; font-size: 10px; color: #999; text-align: center;">
        Generated on {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
