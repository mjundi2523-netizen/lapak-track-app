<x-guest-layout>
    <h2 class="text-xl font-bold mb-2">Verifikasi Email</h2>
    <p class="text-sm text-base-content/60 mb-4">
        Terima kasih telah mendaftar! Sebelum mulai, mohon verifikasi email Anda lewat tautan
        yang baru saja kami kirim. Jika belum menerima, kami bisa mengirim ulang.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success text-sm mb-4">
            Tautan verifikasi baru telah dikirim ke email Anda.
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button label="Kirim Ulang Email" type="submit" class="btn-primary" />
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button label="Keluar" type="submit" class="btn-ghost" />
        </form>
    </div>
</x-guest-layout>
