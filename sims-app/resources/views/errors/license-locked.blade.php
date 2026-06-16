<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Blocked - Subscription Suspended</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Figtree', sans-serif; }
        @keyframes popIn {
            0% { transform: scale(0.92) translateY(16px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .animate-pop-in {
            animation: popIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        /* Matches the app's existing glass-card style exactly */
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37), 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body class="bg-transparent text-gray-800 min-h-screen flex items-center justify-center p-4 m-0 overflow-hidden">
    
    <div class="max-w-md w-full error-card p-8 rounded-3xl text-center space-y-6 animate-pop-in relative z-10">
        
        <!-- Close Button -->
        <button onclick="closeModal()" class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors z-20" title="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Animated Warning Icon -->
        <div class="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center border border-red-100 shadow-[0_0_24px_rgba(239,68,68,0.15)] relative">
            <div class="absolute inset-0 bg-red-200 rounded-full animate-ping opacity-30"></div>
            <svg class="w-10 h-10 text-red-500 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <!-- Headings -->
        <div class="space-y-2">
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Read-Only Mode</h1>
            <span class="text-[11px] font-black text-red-600 uppercase tracking-widest bg-red-50 inline-block px-3 py-1 rounded-full border border-red-100">
                Action Blocked
            </span>
        </div>

        <!-- Error Message Block -->
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-sm text-gray-600 leading-relaxed text-left space-y-2">
            <p class="font-bold text-gray-900 text-base">Database is locked.</p>
            <p>{{ $message ?? 'The system is currently in READ-ONLY mode. You cannot save, edit, or delete any data right now.' }}</p>
            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Please renew your license to restore full access.
            </p>
        </div>

        <!-- Actions -->
        <div class="pt-3 space-y-3">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('services.license.vendor_phone')) }}" 
               target="_blank"
               class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp Support
             </a>
            
            <button onclick="closeModal()" class="w-full py-3.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-all duration-300">
                Cancel / Go Back
            </button>
        </div>
    </div>

    <!-- Script to magically upgrade the Livewire Error Modal -->
    <script>
        function closeModal() {
            if (window !== window.parent) {
                // We are inside Livewire's iframe. The safest and most reliable way to close a Livewire error 
                // modal when an action is blocked is to simply refresh the parent page state.
                window.parent.location.reload();
            } else {
                // We are not in an iframe, just go back.
                window.history.back();
            }
        }

        // Magically override Livewire's solid white error canvas
        if (window !== window.parent) {
            try {
                const iframes = window.parent.document.querySelectorAll('iframe');
                iframes.forEach(iframe => {
                    if (iframe.contentWindow === window) {
                        // 1. Make the iframe completely transparent so it doesn't block the background
                        iframe.style.background = 'transparent';
                        iframe.style.backgroundColor = 'transparent';
                        
                        // 2. Grab the Livewire modal container (which is usually pure white)
                        const container = iframe.parentElement;
                        if (container) {
                            // Apply the gorgeous dark glass effect to the parent container!
                            container.style.background = 'rgba(15, 23, 42, 0.7)';
                            container.style.backdropFilter = 'blur(16px)';
                            container.style.WebkitBackdropFilter = 'blur(16px)';
                            
                            // 3. Remove any solid white backdrop divs Livewire might have added
                            Array.from(container.children).forEach(child => {
                                if (child !== iframe) {
                                    child.style.background = 'transparent';
                                    child.style.backgroundColor = 'transparent';
                                }
                            });
                        }
                    }
                });
            } catch (e) {
                // Ignore cross-origin issues
            }
        }
    </script>
</body>
</html>
