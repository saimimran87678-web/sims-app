<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Datesheet - {{ $exam->name }}</title>
    <style>
        /* Base page styling */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 30px 20px;
        }

        .screen-only {
            display: block;
            margin-bottom: 25px;
            text-align: center;
        }

        .btn-print {
            padding: 12px 28px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.2);
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            background: #1d4ed8;
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
        }

        /* Printable layout */
        .datesheet-container {
            max-width: 1100px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .school-name {
            font-size: 26px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 6px 0;
        }

        .school-address {
            font-size: 13px;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        .datesheet-title-badge {
            border-top: 2px solid #1e3a8a;
            border-bottom: 2px solid #1e3a8a;
            padding: 10px 0;
            margin: 25px 0;
            text-align: center;
        }

        .datesheet-title-text {
            font-size: 18px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Table design */
        .datesheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #cbd5e1;
        }

        .datesheet-table th {
            background-color: #f1f5f9;
            color: #1e3a8a;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .datesheet-table td {
            padding: 12px 10px;
            font-size: 11px;
            color: #334155;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        .datesheet-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .date-col {
            font-weight: 700;
            color: #0f172a;
        }

        .day-col {
            text-transform: uppercase;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
        }

        .bg-holiday {
            background-color: #f1f5f9 !important;
            color: #94a3b8;
            font-style: italic;
            font-weight: bold;
        }

        .empty-cell {
            color: #cbd5e1;
            font-size: 14px;
            font-weight: bold;
        }

        .subject-text {
            font-weight: 700;
            color: #0f172a;
        }

        /* Note card */
        .note-card {
            background: #f8fafc;
            border-left: 4px solid #1e3a8a;
            padding: 12px 15px;
            border-radius: 4px;
            margin-top: 35px;
            font-size: 11px;
            line-height: 1.55;
            color: #475569;
            text-align: left;
        }

        .note-title {
            color: #1e3a8a;
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Signatures block */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding: 0 20px;
        }

        .sig-container {
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #cbd5e1;
            width: 200px;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        /* Print overrides */
        @media print {
            @page {
                size: auto;
                margin: 10mm;
            }
            body {
                padding: 0;
                background-color: #ffffff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .screen-only {
                display: none !important;
            }
            .datesheet-container {
                max-width: 100%;
                border: none;
                box-shadow: none;
                padding: 0;
                background-color: #ffffff;
            }
            .datesheet-table {
                border: 1px solid #000;
                box-shadow: none;
            }
            .datesheet-table th, .datesheet-table td {
                border: 1px solid #000 !important;
            }
            .datesheet-table th {
                background-color: #f1f5f9 !important;
                color: #000 !important;
            }
            .note-card {
                border-left: 4px solid #000 !important;
                background: #f8fafc !important;
            }
            .sig-line {
                border-top: 1.5px solid #000 !important;
            }
        }
    </style>
</head>
<body>

    <div class="screen-only">
        <button onclick="window.print()" class="btn-print">
            Print / Save as PDF
        </button>
    </div>

    <div class="datesheet-container">
        {{-- Formal Header --}}
        <div class="header-section">
            @php
                $logoPath = \App\Models\Setting::getGlobal('institute_logo');
            @endphp
            @if($logoPath && file_exists(public_path($logoPath)))
                <div style="margin-bottom: 12px;">
                    <img src="{{ '/' . $logoPath }}" style="height: 60px; max-width: 150px; object-fit: contain;">
                </div>
            @endif
            
            <h1 class="school-name">
                {{ \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS')) }}
            </h1>
            
            @if($address = \App\Models\Setting::getGlobal('institute_address'))
                <h2 class="school-address">{{ $address }}</h2>
            @endif
            
            <div class="datesheet-title-badge">
                <span class="datesheet-title-text">DATE SHEET - {{ $exam->name }}</span>
            </div>
        </div>

        {{-- Main Table --}}
        <table class="datesheet-table">
            <thead>
                <tr>
                    <th style="width: 15%">Date & Time</th>
                    <th style="width: 12%">Day</th>
                    @foreach($grades as $grade => $classes)
                        <th>Class {{ $grade }}th</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($schedules as $date => $entries)
                    <tr>
                        {{-- Date Column --}}
                        <td class="date-col">
                            {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                        </td>
                        
                        {{-- Day Column --}}
                        <td class="day-col">
                            {{ \Carbon\Carbon::parse($date)->format('l') }}
                        </td>

                        {{-- Grade Columns --}}
                        @foreach($grades as $grade => $classes)
                            @php
                                // Aggregate subjects for this grade on this date
                                $subjectsArr = [];
                                $isHoliday = false;
                                
                                foreach ($classes as $class) {
                                    $entry = $entries->where('class_id', $class->id)->first();
                                    $sub = $entry ? trim($entry->subject) : '-';
                                    
                                    if ($sub === 'Holiday') {
                                        $isHoliday = true;
                                        continue;
                                    }
                                    
                                    if ($sub && $sub !== '-' && $sub !== '') {
                                        // Split by comma or slash to get individual subjects
                                        $parts = preg_split('/[\/,]+/', $sub);
                                        foreach ($parts as $part) {
                                            $partClean = trim($part);
                                            if ($partClean && $partClean !== '-') {
                                                $subjectsArr[] = $partClean;
                                            }
                                        }
                                    }
                                }
                                
                                // De-duplicate clean subjects
                                $uniqueCleanSubjects = array_unique($subjectsArr);
                                
                                if ($isHoliday && empty($uniqueCleanSubjects)) {
                                    $finalText = 'Holiday';
                                } else {
                                    $finalText = implode(', ', $uniqueCleanSubjects);
                                    if (empty($finalText)) $finalText = '-';
                                }
                                
                                $isHoliday = ($finalText === 'Holiday');
                            @endphp
                            
                            <td class="{{ $isHoliday ? 'bg-holiday' : '' }}">
                                @if($finalText == '-' || $finalText == '')
                                    <span class="empty-cell">&mdash;</span>
                                @elseif($isHoliday)
                                    <span>Holiday</span>
                                @else
                                    <span class="subject-text">{{ $finalText }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer Note Card --}}
        @php
            $heading = $exam->datesheet_instructions_heading ?: 'Important Instructions';
            $instructions = $exam->datesheet_instructions;
        @endphp
        <div class="note-card">
            <span class="note-title">{{ $heading }}</span>
            @if($instructions)
                <div style="white-space: pre-line; line-height: 1.5;">{{ $instructions }}</div>
            @else
                <ol style="margin: 0; padding-left: 15px;">
                    <li style="margin-bottom: 4px;">Students must arrive at least 15 minutes before the exam start time.</li>
                    <li style="margin-bottom: 4px;">Examination will be conducted from the prescribed course.</li>
                    <li>Paper will start at 9:00 A.M. (Unless specified otherwise).</li>
                </ol>
            @endif
        </div>

        {{-- Signatures --}}
        <div class="signature-section">
            <div class="sig-container">
                <div class="sig-line">Controller of Examination</div>
            </div>
            <div class="sig-container">
                <div class="sig-line">Principal</div>
            </div>
        </div>
    </div>

</body>
</html>
