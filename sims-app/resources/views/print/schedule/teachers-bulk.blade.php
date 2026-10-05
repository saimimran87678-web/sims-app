<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ !empty($isSelective) ? 'Selected Teachers Timetable Dossier (' . ($teachersCount ?? '') . ' Teachers - 6-Up)' : 'All Teachers Timetable Dossier (6-Up)' }} | {{ $instituteName }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html, body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .bulk-page-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
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
            border-radius: 6px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            border: 1px solid #d1d5db;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        /* Page Container */
        .bulk-page-container {
            width: 100%;
            margin: 10px auto;
            background: #fff;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .page-break {
            page-break-after: always;
        }

        /* 3x2 Grid Table for 100% DomPDF & Print Compatibility */
        table.bulk-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4mm 3mm;
            table-layout: fixed;
            border: none;
        }

        table.bulk-grid-table td.card-cell-td {
            width: 33.333%;
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .empty-cell-td {
            border: 1px dashed #d1d5db !important;
            background: #fafafa;
        }
    </style>
</head>
<body>

    @if(empty($isPdf))
    <div class="screen-toolbar no-print">
        <button onclick="triggerPrintAndDownload()" class="btn-action btn-primary">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Dossier ({{ $teachersCount ?? '' }})
        </button>
        <button onclick="window.close()" class="btn-action btn-secondary">
            ✕ Close
        </button>
    </div>
    @endif

    @foreach($teacherPages as $pageIndex => $pageTeachers)
        <div class="bulk-page-container {{ !$loop->last ? 'page-break' : '' }}">
            <table class="bulk-grid-table">
                @foreach(array_chunk($pageTeachers, 3) as $rowTeachers)
                    <tr>
                        @foreach($rowTeachers as $tData)
                            <td class="card-cell-td">
                                <x-teacher-timetable-card 
                                    :teacherData="$tData" 
                                    :instituteName="$instituteName" 
                                    :instituteLogo="$instituteLogo ?? ''" 
                                    :logoBase64="$logoBase64 ?? null"
                                    :effectiveDate="$effectiveDate" 
                                />
                            </td>
                        @endforeach
                        @for($i = count($rowTeachers); $i < 3; $i++)
                            <td class="card-cell-td empty-cell-td"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    @if(empty($isPdf))
    <script>
        function triggerDownloadPdf() {
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('format', 'pdf');
                url.searchParams.set('download', '1');
                url.searchParams.delete('autoprint');
                var dlFrame = document.createElement('iframe');
                dlFrame.style.display = 'none';
                dlFrame.src = url.toString();
                document.body.appendChild(dlFrame);
            } catch (e) {
                console.error('PDF auto-download failed', e);
            }
        }

        function triggerPrintAndDownload() {
            triggerDownloadPdf();
            setTimeout(function() {
                window.print();
            }, 350);
        }

        @if(!empty($autoprint))
        window.addEventListener('load', function() {
            triggerDownloadPdf();
            setTimeout(function() {
                window.print();
            }, 550);
        });
        @endif
    </script>
    @endif
</body>
</html>