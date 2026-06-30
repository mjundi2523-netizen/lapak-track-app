@php
    $waNumber = preg_replace('/\D/', '', (string) config('lapak.developer_whatsapp'));
    $waLink = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode((string) config('lapak.premium_wa_message'));
    $autoOpen = session('premium_required') ? 'true' : 'false';
@endphp

<div x-data="{ open: {{ $autoOpen }} }"
     x-on:premium-required.window="open = true"
     x-on:keydown.escape.window="open = false"
     x-cloak>
    {{-- Overlay --}}
    <div x-show="open" x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="background:rgba(17,12,40,0.55); backdrop-filter:blur(2px);">

        {{-- Card --}}
        <div x-show="open" @click.outside="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="bg-white rounded-2xl w-full max-w-[440px] overflow-hidden"
             style="box-shadow:0 24px 60px rgba(16,12,40,0.35);">

            {{-- Header gradient --}}
            <div class="relative px-7 pt-8 pb-7 text-center text-white overflow-hidden"
                 style="background:linear-gradient(135deg,#0891b2,#22d3ee);">
                <div class="absolute inset-0 opacity-20"
                     style="background:radial-gradient(circle at 80% 0%, #fff, transparent 45%);"></div>
                <button type="button" @click="open = false"
                        class="absolute top-3.5 right-3.5 w-8 h-8 inline-flex items-center justify-center rounded-lg text-white/80 hover:bg-white/15 transition">
                    <x-icon name="o-x-mark" class="w-5 h-5" />
                </button>
                <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                     style="background:rgba(255,255,255,0.18);">
                    <x-icon name="o-sparkles" class="w-9 h-9" />
                </div>
                <h2 class="relative text-xl font-bold m-0">Fitur Premium</h2>
                <p class="relative text-sm opacity-90 mt-1.5 m-0">Fitur ini hanya tersedia untuk akun premium.</p>
            </div>

            {{-- Body --}}
            <div class="px-7 py-6">
                <p class="text-sm text-[#52525b] m-0 mb-4 text-center leading-relaxed">
                    Buka akses penuh ke <span class="font-semibold text-[#1b2433]">Laporan</span>,
                    <span class="font-semibold text-[#1b2433]">Denah Lapak</span>,
                    <span class="font-semibold text-[#1b2433]">Pengeluaran</span>, dan
                    <span class="font-semibold text-[#1b2433]">Pedagang Eksternal</span>.
                    Hubungi developer untuk mengaktifkan.
                </p>

                <a href="{{ $waLink }}" target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 w-full h-12 rounded-xl text-white font-semibold text-sm transition hover:brightness-95"
                   style="background:#16a34a;">
                    <x-icon name="o-chat-bubble-left-right" class="w-5 h-5" />
                    Hubungi Developer via WhatsApp
                </a>

                <button type="button" @click="open = false"
                        class="w-full h-11 mt-2.5 rounded-xl text-sm font-medium text-[#71717a] hover:bg-[#f4f4f6] transition">
                    Nanti saja
                </button>
            </div>
        </div>
    </div>
</div>
