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
            position: relative;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            background: #f8fafc;
            margin-bottom: 10px;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            line-height: 1.4;
        }
        .student-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .student-table .label {
            color: #64748b;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.15px;
            font-weight: 500;
        }
        .student-table .value {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5px;
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
            top: 6px;
            left: 15px;
            border: 2px solid #16a34a;
            color: #16a34a;
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 2px 6px;
            transform: rotate(-10deg);
            opacity: 0.95;
            z-index: 50;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.85);
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
        .challan-container {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            background-color: #ffffff;
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
                <td class="challan-column" style="position: relative; padding: 2px;">
                    <!-- Wrapper container with border-top accents -->
                    <div class="challan-container" style="border-top: 3px solid {{ $key === 'bank' ? '#1e3a8a' : ($key === 'school' ? '#0f766e' : '#475569') }};">

                        <!-- Header -->
                        <table style="width: 100%; margin-bottom: 5px; border-collapse: collapse;">
                            <tr>
                                @if(!empty($instituteLogo))
                                    <td style="width: 45px; vertical-align: middle; padding-right: 6px;">
                                        <img src="{{ $instituteLogo }}" style="height: 38px; max-width: 45px; object-fit: contain;">
                                    </td>
                                @endif
                                <td style="vertical-align: middle; text-align: {{ !empty($instituteLogo) ? 'left' : 'center' }};">
                                    <h2 class="school-name" style="margin: 0; line-height: 1.1;">{{ $instituteName }}</h2>
                                    @if($instituteAddress)
                                        <p class="school-info" style="margin: 1px 0 0 0;">{{ $instituteAddress }}</p>
                                    @endif
                                    @if($institutePhone)
                                        <p class="school-info" style="margin: 2px 0 0 0; font-weight: 700; color: #475569; font-size: 7.5px;">Phone: {{ $institutePhone }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <!-- Copy Title Tag -->
                        <div class="copy-tag" style="margin-bottom: 6px; {{ $key === 'bank' ? 'background-color: #1e3a8a;' : ($key === 'school' ? 'background-color: #0f766e;' : 'background-color: #475569;') }}">
                            {{ $title }}
                        </div>

                        <!-- Challan Metadata Table -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 7.5px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px;">
                            <tr>
                                <td style="padding: 1.5px 0;">
                                    <span style="color: #64748b;">Challan No:</span>
                                    <strong style="color: #be123c; font-size: 8.5px;">{{ $invoiceNumber }}</strong>
                                </td>
                                <td style="text-align: right; padding: 1.5px 0;">
                                    <span style="color: #64748b;">Billing Month:</span>
                                    <strong style="color: #0f172a;">{{ \Carbon\Carbon::parse($record->period . '-01')->format('M Y') }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 1.5px 0;">
                                    <span style="color: #64748b;">Issue Date:</span>
                                    <strong style="color: #0f172a;">{{ $record->created_at->format('d-M-Y') }}</strong>
                                </td>
                                <td style="text-align: right; padding: 1.5px 0;">
                                    <span style="color: #e11d48; font-weight: bold;">Due Date:</span>
                                    <strong style="color: #be123c;">{{ $record->due_date->format('d-M-Y') }}</strong>
                                </td>
                            </tr>
                        </table>

                        <!-- Student Info Box with Left Border Accent -->
                        <div class="student-box" style="border-left: 2.5px solid {{ $key === 'bank' ? '#1e3a8a' : ($key === 'school' ? '#0f766e' : '#475569') }}; margin-bottom: 8px; padding: 5px 6px;">
                            <table class="student-table" style="width: 100%;">
                                <tr>
                                    <td style="width: 52%;">
                                        <span class="label" style="display: block;">Student Name</span>
                                        <span class="value" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">{{ $student->name }}</span>
                                    </td>
                                    <td style="width: 48%;">
                                        <span class="label" style="display: block;">Class / Roll No</span>
                                        <span class="value" style="display: block;">{{ $record->class->name }} / {{ $student->roll_no }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 52%; padding-top: 3px;">
                                        <span class="label" style="display: block;">Father Name</span>
                                        <span class="value" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">{{ $student->father_name }}</span>
                                    </td>
                                    <td style="width: 48%; padding-top: 3px;">
                                        <span class="label" style="display: block;">Admission No</span>
                                        <span class="value" style="display: block;">{{ $student->admission_no }}</span>
                                    </td>
                                </tr>
                            </table>
                            <!-- Paid stamp overlay -->
                            @if($record->status === 'paid')
                                <div class="stamp-paid" style="font-size: 12px; padding: 1px 4px; top: 4px; left: 10px;">PAID</div>
                            @endif
                        </div>

                        <!-- Fee Breakdown Table -->
                        <table class="items-table" style="margin-bottom: 6px;">
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
                                    <td style="font-size: 8px; color: #0f172a; padding: 3px 5px;">Total Payable:</td>
                                    <td class="amount-col" style="font-size: 8px; color: #b91c1c; padding: 3px 5px;">Rs. {{ number_format(($record->balance + $previousArrears), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Deposit Instructions -->
                        <div class="bank-details" style="border-left: 2.5px solid #d97706; margin-bottom: 10px; padding: 3px 5px; background: #fffbeb;">
                            <p class="bank-title" style="margin-bottom: 1px;">Payment Instructions</p>
                            <div style="margin-top: 1px; font-size: 6.5px; line-height: 1.25; color: #78350f;">
                                &bull; Deposit fee in HBL or Bank Alfalah.<br>
                                &bull; Verify details before depositing.<br>
                                &bull; Ensure stamp is placed after payment.
                            </div>
                        </div>

                        <!-- Signatures -->
                        <table class="sig-table" style="margin-top: 12px;">
                            <tr>
                                <td style="width: 30%;" class="sig-line">Depositor</td>
                                <td style="width: 5%;"></td>
                                <td style="width: 30%;" class="sig-line">Cashier / Bank</td>
                                <td style="width: 5%;"></td>
                                <td style="width: 30%;" class="sig-line">Authorized Sign</td>
                            </tr>
                        </table>
                    </div>
                </td>

                <!-- Divider between columns -->
                @if(!$loop->last)
                    <td class="divider">
                        <div class="divider-line"></div>
                    </td>
                @endif
            @endforeach
        </tr>
    </table>

</body>
</html>
