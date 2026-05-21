<x-guest-layout>
    <div class="mb-6 text-sm text-slate-600 leading-relaxed">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-envelope-simple text-indigo-500 text-lg"></i>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus 
                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="Enter your email address">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-[#6b8cff] hover:bg-[#5174ff] text-white font-medium py-3 rounded-xl transition-colors shadow-[0_8px_20px_rgba(107,140,255,0.3)]">
                Email Password Reset Link
            </button>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-slate-500">
                Remember your password? 
                <a href="{{ route('login') }}" class="text-[#5174ff] hover:text-[#3a58e0] font-medium transition-colors">Back to Login</a>
            </p>
        </div>
    </form>
</x-guest-layout>