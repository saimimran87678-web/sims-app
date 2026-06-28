<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Challan #{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm 6mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            -webkit-print-color-adjust: exact;
        }
        .challan-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .challan-column {
            width: 31%;
            vertical-align: top;
            box-sizing: border-box;
        }
        .divider {
            width: 3.5%;
            text-align: center;
            vertical-align: top;
            position: relative;
        }
        .divider-line {
            border-left: 1.5px dashed #94a3b8;
            height: 520px;
            margin: 0 auto;
            position: relative;
        }
        .scissors-icon {
            position: absolute;
            top: 40%;
            left: -8px;
            background: #ffffff;
            padding: 4px 0;
            color: #64748b;
            font-size: 12px;
        }
        .header-logo-text {
            text-align: center;
            margin-bottom: 6px;
        }
        .school-name {
            margin: 0;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        .school-info {
            margin: 1px 0 0;
            color: #64748b;
            font-size: 7.5px;
            line-height: 1.2;
        }
        .copy-tag {
            background-color: #1e3a8a;
            color: #ffffff;
            text-align: center;
            padding: 3px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8.5px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1.5px 0;
        }
        .student-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 6px;
            background: #f8fafc;
            margin-bottom: 8px;
        }
        .student-table {
            width: 100%;
            font-size: 8.5px;
            border-collapse: collapse;
            line-height: 1.35;
        }
        .student-table td.label {
            color: #64748b;
            width: 32%;
        }
        .student-table td.value {
            font-weight: bold;
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 3.5px 5px;
            font-weight: bold;
            border-bottom: 1.5px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 8px;
        }
        .items-table td {
            padding: 3.5px 5px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table .amount-col {
            text-align: right;
            font-weight: bold;
        }
        .items-table tfoot td {
            font-weight: bold;
            background: #f8fafc;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: none;
            padding: 4px 5px;
        }
        .bank-details {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            background: #fffbeb;
            font-size: 7.5px;
            color: #451a03;
            line-height: 1.3;
            margin-bottom: 15px;
        }
        .bank-title {
            font-weight: bold;
            color: #b45309;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .stamp-paid {
            position: absolute;
            top: 140px;
            left: 20px;
            border: 3px solid #16a34a;
            color: #16a34a;
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 4px 10px;
            transform: rotate(-12deg);
            opacity: 0.75;
            border-radius: 4px;
        }
        .sig-table {
            width: 100%;
            font-size: 7.5px;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .sig-table td {
            text-align: center;
            color: #64748b;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    @php
        $previousArrears = \App\Models\FeeRecord::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->where('period', '<', $record->period)
            ->sum('balance');
            
        $copies = [
            'bank' => 'Bank Copy',
            'school' => 'School Copy',
            'student' => 'Student Copy'
        ];
    @endphp

    <table class="challan-table">
        <tr>
            @foreach($copies as $key => $title)
                <!-- Challan Copy Column -->
                <td class="challan-column" style="position: relative;">
                    <!-- Paid stamp overlay -->
                    @if($record->status === 'paid')
                        <div class="stamp-paid">PAID</div>
                    @endif

                    <!-- Header -->
                    <div class="header-logo-text">
                        <h2 class="school-name">{{ $instituteName }}</h2>
                        <p class="school-info">{{ $instituteAddress }}</p>
                        <p class="school-info" style="font-weight: bold;">Ph: {{ $institutePhone }}</p>
                    </div>

                    <!-- Copy Title Tag -->
                    <div class="copy-tag" style="{{ $key === 'bank' ? 'background-color: #1e3a8a;' : ($key === 'school' ? 'background-color: #0f766e;' : 'background-color: #475569;') }}">
                        {{ $title }}
                    </div>

                    <!-- Challan Metadata -->
                    <table class="meta-table">
                        <tr>
                            <td><strong>Challan No:</strong> <span style="color: #b91c1c; font-weight: bold; font-size: 9px;">{{ $invoiceNumber }}</span></td>
                            <td style="text-align: right;"><strong>Billing Month:</strong> {{ \Carbon\Carbon::parse($record->period . '-01')->format('M Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Issue Date:</strong> {{ $record->created_at->format('d-M-Y') }}</td>
                            <td style="text-align: right; color: #b91c1c;"><strong>Due Date:</strong> <strong>{{ $record->due_date->format('d-M-Y') }}</strong></td>
                        </tr>
                    </table>

                    <!-- Student Info Box -->
                    <div class="student-box">
                        <table class="student-table">
                            <tr>
                                <td class="label">Student Name:</td>
                                <td class="value">{{ $student->name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Father Name:</td>
                                <td class="value">{{ $student->father_name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Class / Roll No:</td>
                                <td class="value">{{ $record->class->name }} / {{ $student->roll_no }}</td>
                            </tr>
                            <tr>
                                <td class="label">Admission No:</td>
                                <td class="value">{{ $student->admission_no }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Fee Breakdown Table -->
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Fee Heads</th>
                                <th style="text-align: right;">Amount (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->items as $item)
                                <tr>
                                    <td>{{ $item->fee_head_name }}</td>
                                    <td class="amount-col">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach

                            @if($previousArrears > 0)
                                <tr style="background-color: #fffaf0;">
                                    <td style="color: #b45309; font-weight: bold;">Previous Arrears</td>
                                    <td class="amount-col" style="color: #b45309;">{{ number_format($previousArrears, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td style="font-size: 9px; color: #0f172a;">Total Payable:</td>
                                <td class="amount-col" style="font-size: 9px; color: #b91c1c;">Rs. {{ number_format(($record->balance + $previousArrears), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Deposit Instructions -->
                    <div class="bank-details">
                        <p class="bank-title">Payment Instructions</p>
                        <div style="margin-top: 1px;">
                            • Deposit fee in HBL (A/C: 1234-567890-01) or Bank Alfalah (A/C: 9876-543210-02).<br>
                            • Please verify challan details before depositing.<br>
                            • Retain your copy and ensure stamp is placed after payment.
                        </div>
                    </div>

                    <!-- Signatures -->
                    <table class="sig-table">
                        <tr>
                            <td style="width: 30%;" class="sig-line">Depositor</td>
                            <td style="width: 5%;"></td>
                            <td style="width: 30%;" class="sig-line">Cashier / Bank</td>
                            <td style="width: 5%;"></td>
                            <td style="width: 30%;" class="sig-line">Authorized Sign</td>
                        </tr>
                    </table>
                </td>

                <!-- Divider between columns -->
                @if(!$loop->last)
                    <td class="divider">
                        <div class="divider-line"></div>
                        <div class="scissors-icon">✂</div>
                    </td>
                @endif
            @endforeach
        </tr>
    </table>

</body>
</html>
