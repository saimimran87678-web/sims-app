<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #059669;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #065f46;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-grid td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 15px;
            margin: 5px;
        }
        .box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: #065f46;
            border-bottom: 1px solid #bbf7d0;
            padding-bottom: 5px;
        }
        .box p {
            margin: 5px 0;
        }
        .receipt-title {
            color: #059669;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #059669;
            color: white;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals {
            width: 50%;
            float: right;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #64748b;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .totals-table .grand-total {
            font-size: 18px;
            color: #059669;
            border-bottom: none;
        }
        .clear {
            clear: both;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .stamp {
            position: absolute;
            bottom: 150px;
            right: 50px;
            border: 4px solid #059669;
            color: #059669;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 20px;
            transform: rotate(-15deg);
            opacity: 0.8;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $instituteName }}</h1>
        <p>{{ $instituteAddress }} | {{ $institutePhone }} | {{ $instituteEmail }}</p>
    </div>

    <div class="stamp">RECEIVED</div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="box">
                    <h3>Received From</h3>
                    <p><strong>Student:</strong> {{ $student->name }}</p>
                    <p><strong>Admission No:</strong> {{ $student->admission_no }}</p>
                    <p><strong>Class:</strong> {{ $record->class->name }}</p>
                    <p><strong>Phone:</strong> {{ $student->phone ?? 'N/A' }}</p>
                </div>
            </td>
            <td>
                <div class="box" style="background-color: #f8fafc; border-color: #e2e8f0;">
                    <h2 class="receipt-title">RECEIPT</h2>
                    <p style="margin: 4px 0 0 0; color: #64748b;">#REC-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</p>
                    <p style="margin: 4px 0 0 0; color: #64748b;">Payment Date: {{ $payment->payment_date->format('d M, Y') }}</p>
                    <p><strong>Payment Method:</strong> {{ strtoupper($payment->payment_method) }}</p>
                    @if($payment->notes)
                        <p><strong>Reference:</strong> {{ $payment->notes }}</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right">Amount Details (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Fee Voucher for {{ \Carbon\Carbon::parse($record->period . '-01')->format('F Y') }}</td>
                <td style="text-align: right;">Rs. {{ number_format($record->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Amount Paid</strong></td>
                <td style="text-align: right; color: #059669; font-weight: bold;">- Rs. {{ number_format($payment->amount_paid, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">Total Paid in this Transaction:</td>
                <td class="value grand-total">Rs. {{ number_format($payment->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label" style="font-size: 14px; color: #b91c1c;">Remaining Balance Due:</td>
                <td class="value" style="font-size: 14px; color: #b91c1c;">Rs. {{ number_format($record->balance, 2) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="clear"></div>

    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>This is a computer generated receipt and requires no signature.</p>
    </div>

</body>
</html>
