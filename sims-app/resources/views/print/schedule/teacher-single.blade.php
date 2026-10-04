<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Timetable - {{ $teacher->name }} | Adminova Timetables</title>
    <style>
        @page {
            size: auto;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px 0;
            background-color: #f3f4f6;
            color: #000;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        /* Screen Floating Toolbar */
        .screen-toolbar {
            position: fixed;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            border: 1px solid #e5e7eb;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #fff;
                padding: 0;
                display: block;
            }
            .card-wrapper {
                box-shadow: none !important;
                margin: 0 auto;
            }
        }

        /* Wrapper sized to standard teacher slip */
        .card-wrapper {
            width: 95mm;
            min-height: 135mm;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            border-radius: 2px;
            padding: 2px;
        }
    </style>
</head>
<body>

    {{-- Screen Floating Actions --}}
    <div class="screen-toolbar no-print">
        <button onclick="window.print()" class="btn-action btn-primary">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf', 'download' => 1]) }}" class="btn-action btn-secondary" style="text-decoration:none;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PDF
        </a>
        <button onclick="window.close()" class="btn-action btn-secondary">
            ✕ Close
        </button>
    </div>

    <div class="card-wrapper">
        <x-teacher-timetable-card 
            :teacherData="$teacherData" 
            :instituteName="$instituteName" 
            :instituteLogo="$instituteLogo" 
            :logoBase64="$logoBase64 ?? null"
            :effectiveDate="$effectiveDate" 
        />
    </div>

    @if(!empty($autoprint))
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 350);
        });
    </script>
    @endif
</body>
</html>
