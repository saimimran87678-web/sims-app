<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Link Expired</title>
    <link rel="stylesheet" href="/fonts/inter.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-gray-100 to-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden text-center p-8">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Voucher Link Expired</h1>
        <p class="text-gray-500 text-sm leading-relaxed mb-6">
            For data security and privacy, public fee voucher links expire automatically after 30 days. This link is no longer active.
        </p>

        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-left text-xs space-y-2 mb-6">
            <div class="flex justify-between">
                <span class="text-gray-500">Student:</span>
                <span class="font-semibold text-gray-800">{{ $record->student?->name ?? 'Enrolled Student' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Billing Period:</span>
                <span class="font-semibold text-gray-800">{{ $record->period }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Expired On:</span>
                <span class="font-semibold text-amber-700">{{ $record->access_token_expires_at?->format('d M, Y h:i A') ?? 'Expired' }}</span>
            </div>
        </div>

        <div class="space-y-3">
            <a href="{{ route('portal') }}" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Access Student & Parent Portal
            </a>
            <p class="text-[11px] text-gray-400">
                You can also contact the school administration office to request an updated voucher copy.
            </p>
        </div>
    </div>
</body>
</html>
