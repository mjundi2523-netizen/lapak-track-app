<x-guest-layout>
    <h2 class="text-xl font-bold text-[#18181b] mb-1">Masuk</h2>
    <p class="text-sm text-[#71717a] mb-5">Silakan masuk ke akun Anda.</p>

    @if (session('status'))
        <div class="alert alert-success text-sm mb-4">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <x-auth-input name="email" label="Email" type="email" icon="o-envelope"
            :value="old('email')" autofocus autocomplete="username" />

        <x-auth-input name="password" label="Password" type="password" icon="o-key"
            autocomplete="current-password" />

        <div class="flex items-center justify-between mb-5">
            <label class="flex items-center gap-2 text-sm text-[#3f3f46] cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4" style="accent-color:var(--lt-p);" />
                Ingat saya
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-[var(--lt-p)] hover:underline">Lupa password?</a>
            @endif
        </div>

        <button type="submit"
            class="w-full h-11 bg-[var(--lt-p)] text-white rounded-[10px] text-[15px] font-semibold hover:brightness-95 transition">
            Masuk
        </button>

        <p class="text-center text-sm text-[#71717a] mt-[18px]">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-[var(--lt-p)] font-medium hover:underline">Daftar</a>
        </p>
    </form>
</x-guest-layout>
