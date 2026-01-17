<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->name }} - Datesheet</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .college-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .college-address {
            font-size: 14px;
            margin-bottom: 20px;
        }
        .exam-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000; /* Optional: if design needs it */
            display: inline-block;
            padding-bottom: 2px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 20px;
        }
        th, td {
            text-align: center;
            vertical-align: middle;
            padding: 8px 5px;
        }
        /* Header Styling */
        thead th {
            background-color: #E5E7EB; /* Light Gray */
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
             /* Remove borders for cleaner look? Image has subtle borders */
        }
        
        /* Specific Column Widths */
        th:first-child {
            width: 15%; /* Date & Time */
            text-align: left;
            padding-left: 10px;
        }
        td:first-child {
            text-align: left;
            padding-left: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Alternating Rows or Bordered? User image: Simple rows, some shaded. */
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        /* Shaded / Break rows? */
        /* If a cell is empty, user image shows dashes or shading. */
        
        /* Footer/Notes */
        .notes-section {
            margin-top: 40px;
            font-size: 12px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notes-list {
            list-style-type: decimal;
            padding-left: 20px;
            margin: 0;
            line-height: 1.5;
        }
        .signature-section {
            margin-top: 60px;
            font-weight: bold;
        }
        
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; border-radius: 5px;">Print Datesheet</button>
    </div>

    <div class="header">
        <div class="college-name">ISLAMABAD MODEL COLLEGE FOR BOYS (VI-X)</div>
        <div class="college-address">G-6/2 ISLAMABAD</div>
        <br>
        <div class="exam-title">DATE SHEET {{ strtoupper($exam->name) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>DATE & DAY</th>
                @foreach($classes as $class)
                    <th>{{ strtoupper($class->name) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($dates as $date)
                @php 
                    $dt = \Carbon\Carbon::parse($date);
                    $isSunday = $dt->isSunday();
                    
                    // Check if any class has Holiday on this date
                    $hasHoliday = false;
                    foreach($classes as $class) {
                        $subj = $matrix[$date][$class->id] ?? null;
                        if ($subj === 'Holiday') {
                            $hasHoliday = true;
                            break;
                        }
                    }
                    
                    // Use holiday styling for Sundays or explicit Holiday entries
                    $rowStyle = ($isSunday || $hasHoliday) 
                        ? 'background-color: #fef3c7; -webkit-print-color-adjust: exact;' 
                        : '';
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td>
                        {{ $dt->format('d-m-Y') }} <br>
                        <span style="font-weight: normal; text-transform: uppercase;">{{ $dt->format('l') }}</span>
                    </td>
                    @foreach($classes as $class)
                        @php
                            $subjectRaw = $matrix[$date][$class->id] ?? null;
                            
                            // Clean the subject name - extract just the subject names
                            $subjectName = null;
                            if ($subjectRaw && $subjectRaw !== '-') {
                                // Most aggressive cleaning:
                                // 1. Remove all JSON objects {...}
                                $cleaned = preg_replace('/\{[^}]*\}/s', '', $subjectRaw);
                                // 2. Remove all parenthetical content (...)
                                $cleaned = preg_replace('/\([^)]*\)/s', '', $cleaned);
                                // 3. Remove "Class XY: " prefixes (matches Class 10A:, Class 11B:, etc)
                                $cleaned = preg_replace('/Class\s*\d+[A-Z]?\s*:\s*/i', '', $cleaned);
                                // 4. Remove any remaining quotes or colons
                                $cleaned = str_replace(['"', "'", ':'], '', $cleaned);
                                
                                // Split by comma, slash, or pipe
                                $subjects = preg_split('/[,\/\|]/', $cleaned);
                                $subjects = array_map('trim', $subjects);
                                
                                // Filter out empty, null, or placeholder values
                                $subjects = array_filter($subjects, function($s) {
                                    $s = strtolower(trim($s));
                                    return $s && $s !== '-' && $s !== 'null' && $s !== 'total' && $s !== 'passing' && strlen($s) > 1;
                                });
                                
                                $subjectName = implode(', ', array_unique($subjects));
                            }
                            
                            $isHoliday = $subjectRaw === 'Holiday';
                            $isEmpty = empty($subjectName) || $subjectName === '-';
                        @endphp
                        <td style="{{ $isHoliday ? 'background-color: #fef3c7; color: #92400e; font-style: italic; -webkit-print-color-adjust: exact;' : '' }}">
                            @if($isHoliday)
                                Holiday
                            @elseif($isEmpty)
                                &mdash;
                            @else
                                <strong>{{ $subjectName }}</strong>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="notes-section">
        <div class="notes-title">NOTE:</div>
        <ul class="notes-list">
            <li>Students must arrive at least 15 minutes before the exam start time.</li>
            <li>Examination will be conducted from the prescribed course.</li>
            <li>Paper will start at 9:00 A.M. (Unless specified otherwise).</li>
        </ul>
    </div>

</body>
</html>
