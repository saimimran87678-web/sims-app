<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Datesheet - {{ $exam->name }}</title>
    <style>
        @media print {
            @page {
                /* Auto size - let user choose Portrait/Landscape */
                margin: 5mm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-family: "Times New Roman", Times, serif;
            }
            .print-hidden {
                display: none !important;
            }
            
            /* Table Styling */
            table {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid black;
                font-size: 10pt; /* Auto-scale base */
            }
            th, td {
                border: 1px solid black !important;
                padding: 4px !important;
                text-align: center;
                height: auto !important;
            }
            th {
                background-color: #d1d5db !important; /* bg-gray-300 */
                font-weight: bold;
                text-transform: uppercase;
            }
            
            /* Header Styling */
            .header-title { font-size: 16pt; font-weight: bold; text-transform: uppercase; text-align: center; }
            .header-subtitle { font-size: 12pt; text-align: center; margin-bottom: 10px; }
            .exam-title { font-size: 14pt; font-weight: bold; text-align: center; margin: 10px 0; text-transform: uppercase; }

            /* Footer Styling */
            .footer-note { font-size: 9pt; margin-top: 10px; }
            .signature-line { border-top: 1px solid black; width: 200px; margin-top: 40px; }
            
            /* Landscape Adjustments */
            @media (orientation: landscape) {
                table { font-size: 11pt; }
            }
            /* Portrait Adjustments */
            @media (orientation: portrait) {
                table { font-size: 9pt; }
                th, td { padding: 2px !important; }
            }
            
            /* Helpers */
            .bg-holiday { background-color: #9ca3af !important; } /* darker gray */
        }
        
        /* Screen Styles for preview */
        body { font-family: sans-serif; padding: 20px; }
        .screen-only { display: block; margin-bottom: 20px; }
        @media print { .screen-only { display: none; } }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: center; }
        th { background: #eee; }
        .bg-holiday { background-color: #ccc; }
    </style>
</head>
<body class="bg-white">

    <div class="screen-only text-center">
        <button onclick="window.print()" style="padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer;">
            Print / Save as PDF
        </button>
    </div>

    {{-- Formal Header --}}
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold uppercase" style="font-size: 18pt; margin-bottom: 5px;">ISLAMABAD MODEL COLLEGE FOR BOYS (VI-X)</h1>
        <h2 class="text-lg" style="font-size: 14pt; margin-top: 0;">G-6/2 ISLAMABAD</h2>
        <div class="text-xl font-bold mt-4 uppercase" style="font-size: 16pt; text-decoration: underline;">DATE SHEET {{ $exam->name }}</div>
    </div>

    {{-- Main Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Date & Time</th>
                <th style="width: 10%">Day</th>
                @foreach($grades as $grade => $classes)
                    {{-- Logic: If we want to show merged columns for whole Grade or split per Class --}}
                    {{-- For simplest Print view, let's assume one column per Grade as requested by typical formats, 
                         BUT if classes have diff papers, we might need split. 
                         For this implementation: We check distinct subjects for the grade on dates.
                         To keep it simple: We map 1 Column per Grade. If split, we show e.g. "10A: Math / 10B: Phy"
                    --}}
                    <th>Class {{ $grade }}th</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($schedules as $date => $entries)
                <tr>
                    {{-- Date Column --}}
                    <td class="font-bold bg-gray-100">
                        {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                    </td>
                    
                    {{-- Day Column --}}
                    <td class="uppercase text-sm">
                        {{ \Carbon\Carbon::parse($date)->format('l') }}
                    </td>

                    {{-- Grade Columns --}}
                    @foreach($grades as $grade => $classes)
                        @php
                            // Aggregate subjects for this grade on this date
                            $subjectsArr = [];
                            $isHolidayAll = true;
                            
                            foreach ($classes as $class) {
                                $entry = $entries->where('class_id', $class->id)->first();
                                $sub = $entry ? $entry->subject : '-';
                                
                                if ($sub !== 'Holiday') $isHolidayAll = false;
                                
                                // Append Marks if available
                                if ($sub && $sub !== '-' && $sub !== 'Holiday') {
                                    $marks = $marksData[$class->id][$sub] ?? '';
                                    if ($marks) $sub .= " ({$marks})";
                                }
                                
                                $subjectsArr[$class->name] = $sub;
                            }
                            
                            // Visualize Logic
                            // 1. If all classes have same subject -> Show once
                            // 2. If different -> Show "10A: Math / 10B: Phy"
                            
                            $uniqueSubjects = array_unique(array_values($subjectsArr));
                            $finalText = '';
                            
                            if (count($uniqueSubjects) === 1) {
                                $finalText = $uniqueSubjects[0];
                            } else {
                                // Split View
                                $parts = [];
                                foreach ($subjectsArr as $clsName => $s) {
                                    if ($s !== '-') $parts[] = "$clsName: $s";
                                }
                                $finalText = implode(' / ', $parts);
                                if (empty($finalText)) $finalText = '-';
                            }
                            
                            $isHoliday = ($finalText === 'Holiday');
                        @endphp
                        
                        <td class="{{ $isHoliday ? 'bg-holiday' : '' }}">
                            @if($finalText == '-' || $finalText == '')
                                <span class="text-xl font-bold">&mdash;</span>
                            @elseif($isHoliday)
                                <span style="display:none">Holiday</span>
                            @else
                                <span class="font-bold text-lg">{{ $finalText }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="mt-4 text-sm font-sans" style="font-family: Arial, sans-serif; margin-top: 20px;">
        <strong>NOTE:</strong>
        <ol class="list-decimal list-inside" style="margin-top: 5px;">
            <li>Students must arrive at least 15 minutes before the exam start time.</li>
            <li>Examination will be conducted from the prescribed course.</li>
            <li>Paper will start at 9:00 A.M. (Unless specified otherwise).</li>
        </ol>
    </div>

    {{-- Signatures --}}
    <div class="flex justify-between mt-12 px-8" style="display: flex; justify-content: space-between; margin-top: 50px; padding: 0 40px;">
        <div class="text-center">
            <div style="border-top: 1px solid black; width: 200px; padding-top: 5px; text-align: center;">Controller of Examination</div>
        </div>
        <div class="text-center">
            <div style="border-top: 1px solid black; width: 200px; padding-top: 5px; text-align: center;">Principal</div>
        </div>
    </div>

</body>
</html>
