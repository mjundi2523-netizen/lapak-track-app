<x-guest-layout>
    <h2 class="text-xl font-bold mb-1">Lupa Password</h2>
    <p class="text-sm text-base-content/60 mb-4">
        Masukkan email Anda dan kami akan mengirim tautan untuk mengatur ulang password.
    </p>

    @if (session('status'))
        <div class="alert alert-success text-sm mb-4">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-input label="Email" name="email" type="email" value="{{ old('email') }}"
            icon="o-envelope" required autofocus />

        <x-button label="Kirim Tautan Reset" type="submit" class="btn-primary w-full" />

        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="link link-primary">Kembali ke Masuk</a>
        </p>
    </form>
</x-guest-layout>
