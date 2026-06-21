<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Invoice #{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</title>
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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #1e3a8a;
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
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 15px;
            margin: 5px;
        }
        .box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .box p {
            margin: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #1e3a8a;
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
        .items-table .amount {
            text-align: right;
            font-weight: bold;
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
            color: #b91c1c;
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
            border: 4px solid #16a34a;
            color: #16a34a;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 20px;
            transform: rotate(-15deg);
            opacity: 0.8;
            border-radius: 8px;
            display: {{ $record->status === 'paid' ? 'block' : 'none' }};
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-unpaid { background: #fee2e2; color: #b91c1c; }
        .status-partial { background: #fef3c7; color: #d97706; }
        .status-paid { background: #dcfce3; color: #15803d; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $instituteName }}</h1>
        <p>{{ $instituteAddress }} | {{ $institutePhone }} | {{ $instituteEmail }}</p>
    </div>

    @if($record->status === 'paid')
        <div class="stamp">PAID</div>
    @endif

    <table class="info-grid">
        <tr>
            <td>
                <div class="box">
                    <h3>Student Details</h3>
                    <p><strong>Name:</strong> {{ $student->name }}</p>
                    <p><strong>Admission No:</strong> {{ $student->admission_no }}</p>
                    <p><strong>Class:</strong> {{ $record->class->name }}</p>
                    <p><strong>Phone:</strong> {{ $student->phone ?? 'N/A' }}</p>
                </div>
            </td>
            <td>
                <div class="box">
                        <h2 style="margin: 0; color: #1e293b; font-size: 24px;">INVOICE</h2>
                        <p style="margin: 4px 0 0 0; color: #64748b;">#{{ $invoiceNumber ?? 'INV-'.str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p style="margin: 4px 0 0 0; color: #64748b;">Period: {{ \Carbon\Carbon::parse($record->period . '-01')->format('F Y') }}</p>
                    <p><strong>Due Date:</strong> {{ $record->due_date->format('d M, Y') }}</p>
                    <p>
                        <strong>Status:</strong> 
                        <span class="status-badge status-{{ $record->status }}">
                            {{ $record->status }}
                        </span>
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $item)
                <tr>
                    <td>{{ $item->description ?: $item->fee_head_name }}</td>
                    <td class="amount">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
            
            @php
                $previousArrears = \App\Models\FeeRecord::where('student_id', $student->id)
                    ->where('status', '!=', 'paid')
                    ->where('period', '<', $record->period)
                    ->sum('balance');
            @endphp

            @if($previousArrears > 0)
            <tr>
                <td><strong>Previous Arrears</strong></td>
                <td style="text-align: right;"><strong>Rs. {{ number_format($previousArrears, 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td>Current Month Total</td>
                <td style="text-align: right;">Rs. {{ number_format($record->total_amount, 2) }}</td>
            </tr>
            @if($previousArrears > 0)
            <tr>
                <td><strong>Total Payable (incl. Arrears)</strong></td>
                <td style="text-align: right;"><strong>Rs. {{ number_format($record->total_amount + $previousArrears, 2) }}</strong></td>
            </tr>
            @endif
        </tfoot>
    </table>

    <div class="totals">
        <table class="totals-table">
            @if($record->paid_amount > 0)
            <tr>
                <td class="label">Amount Paid:</td>
                <td class="value" style="color: #16a34a;">- Rs. {{ number_format($record->paid_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="label" style="font-size: 16px; color: #b91c1c;">Balance Due:</td>
                <td class="value grand-total">Rs. {{ number_format($record->balance + ($previousArrears ?? 0), 2) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="clear"></div>

    <div class="footer">
        <p>This is a computer generated invoice and requires no signature.</p>
        <p>Please pay before the due date to avoid late fees.</p>
    </div>

</body>
</html>
