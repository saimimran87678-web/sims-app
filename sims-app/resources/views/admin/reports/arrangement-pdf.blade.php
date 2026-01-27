<!DOCTYPE html>
<html>
<head>
    <title>Teacher Arrangement Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; margin-bottom: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
        
        .sub-item { margin-bottom: 8px; page-break-inside: avoid; }
        .sub-period { display: inline-block; width: 20px; font-weight: bold; margin-right: 5px; background: #eee; text-align: center; border: 1px solid #ccc; border-radius: 3px; font-size: 11px; }
        .sub-details { display: inline-block; }
        .sub-teacher { font-weight: bold; }
        .text-red { color: #d32f2f; }
        .text-blue { color: #1976d2; }
        
        .absent-name { font-size: 14px; font-weight: bold; }
        .absent-remarks { font-style: italic; color: #666; font-size: 11px; margin-top: 4px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 10px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teacher Arrangement Report</h1>
        <p><strong>Date:</strong> {{ $date }}</p>
    </div>

    @if(empty($reportData))
        <div style="text-align: center; margin-top: 50px; font-size: 14px; color: #666;">
            No teachers absent or on leave for this date.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th width="35%">Absent Teacher</th>
                    <th width="65%">Arrangements / Substitutions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td>
                            <div class="absent-name">{{ $row['teacher'] }}</div>
                            <div style="margin-top: 2px;">Status: <strong>{{ ucfirst($row['status']) }}</strong></div>
                            @if($row['remarks'])
                                <div class="absent-remarks">"{{ $row['remarks'] }}"</div>
                            @endif
                        </td>
                        <td>
                            @if(empty($row['schedule']))
                                <i>No classes scheduled for today.</i>
                            @else
                                @foreach($row['schedule'] as $sched)
                                    <div class="sub-item">
                                        <span class="sub-period">{{ $sched['period'] }}</span>
                                        <span class="sub-details">
                                            {{ $sched['class'] }} - {{ $sched['subject'] }}
                                            : <span class="sub-teacher {{ $sched['substitute'] == 'Not Assigned' ? 'text-red' : 'text-blue' }}">
                                                {{ $sched['substitute'] }}
                                            </span>
                                        </span>
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
