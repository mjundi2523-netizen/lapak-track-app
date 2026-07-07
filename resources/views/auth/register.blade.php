<x-guest-layout>
    <h2 class="text-xl font-bold text-[#18181b] mb-1">Daftar</h2>
    <p class="text-sm text-[#71717a] mb-5">Buat akun baru.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <x-auth-input name="name" label="Nama" icon="o-user"
            :value="old('name')" autofocus autocomplete="name" />

        <x-auth-input name="email" label="Email" type="email" icon="o-envelope"
            :value="old('email')" autocomplete="username" />

        {{-- Market (tenant) — disediakan developer via DB --}}
        <div class="mb-4">
            <label for="market_id" class="block text-sm font-medium text-[#3f3f46] mb-1.5">Pasar / Perusahaan</label>
            <select name="market_id" id="market_id" required
                class="w-full h-11 px-3 rounded-[10px] border border-[#e4e4e7] bg-white text-[15px] text-[#18181b] focus:outline-none focus:border-[var(--lt-p)] focus:ring-1 focus:ring-[var(--lt-p)]">
                <option value="" disabled {{ old('market_id') ? '' : 'selected' }}>Pilih pasar</option>
                @foreach($markets as $market)
                    <option value="{{ $market->mid }}" {{ (string) old('market_id') === (string) $market->mid ? 'selected' : '' }}>{{ $market->name }}</option>
                @endforeach
            </select>
            @error('market_id')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
            @if($markets->isEmpty())
                <p class="text-xs text-amber-600 mt-1">Belum ada pasar terdaftar. Hubungi admin untuk pendaftaran pasar.</p>
            @endif
        </div>

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
