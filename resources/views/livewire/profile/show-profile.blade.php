@php
    $user = Auth::user();
    $initials = strtoupper(\Illuminate\Support\Str::substr($user->name, 0, 2));
    $inputCls = 'w-full h-[42px] px-3 rounded-[9px] text-sm outline-none text-[#18181b] bg-white box-border';
    $inputStyle = 'border:1px solid #d4d4d8;';
    $labelCls = 'block text-[13px] font-semibold mb-1.5 text-[#3f3f46]';
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Profil Pengguna</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Profil</div>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate
           class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold transition hover:brightness-95"
           style="background:#f4f4f6; color:#52525b;">
            <x-icon name="o-arrow-left" class="w-4 h-4" /> Kembali
        </a>
    </div>

    {{-- Header card --}}
    <div class="relative bg-white rounded-2xl overflow-hidden mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div style="height:108px; background:linear-gradient(120deg, var(--lt-p), color-mix(in srgb, var(--lt-p) 55%, #22d3ee));"></div>
        <div class="px-7 pb-6 flex items-end gap-5 flex-wrap">
            <div class="w-[104px] h-[104px] rounded-full text-white flex items-center justify-center text-4xl font-bold shrink-0 -mt-[52px]"
                 style="background:var(--lt-p); border:4px solid #fff; box-shadow:0 6px 18px rgba(16,12,40,0.12);">{{ $initials }}</div>
            <div class="flex-1 min-w-[200px] pt-3.5">
                <div class="text-[21px] font-bold text-[#1b2433]">{{ $user->name }}</div>
                <div class="text-sm text-[#9aa3b2] mt-0.5">{{ $user->email }}</div>
            </div>
            <div class="flex gap-[18px] pt-3.5">
                <div class="text-center"><div class="text-[22px] font-bold text-[#1b2433]">{{ $stallCount }}</div><div class="text-xs text-[#9aa3b2]">Lapak</div></div>
                <div class="w-px" style="background:#eceef2;"></div>
                <div class="text-center"><div class="text-[22px] font-bold text-[#1b2433]">{{ $dealerCount }}</div><div class="text-xs text-[#9aa3b2]">Pedagang</div></div>
                <div class="w-px" style="background:#eceef2;"></div>
                <div class="text-center"><div class="text-[22px] font-bold text-[#1b2433]">Admin</div><div class="text-xs text-[#9aa3b2]">Peran</div></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-5 items-start">
        {{-- Kolom kiri: form --}}
        <div class="flex flex-col gap-5 min-w-0">
            {{-- Informasi Profil --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-6 py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold m-0 text-[#1b2433]">Informasi Profil</h3>
                    <p class="text-[13px] text-[#9aa3b2] mt-1 mb-0">Perbarui nama dan alamat email akun Anda.</p>
                </div>
                <form wire:submit="updateProfile" class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelCls }}">Nama</label>
                            <input wire:model="name" class="{{ $inputCls }}" style="{{ $inputStyle }}" />
                            @error('name') <span class="text-xs text-[#dc2626] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Email</label>
                            <input wire:model="email" type="email" class="{{ $inputCls }}" style="{{ $inputStyle }}" />
                            @error('email') <span class="text-xs text-[#dc2626] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Peran</label>
                            <input value="Administrator" disabled class="w-full h-[42px] px-3 rounded-[9px] text-sm outline-none box-border" style="border:1px solid #ececf0; color:#9aa3b2; background:#f7f8fb; cursor:not-allowed;" />
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Bergabung</label>
                            <input value="{{ $user->created_at?->format('d M Y') ?? '-' }}" disabled class="w-full h-[42px] px-3 rounded-[9px] text-sm outline-none box-border" style="border:1px solid #ececf0; color:#9aa3b2; background:#f7f8fb; cursor:not-allowed;" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-5 pt-[18px]" style="border-top:1px solid #f0f0f1;">
                        <button type="submit" wire:loading.attr="disabled"
                                class="h-[42px] px-[22px] rounded-[9px] text-sm font-semibold text-white border-none cursor-pointer transition hover:brightness-95"
                                style="background:var(--lt-p);">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            {{-- Ubah Password --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-6 py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold m-0 text-[#1b2433]">Ubah Password</h3>
                    <p class="text-[13px] text-[#9aa3b2] mt-1 mb-0">Gunakan password minimal 8 karakter agar tetap aman.</p>
                </div>
                <form wire:submit="changePassword" class="p-6">
                    <div class="flex flex-col gap-4 max-w-[420px]">
                        <div>
                            <label class="{{ $labelCls }}">Password Baru</label>
                            <input wire:model="password" type="password" placeholder="••••••••" class="{{ $inputCls }}" style="{{ $inputStyle }}" />
                            @error('password') <span class="text-xs text-[#dc2626] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Konfirmasi Password</label>
                            <input wire:model="password_confirmation" type="password" placeholder="••••••••" class="{{ $inputCls }}" style="{{ $inputStyle }}" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-5 pt-[18px]" style="border-top:1px solid #f0f0f1;">
                        <button type="submit" wire:loading.attr="disabled"
                                class="h-[42px] px-[22px] rounded-[9px] text-sm font-semibold text-white border-none cursor-pointer transition hover:brightness-95"
                                style="background:var(--lt-p);">Perbarui Password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Kolom kanan --}}
        <div class="flex flex-col gap-5">
            {{-- Tampilan / Dark mode --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-[22px] py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold m-0 text-[#1b2433]">Tampilan</h3>
                </div>
                <div class="px-[22px] py-[18px] flex items-center justify-between gap-3"
                     x-data="{ on: document.body.classList.contains('lt-dark') }">
                    <div class="flex items-center gap-3">
                        <div class="w-[38px] h-[38px] rounded-[10px] flex items-center justify-center shrink-0"
                             style="background:color-mix(in srgb, var(--lt-p) 12%, #fff); color:var(--lt-p);">
                            <x-icon name="o-moon" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-[#1b2433]">Mode Gelap</div>
                            <div class="text-xs text-[#9aa3b2]" x-text="on ? 'Sedang aktif' : 'Nonaktif'"></div>
                        </div>
                    </div>
                    <button type="button" role="switch" :aria-checked="on"
                            @click="on=!on; document.body.classList.toggle('lt-dark', on); document.body.setAttribute('data-theme', on ? 'dark' : 'light'); $wire.setDark(on)"
                            class="relative w-[46px] h-[26px] rounded-full transition-colors shrink-0 cursor-pointer"
                            :style="on ? 'background:var(--lt-p)' : 'background:#d4d4d8'">
                        <span class="absolute top-[3px] left-[3px] w-5 h-5 rounded-full bg-white transition-transform"
                              :style="on ? 'transform:translateX(20px)' : ''"></span>
                    </button>
                </div>
            </div>

            {{-- Detail Akun --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-[22px] py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold m-0 text-[#1b2433]">Detail Akun</h3>
                </div>
                <div class="px-[22px] pt-2 pb-4">
                    <div class="flex justify-between gap-3 py-[11px] text-[13px]" style="border-bottom:1px solid #f4f4f5;">
                        <span class="text-[#9aa3b2]">Status</span>
                        <span class="inline-flex items-center gap-1.5 font-semibold text-[#15803d]"><span class="w-2 h-2 rounded-full" style="background:#16a34a;"></span>Aktif</span>
                    </div>
                    <div class="flex justify-between gap-3 py-[11px] text-[13px]" style="border-bottom:1px solid #f4f4f5;">
                        <span class="text-[#9aa3b2]">Verifikasi Email</span>
                        @if($user->email_verified_at)
                            <span class="font-semibold text-[#15803d]">Terverifikasi</span>
                        @else
                            <span class="font-semibold text-[#a16207]">Belum</span>
                        @endif
                    </div>
                    <div class="flex justify-between gap-3 py-[11px] text-[13px]" style="border-bottom:1px solid #f4f4f5;">
                        <span class="text-[#9aa3b2]">Paket</span>
                        @if($user->isPremium())
                            <span class="font-semibold" style="color:var(--lt-p);">Premium</span>
                        @else
                            <span class="font-semibold text-[#27272a]">Gratis</span>
                        @endif
                    </div>
                    <div class="flex justify-between gap-3 py-[11px] text-[13px]" style="border-bottom:1px solid #f4f4f5;">
                        <span class="text-[#9aa3b2]">Bergabung</span>
                        <span class="text-[#27272a]">{{ $user->created_at?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-3 py-[11px] text-[13px]">
                        <span class="text-[#9aa3b2]">ID Pengguna</span>
                        <span class="text-[#27272a] tabular-nums">#USR-{{ str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>

            {{-- Tentang Aplikasi --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-[22px] py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold m-0 text-[#1b2433]">Tentang Aplikasi</h3>
                </div>
                <div class="px-[22px] py-[18px] text-[13px] text-[#52525b] leading-relaxed">
                    <p class="m-0"><span class="font-semibold" style="color:var(--lt-p);">LapakTrack</span> — sistem manajemen pedagang, lapak, tagihan, dan pembayaran pasar.</p>
                    <p class="text-xs text-[#9aa3b2] mt-2 mb-0">Versi 1.0.0 · © {{ date('Y') }} LapakTrack</p>
                </div>
            </div>

            {{-- Keluar --}}
            <button type="button" wire:click="logout"
                    class="w-full h-10 inline-flex items-center justify-center gap-2 rounded-[9px] text-[13px] font-semibold cursor-pointer transition hover:brightness-95"
                    style="background:#fff; border:1px solid #f0c2c2; color:#dc2626;">
                <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" /> Keluar dari Akun
            </button>
        </div>
    </div>
</div>
