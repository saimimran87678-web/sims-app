<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>School Leaving Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a1a;
            background: #ffffff;
            font-size: 11pt;
            line-height: 1.4;
        }
        .certificate-container {
            border: 3px double #78350f;
            padding: 20px 25px;
            position: relative;
            background: #ffffff;
            min-height: 980px;
        }
        .inner-border {
            border: 1px solid #d97706;
            padding: 15px 20px;
            min-height: 940px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #78350f;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .logo-cell {
            width: 80px;
            vertical-align: middle;
            text-align: center;
        }
        .logo-img {
            max-width: 75px;
            max-height: 75px;
        }
        .institute-cell {
            vertical-align: middle;
            text-align: center;
            padding: 0 10px;
        }
        .institute-title {
            font-size: 20pt;
            font-weight: bold;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .institute-contact {
            font-size: 9pt;
            color: #4b5563;
        }
        .title-banner {
            text-align: center;
            margin: 15px 0 20px 0;
        }
        .title-badge {
            display: inline-block;
            background: #78350f;
            color: #ffffff;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 5px 25px;
            border-radius: 4px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 18px;
            font-size: 9.5pt;
        }
        .meta-table td {
            padding: 3px 0;
        }
        .cert-number {
            font-family: monospace;
            font-weight: bold;
            color: #b45309;
            font-size: 11pt;
        }
        .fields-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .fields-table td {
            padding: 6px 4px;
            vertical-align: bottom;
            font-size: 10pt;
        }
        .sr-col {
            width: 25px;
            font-weight: bold;
            color: #78350f;
        }
        .label-col {
            width: 280px;
            color: #374151;
            font-weight: 500;
        }
        .colon-col {
            width: 15px;
            text-align: center;
            font-weight: bold;
        }
        .value-col {
            border-bottom: 1px dotted #9ca3af;
            font-weight: bold;
            color: #111827;
            padding-left: 5px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            text-align: center;
        }
        .sig-col {
            width: 33.33%;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 1px solid #4b5563;
            margin-top: 45px;
            padding-top: 5px;
            font-size: 9pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
        }
        .seal-box {
            display: inline-block;
            border: 1px dashed #9ca3af;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            line-height: 80px;
            font-size: 8pt;
            color: #9ca3af;
            text-transform: uppercase;
        }
        .footer-notice {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 7.5pt;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="certificate-container">
    <div class="inner-border">
        {{-- Institute Header --}}
        <table class="header-table">
            <tr>
                @if(!empty($instituteLogo))
                    <td class="logo-cell">
                        <img src="{{ $instituteLogo }}" class="logo-img" alt="Logo" />
                    </td>
                @endif
                <td class="institute-cell">
                    <div class="institute-title">{{ $instituteName ?? 'School Information Management System' }}</div>
                    <div class="institute-contact">
                        @if(!empty($instituteAddress)) {{ $instituteAddress }} @endif
                        @if(!empty($institutePhone)) &bull; Phone: {{ $institutePhone }} @endif
                        @if(!empty($instituteEmail)) &bull; Email: {{ $instituteEmail }} @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Certificate Banner --}}
        <div class="title-banner">
            <span class="title-badge">School Leaving Certificate</span>
        </div>

        {{-- Meta Serial & Dates --}}
        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Certificate No:</strong> <span class="cert-number">{{ $certificate->certificate_number }}</span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Date of Issue:</strong> {{ $certificate->issue_date ? $certificate->issue_date->format('d F, Y') : now()->format('d F, Y') }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Admission / G.R. No:</strong> <span style="font-family: monospace; font-weight: bold;">{{ $student->admission_no }}</span>
                </td>
                <td style="text-align: right;">
                    <strong>Academic Session:</strong> {{ $session->name ?? 'Current Session' }}
                </td>
            </tr>
        </table>

        {{-- Certificate Body Fields --}}
        <table class="fields-table">
            <tr>
                <td class="sr-col">1.</td>
                <td class="label-col">Name of Pupil</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="font-size: 11pt; text-transform: uppercase;">{{ $student->name }}</td>
            </tr>
            <tr>
                <td class="sr-col">2.</td>
                <td class="label-col">Father's / Guardian's Name</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $student->father_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="sr-col">3.</td>
                <td class="label-col">Pupil's B-Form / CRC No.</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="font-family: monospace;">{{ $student->b_form_number ?? ($student->father_cnic ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="sr-col">4.</td>
                <td class="label-col">Father's / Guardian's CNIC</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="font-family: monospace;">{{ $student->father_cnic ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="sr-col">5.</td>
                <td class="label-col">Gender</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ ucfirst($student->gender ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="sr-col">6.</td>
                <td class="label-col">Date of Birth (in figures)</td>
                <td class="colon-col">:</td>
                <td class="value-col">
                    {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d-m-Y') : 'N/A' }}
                    @if($student->dob)
                        <span style="font-size: 8.5pt; font-weight: normal; color: #4b5563;">
                            ({{ \Carbon\Carbon::parse($student->dob)->format('jS \d\a\y \o\f F, Y') }})
                        </span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="sr-col">7.</td>
                <td class="label-col">Original Date of Admission</td>
                <td class="colon-col">:</td>
                <td class="value-col">
                    @php
                        $origDate = $student->original_admission_date ?? $student->admission_date;
                    @endphp
                    {{ $origDate ? \Carbon\Carbon::parse($origDate)->format('d F, Y') : 'N/A' }}
                </td>
            </tr>
            <tr>
                <td class="sr-col">8.</td>
                <td class="label-col">Class in which pupil last studied</td>
                <td class="colon-col">:</td>
                <td class="value-col">
                    <strong>{{ $enrollment->classModel->name ?? $certificate->class_at_leaving ?? '-' }}</strong>
                    @if($student && $student->section)
                        (Section: {{ $student->section->name }})
                    @endif
                    &bull; Shift: <strong style="text-transform: capitalize;">{{ $enrollment->shift_type ?? 'Regular' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="sr-col">9.</td>
                <td class="label-col">Date of Leaving School</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $certificate->leaving_date ? $certificate->leaving_date->format('d F, Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="sr-col">10.</td>
                <td class="label-col">Reason for Leaving School</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="text-transform: capitalize;">{{ str_replace('_', ' ', $certificate->reason) }}</td>
            </tr>
            <tr>
                <td class="sr-col">11.</td>
                <td class="label-col">General Conduct & Character</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $certificate->conduct ?? 'Good' }}</td>
            </tr>
            <tr>
                <td class="sr-col">12.</td>
                <td class="label-col">Institutional Dues & Clearance</td>
                <td class="colon-col">:</td>
                <td class="value-col" style="color: #065f46;">
                    @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                        Fully Cleared & Paid up to date of leaving
                    @else
                        Cleared & Approved per Institutional Policy
                    @endif
                </td>
            </tr>
            <tr>
                <td class="sr-col">13.</td>
                <td class="label-col">Remarks / Progress Summary</td>
                <td class="colon-col">:</td>
                <td class="value-col">{{ $certificate->remarks ?? 'Eligible for admission to next higher class.' }}</td>
            </tr>
        </table>

        {{-- Signatures & Seal --}}
        <table class="signatures-table">
            <tr>
                <td class="sig-col">
                    <div class="sig-line">Prepared By / Class Teacher</div>
                </td>
                <td class="sig-col">
                    <div class="seal-box">Institutional Seal</div>
                    <div style="font-size: 8pt; color: #6b7280; margin-top: 4px;">Office Stamp</div>
                </td>
                <td class="sig-col">
                    <div class="sig-line">Headmaster / Principal</div>
                </td>
            </tr>
        </table>

        {{-- Anti-Tamper Verification Notice --}}
        <div class="footer-notice">
            This certificate is an official institutional credential generated by SIMS on {{ now()->format('d M Y, h:i A') }}.
            Any erasure, alteration, or unauthorized alteration invalidates this document. Verified Serial: {{ $certificate->certificate_number }}.
        </div>
    </div>
</div>

</body>
</html>
