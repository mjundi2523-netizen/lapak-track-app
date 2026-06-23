<x-guest-layout>
    <h2 class="text-xl font-bold mb-1">Daftar</h2>
    <p class="text-sm text-base-content/60 mb-4">Buat akun baru.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-input label="Nama" name="name" value="{{ old('name') }}" icon="o-user" required autofocus />

        <x-input label="Email" name="email" type="email" value="{{ old('email') }}"
            icon="o-envelope" required />

        <x-input label="Password" name="password" type="password" icon="o-key" required />

        <x-input label="Konfirmasi Password" name="password_confirmation" type="password"
            icon="o-key" required />

        <x-button label="Daftar" type="submit" class="btn-primary w-full" />

        <p class="text-center text-sm text-base-content/60">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="link link-primary">Masuk</a>
        </p>
    </form>
</x-guest-layout>
