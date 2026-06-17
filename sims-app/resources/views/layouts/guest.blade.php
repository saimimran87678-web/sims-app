<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Login' }} | {{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <style>
            [x-cloak] { display: none !important; }
            * { font-family: 'Inter', sans-serif; }
            
            .login-bg {
                background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #38b2ac 100%);
                min-height: 100vh;
                position: relative;
                overflow: hidden;
            }
            
            .login-bg::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 100%;
                height: 100%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
                animation: float 15s ease-in-out infinite;
            }
            
            .login-bg::after {
                content: '';
                position: absolute;
                bottom: -30%;
                left: -30%;
                width: 80%;
                height: 80%;
                background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 50%);
                animation: float 20s ease-in-out infinite reverse;
            }
            
            @keyframes float {
                0%, 100% { transform: translate(0, 0) rotate(0deg); }
                50% { transform: translate(30px, 30px) rotate(5deg); }
            }
            
            .glass-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                position: relative;
                z-index: 10;
            }
            
            .input-modern {
                width: 100%;
                padding: 14px 16px;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                font-size: 15px;
                transition: all 0.3s ease;
                background: #f8fafc;
            }
            
            .input-modern:focus {
                outline: none;
                border-color: #3182ce;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
            }
            
            .btn-login {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px -5px rgba(30, 58, 95, 0.4);
            }
            
            .btn-login:active {
                transform: translateY(0);
            }
            
            .school-logo {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
                border-radius: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                box-shadow: 0 10px 30px -10px rgba(30, 58, 95, 0.5);
            }
            
            .checkbox-modern {
                width: 18px;
                height: 18px;
                border-radius: 5px;
                border: 2px solid #cbd5e0;
                cursor: pointer;
            }
            
            .checkbox-modern:checked {
                background: #3182ce;
                border-color: #3182ce;
            }
            
            .link-modern {
                color: #3182ce;
                font-weight: 500;
                text-decoration: none;
                transition: color 0.2s;
            }
            
            .link-modern:hover {
                color: #2c5282;
            }
            
            .floating-shapes {
                position: absolute;
                width: 100%;
                height: 100%;
                overflow: hidden;
                pointer-events: none;
            }
            
            .shape {
                position: absolute;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 50%;
            }
            
            .shape-1 { width: 300px; height: 300px; top: 10%; left: -5%; }
            .shape-2 { width: 200px; height: 200px; top: 60%; right: 5%; }
            .shape-3 { width: 150px; height: 150px; bottom: 10%; left: 10%; }
        </style>
    </head>
    <body class="antialiased">
        <div class="login-bg">
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
            
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
