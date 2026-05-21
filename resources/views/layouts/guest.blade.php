<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sagardutt') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Syne:wght@400..800&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/@phosphor-icons/web"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                font-family: 'DM Sans', sans-serif;
            }
            .font-syne {
                font-family: 'Syne', sans-serif;
            }
        </style>
    </head>
    <body class="font-sans text-slate-800 antialiased bg-[#eef2f6] min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
        
        <!-- Large background circles for that modern look -->
        <div class="absolute -top-[20%] -right-[10%] w-[50%] h-[60%] rounded-full bg-[#e2e8f0] opacity-50 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[50%] h-[60%] rounded-full bg-[#e2e8f0] opacity-50 blur-3xl pointer-events-none"></div>

        <div class="max-w-[1000px] w-full bg-white rounded-[2rem] shadow-2xl flex overflow-hidden min-h-[600px] relative z-10">
            
            <!-- Left Side (Blue Banner) -->
            <div class="hidden md:flex md:w-[45%] bg-[#5174ff] relative p-12 flex-col justify-center overflow-hidden">
                <!-- Abstract Background Elements -->
                <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <!-- Dots top left -->
                    <div class="absolute top-10 left-10 grid grid-cols-4 gap-2 opacity-40">
                        @for($i=0; $i<16; $i++) <div class="w-1.5 h-1.5 rounded-full bg-white"></div> @endfor
                    </div>
                    <!-- Circles top right -->
                    <div class="absolute top-12 right-12 w-12 h-12 rounded-full border border-white/30"></div>
                    <div class="absolute top-14 right-16 w-3 h-3 rounded-full bg-white"></div>
                    
                    <!-- Bottom left shapes -->
                    <div class="absolute bottom-16 left-12 w-6 h-6 rounded-full bg-[#00f2fe] shadow-[0_0_15px_rgba(0,242,254,0.6)]"></div>
                    <div class="absolute bottom-8 left-8 grid grid-cols-4 gap-2 opacity-40">
                        @for($i=0; $i<16; $i++) <div class="w-1.5 h-1.5 rounded-full bg-white"></div> @endfor
                    </div>
                    
                    <!-- Bottom right large circle -->
                    <div class="absolute -bottom-24 -right-10 w-80 h-80 rounded-full border-[1.5rem] border-white/10"></div>
                    <div class="absolute -bottom-10 right-0 w-56 h-56 rounded-full bg-gradient-to-tr from-[#00f2fe] to-[#4facfe] shadow-2xl"></div>
                    <div class="absolute bottom-32 right-16 w-4 h-4 rounded-full bg-[#00f2fe]"></div>
                    
                    <!-- Pill shapes top left -->
                    <div class="absolute -top-10 left-32 w-16 h-40 rounded-full bg-white/10 transform -rotate-12"></div>
                    <div class="absolute top-0 left-52 w-8 h-24 rounded-full bg-white/20 transform -rotate-12"></div>
                    
                    <div class="absolute bottom-12 left-24 text-white/50 text-2xl font-bold">×</div>
                </div>

                <!-- Content -->
                <div class="relative z-10 text-white">
                    <h1 class="font-syne font-bold text-[40px] leading-tight mb-4">
                        Sagardutt<br>Pepsi Distributors
                    </h1>
                    <p class="text-white/90 text-sm leading-relaxed">
                        Complete GST Billing, Inventory &amp; Sales Management for Distributors
                    </p>
                </div>
            </div>

            <!-- Right Side (Form) -->
            <div class="w-full md:w-[55%] p-8 sm:p-12 lg:px-16 lg:py-12 flex flex-col justify-center bg-white relative">
                
                <div class="flex flex-col items-center mb-8">
                    <!-- Logo -->
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] flex items-center justify-center mb-4 border border-slate-50">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#00c6ff] to-[#0072ff] rounded-xl flex items-center justify-center text-white font-syne font-bold text-2xl relative overflow-hidden">
                            <span class="relative z-10">a</span>
                            <div class="absolute w-[150%] h-[150%] bg-white rounded-full -left-[50%] -top-[50%] opacity-20"></div>
                        </div>
                    </div>
                    <h2 class="font-syne text-xl font-semibold text-slate-800 tracking-wide">{{ Route::is('login') ? 'Hello ! Welcome back' : (Route::is('register') ? 'Create an account' : 'Welcome') }}</h2>
                </div>

                {{ $slot }}
                
            </div>
        </div>
        
        <!-- Bottom right dots for outer background -->
        <div class="absolute bottom-8 right-8 grid grid-cols-4 gap-2 opacity-20 pointer-events-none hidden lg:grid">
            @for($i=0; $i<16; $i++) <div class="w-1.5 h-1.5 rounded-full bg-slate-500"></div> @endfor
        </div>
        <script>
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-password-toggle]');
                if (!btn) return;
                const container = btn.closest('[data-password-field]');
                if (!container) return;
                const input = container.querySelector('input');
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('ph-eye', 'ph-eye-slash');
                    icon.classList.add(show ? 'ph-eye-slash' : 'ph-eye');
                }
            });
        </script>
    </body>
</html>
