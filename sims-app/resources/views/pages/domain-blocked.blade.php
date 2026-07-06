<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Authorization Required - SIMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a; /* slate-900 */
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        @keyframes orbit {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-orbit {
            animation: orbit 20s linear infinite;
        }
    </style>
</head>
<body class="text-slate-100 min-h-screen flex items-center justify-center relative overflow-hidden px-4">
    <!-- Background Decorative Gradients -->
    <div class="absolute w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[120px] -top-24 -left-24 z-0"></div>
    <div class="absolute w-[500px] h-[500px] bg-violet-500/10 rounded-full blur-[120px] -bottom-24 -right-24 z-0"></div>

    <div class="max-w-md w-full glass-card p-8 rounded-3xl shadow-2xl relative z-10 text-center space-y-6">
        <!-- Globe Lock Icon -->
        <div class="mx-auto w-20 h-20 bg-indigo-500/10 rounded-2xl flex items-center justify-center border border-indigo-500/20 text-indigo-400 relative overflow-hidden">
            <svg class="w-10 h-10 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
            <div class="absolute inset-0 border border-dashed border-indigo-500/30 rounded-full scale-[0.8] animate-orbit"></div>
        </div>

        <!-- Heading -->
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                Domain Blocked
            </h1>
            <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest">
                SIMS License Verification
            </p>
        </div>

        <!-- Error Description -->
        <div class="bg-indigo-950/40 border border-indigo-500/20 rounded-2xl p-5 text-sm text-slate-300 leading-relaxed shadow-inner">
            This domain (<span class="font-mono text-indigo-300 font-semibold">{{ request()->getHost() }}</span>) is not authorized to host this SIMS license. Please configure the allowed domains list in your admin panel and re-activate.
        </div>

        <!-- Support Info -->
        <div class="flex flex-col space-y-3 pt-2">
            <a href="tel:{{ config('services.license.vendor_phone') }}" 
               class="w-full py-3 bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 font-semibold rounded-2xl transition-all duration-200">
                📞 Contact Support ({{ config('services.license.vendor_phone') }})
            </a>
            
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('services.license.vendor_phone')) }}?text=Hello,%20our%20SIMS%20software%20license%20requires%20domain%20authorization.%20School%20ID:%20{{ config('services.license.school_id') }}%20Domain:%20{{ request()->getHost() }}" 
               target="_blank"
               class="w-full py-3 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-semibold rounded-2xl shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                WhatsApp Support
            </a>
        </div>

        <!-- Footer -->
        <div class="text-[11px] text-slate-500 pt-4 border-t border-slate-800 font-medium">
            Powered by Adminova • School ID: <span class="font-mono text-slate-400">{{ config('services.license.school_id') }}</span>
        </div>
    </div>
</body>
</html>
