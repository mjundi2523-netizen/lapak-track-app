<x-guest-layout>
    <h2 class="text-xl font-bold text-[#18181b] mb-1">Daftar</h2>
    <p class="text-sm text-[#71717a] mb-5">Buat akun baru.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <x-auth-input name="name" label="Nama" icon="o-user"
            :value="old('name')" autofocus autocomplete="name" />

        <x-auth-input name="email" label="Email" type="email" icon="o-envelope"
            :value="old('email')" autocomplete="username" />

        <x-auth-input name="password" label="Password" type="password" icon="o-key"
            autocomplete="new-password" />

        <x-auth-input name="password_confirmation" label="Konfirmasi Password" type="password" icon="o-key"
            autocomplete="new-password" />

        <button type="submit"
            class="w-full h-11 mt-1 bg-[var(--lt-p)] text-white rounded-[10px] text-[15px] font-semibold hover:brightness-95 transition">
            Daftar
        </button>

        <p class="text-center text-sm text-[#71717a] mt-[18px]">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-[var(--lt-p)] font-medium hover:underline">Masuk</a>
        </p>
    </form>
</x-guest-layout>
