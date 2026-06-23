<x-guest-layout>
    <h2 class="text-xl font-bold mb-2">Konfirmasi Password</h2>
    <p class="text-sm text-base-content/60 mb-4">
        Ini area aman aplikasi. Mohon konfirmasi password Anda sebelum melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <x-input label="Password" name="password" type="password" icon="o-key" required autofocus />

        <x-button label="Konfirmasi" type="submit" class="btn-primary w-full" />
    </form>
</x-guest-layout>
