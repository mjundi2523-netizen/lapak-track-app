<x-guest-layout>
    <h2 class="text-xl font-bold mb-1">Masuk</h2>
    <p class="text-sm text-base-content/60 mb-4">Silakan masuk ke akun Anda.</p>

    @if (session('status'))
        <div class="alert alert-success text-sm mb-4">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-input label="Email" name="email" type="email" value="{{ old('email') }}"
            icon="o-envelope" required autofocus />

        <x-input label="Password" name="password" type="password" icon="o-key" required />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                Ingat saya
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm link link-primary">Lupa password?</a>
            @endif
        </div>

        <x-button label="Masuk" type="submit" class="btn-primary w-full" />

        <p class="text-center text-sm text-base-content/60">
            Belum punya akun?
            <a href="{{ route('register') }}" class="link link-primary">Daftar</a>
        </p>
    </form>
</x-guest-layout>
