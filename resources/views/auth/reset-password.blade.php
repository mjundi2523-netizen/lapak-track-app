<x-guest-layout>
    <h2 class="text-xl font-bold mb-4">Atur Ulang Password</h2>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-input label="Email" name="email" type="email"
            value="{{ old('email', $request->email) }}" icon="o-envelope" required autofocus />

        <x-input label="Password Baru" name="password" type="password" icon="o-key" required />

        <x-input label="Konfirmasi Password" name="password_confirmation" type="password"
            icon="o-key" required />

        <x-button label="Atur Ulang Password" type="submit" class="btn-primary w-full" />
    </form>
</x-guest-layout>
