<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Voucher - {{ $student->name }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .stamp-container {
            position: absolute;
            top: 20px;
            right: 20px;
            pointer-events: none;
            transform: rotate(-10deg);
            z-index: 10;
        }
        @media (max-width: 640px) {
            .stamp-container {
                position: relative;
                top: 0;
                right: 0;
                margin: 15px auto;
                transform: rotate(-5deg);
                display: flex;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="py-6 px-4 sm:py-12 sm:px-6 lg:px-8 text-slate-800">

    <div class="max-w-3xl mx-auto space-y-6">
        
        <!-- Header Info Bar / Quick Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/60 backdrop-blur-md p-4 rounded-2xl border border-white/40 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Official Digital Portal</span>
            </div>
            
            <a href="{{ route('public.voucher.pdf', $token) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition-all shadow-sm shadow-blue-500/10 hover:shadow-blue-500/20 active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download PDF Voucher
            </a>
        </div>

        <!-- Main Invoice Card -->
        <div class="relative glass-card rounded-3xl shadow-xl overflow-hidden p-6 sm:p-8">
            
            <!-- STAMP OVERLAY (Only visible if paid) -->
            @if($record->status === 'paid')
                <div class="stamp-container">
                    <svg width="150" height="150" viewBox="0 0 150 150" class="opacity-90 select-none">
                        <!-- Double circle border -->
                        <circle cx="75" cy="75" r="70" fill="none" stroke="#10b981" stroke-width="3" stroke-dasharray="none" />
                        <circle cx="75" cy="75" r="63" fill="none" stroke="#10b981" stroke-width="1.5" />
                        
                        <defs>
                            <!-- Circle text path (rotated to start text at a natural position) -->
                            <path id="textCircle" d="M 75, 75 m -52, 0 a 52,52 0 1,1 104,0 a 52,52 0 1,1 -104,0" />
                        </defs>
                        
                        <text font-size="8.5" font-weight="900" fill="#10b981" letter-spacing="1">
                            <textPath href="#textCircle" startOffset="50%" text-anchor="middle">
                                ★ {{ strtoupper($instituteName) }} ★
                            </textPath>
                        </text>
                        
                        <!-- Center status and dates -->
                        <text x="75" y="73" font-size="22" font-weight="900" fill="#10b981" text-anchor="middle" letter-spacing="0.5">PAID</text>
                        
                        <text x="75" y="93" font-size="8" font-weight="700" fill="#10b981" text-anchor="middle">
                            {{ $record->paid_date ? $record->paid_date->format('d-M-Y') : now()->format('d-M-Y') }}
                        </text>
                        
                        <path d="M 50 100 L 100 100" stroke="#10b981" stroke-width="1.2" stroke-dasharray="3,3" />
                    </svg>
                </div>
            @endif

            <!-- School Details & Branding -->
            <div class="border-b border-slate-200/60 pb-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">{{ $instituteName }}</h1>
                        <p class="text-xs text-slate-500 mt-1 max-w-md leading-relaxed">{{ $instituteAddress }}</p>
                        @if($institutePhone)
                            <p class="text-xs font-semibold text-slate-600 mt-1">Ph: {{ $institutePhone }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col sm:items-end">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Fee Challan</span>
                        <span class="text-lg font-bold text-red-600 mt-0.5">{{ $invoiceNumber }}</span>
                    </div>
                </div>
            </div>

            <!-- Student Profile Information -->
            <div class="bg-slate-50/50 rounded-2xl border border-slate-100 p-4 sm:p-5 mb-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Student Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between sm:justify-start gap-2 border-b sm:border-0 border-slate-100 pb-2 sm:pb-0">
                        <span class="text-slate-500 w-32">Student Name:</span>
                        <span class="font-bold text-slate-800">{{ $student->name }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-2 border-b sm:border-0 border-slate-100 pb-2 sm:pb-0">
                        <span class="text-slate-500 w-32">Father's Name:</span>
                        <span class="font-bold text-slate-800">{{ $student->father_name }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-2 border-b sm:border-0 border-slate-100 pb-2 sm:pb-0">
                        <span class="text-slate-500 w-32">Class & Section:</span>
                        <span class="font-bold text-slate-800">{{ $class->name }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-2">
                        <span class="text-slate-500 w-32">Roll / Admission No:</span>
                        <span class="font-bold text-slate-800">{{ $student->roll_no }} / {{ $student->admission_no }}</span>
                    </div>
                </div>
            </div>

            <!-- Challan Dates & Quick Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 text-center sm:text-left">
                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100/50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Billing Period</div>
                    <div class="text-sm font-bold text-slate-700 mt-1">{{ \Carbon\Carbon::parse($record->period . '-01')->format('M Y') }}</div>
                </div>
                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100/50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Issue Date</div>
                    <div class="text-sm font-bold text-slate-700 mt-1">{{ $record->created_at->format('d M, Y') }}</div>
                </div>
                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100/50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Due Date</div>
                    <div class="text-sm font-bold text-red-600 mt-1">{{ $record->due_date->format('d M, Y') }}</div>
                </div>
                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100/50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Status</div>
                    <div class="mt-1 flex justify-center sm:justify-start">
                        @if($record->status === 'paid')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Paid</span>
                        @elseif($record->status === 'partial')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Partial</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">Unpaid</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Fee Heads Breakdown -->
            <div class="mb-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Itemized Particulars</h3>
                <div class="overflow-hidden border border-slate-150 rounded-2xl">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-150">
                                <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-xs">Fee Particulars</th>
                                <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-right">Amount (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 bg-white/40">
                            @foreach($record->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-slate-700 font-medium">{{ $item->fee_head_name }}</td>
                                    <td class="px-4 py-3 text-slate-900 font-semibold text-right">Rs. {{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            
                            @if($previousArrears > 0)
                                <tr class="bg-amber-50/30">
                                    <td class="px-4 py-3 text-amber-800 font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Outstanding Arrears
                                    </td>
                                    <td class="px-4 py-3 text-amber-700 font-extrabold text-right">Rs. {{ number_format($previousArrears, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50/70 border-t border-slate-200">
                                <td class="px-4 py-4 font-bold text-slate-600">Total Balance Payable:</td>
                                <td class="px-4 py-4 font-extrabold text-slate-900 text-right text-base">
                                    Rs. {{ number_format(($record->balance + $previousArrears), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Payment Transaction Details -->
            @if($record->status === 'paid' && $record->payments->isNotEmpty())
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-4 sm:p-5 mb-6">
                    <h3 class="text-xs font-bold text-emerald-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Transaction Receipt Summary
                    </h3>
                    <div class="space-y-2.5 text-sm">
                        @foreach($record->payments as $payment)
                            <div class="flex justify-between items-center text-slate-700 border-b border-emerald-100/50 pb-2 last:border-0 last:pb-0">
                                <div>
                                    <span class="font-bold text-emerald-700">Rs. {{ number_format($payment->amount_paid, 2) }}</span>
                                    <span class="text-xs text-slate-500 ml-1">via {{ ucfirst($payment->payment_method) }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-500">Paid on: {{ $payment->payment_date->format('d M Y, h:i A') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Payment Instructions -->
            <div class="bg-amber-50/40 rounded-2xl border border-amber-100 p-4 sm:p-5 text-xs text-amber-900 leading-relaxed">
                <h4 class="font-bold text-amber-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Deposit Guidelines
                </h4>
                <ul class="list-disc pl-4 space-y-1.5">
                    <li>Deposit the total outstanding dues in <strong>HBL</strong> (A/C: <span class="font-bold">1234-567890-01</span>) or <strong>Bank Alfalah</strong> (A/C: <span class="font-bold">9876-543210-02</span>).</li>
                    <li>Always mention the invoice number <strong class="text-red-700 font-bold">{{ $invoiceNumber }}</strong> in the transaction reference/receipt note.</li>
                    <li>Once payment is made, this portal will auto-update with your digital receipt within 24 hours of bank clearance.</li>
                </ul>
            </div>

        </div>

        <!-- Footer Branding -->
        <div class="text-center text-xs text-slate-400">
            Powered by {{ $instituteName }} Student Information Management System &copy; {{ date('Y') }}
        </div>

    </div>

</body>
</html>
