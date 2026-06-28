<div x-data="{ open: false }" class="relative" wire:poll.60s>
    {{-- Bell button --}}
    <button @click="open = !open" @click.outside="open = false"
            class="relative w-10 h-10 inline-flex items-center justify-center rounded-[10px] text-[#52525b] hover:bg-base-200 transition">
        <x-icon name="o-bell" class="w-[21px] h-[21px]" />
        @if($totalCount > 0)
            <span class="absolute top-[7px] right-[7px] min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full text-white text-[10px] font-bold leading-none"
                  style="background:#ef4444; border:2px solid #fff; line-height:1;">
                {{ $totalCount > 99 ? '99+' : $totalCount }}
            </span>
        @else
            <span class="absolute top-[9px] right-[9px] w-2 h-2 rounded-full" style="background:#d1d5db; border:2px solid #fff;"></span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open" x-cloak x-transition.opacity
         class="absolute right-0 top-[52px] bg-white rounded-2xl overflow-hidden z-50"
         style="width:340px; border:1px solid #e5e7eb; box-shadow:0 16px 40px rgba(0,0,0,0.13);">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid #f0f0f1;">
            <span class="text-sm font-bold text-[#1b2433]">Notifikasi</span>
            <div class="flex items-center gap-2">
                @if($totalCount > 0)
                    <button wire:click="clear"
                            class="text-xs font-semibold transition hover:underline"
                            style="color:#ef4444;">
                        Hapus semua
                    </button>
                @endif
            </div>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto" style="max-height:380px;">
            @if($totalCount === 0)
                <div class="flex flex-col items-center gap-2 py-10 px-5 text-center">
                    @if($isCleared)
                        <x-icon name="o-bell-slash" class="w-10 h-10 text-[#9aa3b2]" />
                        <p class="text-sm font-semibold text-[#1b2433] m-0">Notifikasi dihapus</p>
                        <p class="text-xs text-[#9aa3b2] m-0">Notifikasi baru akan muncul saat ada tagihan overdue berikutnya.</p>
                    @else
                        <x-icon name="o-check-circle" class="w-10 h-10 text-[#22c55e]" />
                        <p class="text-sm font-semibold text-[#1b2433] m-0">Semua tagihan lunas</p>
                        <p class="text-xs text-[#9aa3b2] m-0">Tidak ada tagihan yang melewati jatuh tempo.</p>
                    @endif
                </div>
            @else
                {{-- Jatuh Tempo Hari Ini --}}
                @if($todayItems->count() > 0)
                    <div class="px-5 pt-4 pb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#9aa3b2]">Jatuh Tempo Hari Ini</span>
                    </div>
                    @foreach($todayItems as $bill)
                        <a href="{{ route('bills.show', $bill) }}" @click="open = false"
                           class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-[#fffbeb]"
                           style="border-top:1px solid #f4f4f5;">
                            <div class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                 style="background:#f59e0b;">
                                {{ strtoupper(substr($bill->holder?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#18181b] truncate">{{ $bill->holder?->name ?? '-' }}</div>
                                <div class="text-xs text-[#71717a] truncate">{{ $bill->location_label }} · Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</div>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0" style="background:#fef3c7; color:#92400e;">Hari ini</span>
                        </a>
                    @endforeach
                @endif

                {{-- Lewat Jatuh Tempo --}}
                @if($overdueItems->count() > 0)
                    <div class="px-5 pt-4 pb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#9aa3b2]">Lewat Jatuh Tempo</span>
                    </div>
                    @foreach($overdueItems as $bill)
                        @php $daysLate = (int) $bill->due_date->diffInDays(now()); @endphp
                        <a href="{{ route('bills.show', $bill) }}" @click="open = false"
                           class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-[#fff1f2]"
                           style="border-top:1px solid #f4f4f5;">
                            <div class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                 style="background:#ef4444;">
                                {{ strtoupper(substr($bill->holder?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#18181b] truncate">{{ $bill->holder?->name ?? '-' }}</div>
                                <div class="text-xs text-[#71717a] truncate">{{ $bill->location_label }} · Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</div>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0" style="background:#fee2e2; color:#b91c1c;">
                                +{{ $daysLate }}h
                            </span>
                        </a>
                    @endforeach
                    @if($totalCount > $todayItems->count() + $overdueItems->count())
                        <p class="text-center text-xs text-[#9aa3b2] py-3 m-0" style="border-top:1px solid #f4f4f5;">
                            dan {{ $totalCount - $todayItems->count() - $overdueItems->count() }} lagi…
                        </p>
                    @endif
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div style="border-top:1px solid #f0f0f1;">
            <a href="{{ route('bills.index') }}" wire:navigate @click="open = false"
               class="flex items-center justify-center gap-1.5 py-3.5 text-xs font-semibold transition hover:bg-base-100"
               style="color:var(--lt-p);">
                Lihat semua tagihan <x-icon name="o-arrow-right" class="w-3.5 h-3.5" />
            </a>
        </div>
    </div>
</div>
