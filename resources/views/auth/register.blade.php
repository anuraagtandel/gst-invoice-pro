<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-user text-indigo-500 text-lg"></i>
                </div>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" 
                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="Enter your full name">
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-envelope-simple text-indigo-500 text-lg"></i>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" 
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
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="block w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="Create a password">
                <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="ph ph-eye text-lg"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
            <div data-password-field class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="ph ph-lock-key text-indigo-500 text-lg"></i>
                </div>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="block w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-[#5174ff] focus:border-[#5174ff] outline-none transition-all placeholder:text-slate-400"
                    placeholder="Confirm your password">
                <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="ph ph-eye text-lg"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-[#6b8cff] hover:bg-[#5174ff] text-white font-medium py-3 rounded-xl transition-colors shadow-[0_8px_20px_rgba(107,140,255,0.3)]">
                Register
            </button>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-slate-500">
                Already registered? 
                <a href="{{ route('login') }}" class="text-[#5174ff] hover:text-[#3a58e0] font-medium transition-colors">Login here</a>
            </p>
        </div>
    </form>
</x-guest-layout>
