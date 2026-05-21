<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-envelope-simple text-indigo-500 text-lg"></i>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="Enter your email address">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
            <div data-password-field class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-lock-key text-indigo-500 text-lg"></i>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="block w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="***************">
                <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="ph ph-eye text-lg"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember & Forgot -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="flex items-center cursor-pointer group">
                <div class="relative flex items-center">
                    <input id="remember_me" type="checkbox" class="peer sr-only" name="remember">
                    <div class="w-4 h-4 border-2 border-slate-300 rounded peer-checked:bg-[#5174ff] peer-checked:border-[#5174ff] transition-all flex items-center justify-center">
                        <i class="ph ph-check text-white text-[10px] opacity-0 peer-checked:opacity-100"></i>
                    </div>
                </div>
                <span class="ms-2 text-sm text-slate-600 group-hover:text-slate-800 transition-colors">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-[#5174ff] hover:text-[#3a58e0] font-medium transition-colors" href="{{ route('password.request') }}">
                    Reset Password!
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-[#6b8cff] hover:bg-[#5174ff] text-white font-medium py-3 rounded-xl transition-colors shadow-[0_8px_20px_rgba(107,140,255,0.3)]">
                Login
            </button>
        </div>
    </form>
</x-guest-layout>
