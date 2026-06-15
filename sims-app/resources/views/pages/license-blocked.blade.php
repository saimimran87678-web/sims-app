<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software License Required - SIMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px rgba(255, 255, 255, 0.1) solid;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center relative overflow-hidden px-4">
    <!-- Background Decorative Gradients -->
    <div class="absolute w-96 h-96 bg-red-600/10 rounded-full blur-[100px] -top-12 -left-12"></div>
    <div class="absolute w-96 h-96 bg-indigo-600/10 rounded-full blur-[100px] -bottom-12 -right-12"></div>

    <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl relative z-10 text-center space-y-6">
        <!-- Locked Icon -->
        <div class="mx-auto w-20 h-20 bg-red-500/10 rounded-2xl flex items-center justify-center border border-red-500/20 shadow-lg shadow-red-500/5 animate-pulse">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <!-- Heading -->
        <div class="space-y-2">
            <h1 class="text-3xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                Access Locked
            </h1>
            <p class="text-xs font-semibold text-red-400 uppercase tracking-widest">
                SIMS License Status
            </p>
        </div>

        <!-- Error Description -->
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5 text-sm text-slate-300 leading-relaxed shadow-inner">
            {{ $licenseStatus['message'] ?? 'Your SIMS software license is inactive, suspended, or expired. Please contact your vendor to activate features.' }}
        </div>

        <!-- Primary Action buttons -->
        <div class="flex flex-col space-y-3 pt-2">
            <a href="tel:{{ config('services.license.vendor_phone') }}" 
               class="w-full py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 active:from-red-700 active:to-red-800 text-white font-semibold rounded-2xl shadow-lg shadow-red-600/15 hover:shadow-red-600/25 transition-all duration-200 transform hover:-translate-y-0.5">
                📞 Contact Support ({{ config('services.license.vendor_phone') }})
            </a>
            
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('services.license.vendor_phone')) }}?text=Hello,%20our%20SIMS%20software%20license%20requires%20activation.%20School%20ID:%20{{ config('services.license.school_id') }}" 
               target="_blank"
               class="w-full py-3 bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-slate-200 border border-slate-800 font-semibold rounded-2xl transition-all duration-200">
                💬 WhatsApp Message
            </a>
            
            <a href="{{ route('dashboard') }}" 
               class="w-full py-2.5 block text-xs font-medium text-slate-500 hover:text-slate-300 transition-colors duration-200 text-center">
                🔄 Re-check license status
            </a>
        </div>

        <!-- Footer -->
        <div class="text-[10px] text-slate-600 pt-4 border-t border-slate-900">
            SIMS V2 • School ID: <span class="font-mono">{{ config('services.license.school_id') }}</span>
        </div>
    </div>
</body>
</html>
