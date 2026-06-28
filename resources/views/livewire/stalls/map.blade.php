<div>
    <x-index-header title="Denah Lapak">
        <x-button label="Daftar Lapak" link="{{ route('stalls.index') }}" class="btn-ghost" icon="o-list-bullet" />
    </x-index-header>

    {{-- Ringkasan + legenda --}}
    <div class="flex flex-wrap items-center gap-2.5 mb-5 text-sm">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full font-medium" style="background:#dcfce7; color:#15803d;">
            <span class="inline-block w-3 h-3 rounded-[3px]" style="background:#16a34a;"></span>
            Terisi ({{ $occupiedCount }})
        </span>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full font-medium" style="background:#fef9c3; color:#a16207;">
            <span class="inline-block w-3 h-3 rounded-[3px] border" style="background:#fff; border-color:#eab308;"></span>
            Kosong ({{ $emptyCount }})
        </span>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full font-medium" style="background:#f1f1f3; color:#52525b;">
            <span class="inline-block w-3 h-3 rounded-[3px]" style="background:#a1a1aa;"></span>
            Nonaktif ({{ $inactiveCount }})
        </span>
        <span class="ml-auto text-[#9aa3b2]">{{ $total }} lapak</span>
    </div>

    @php
        // [bg, border, blockColor, tenantColor]
        $style = [
            'occupied' => ['#dcfce7', '#86efac', '#14532d', '#15803d'],
            'empty'    => ['#ffffff', '#e5e7eb', '#3f3f46', '#9aa3b2'],
            'inactive' => ['#f4f4f5', '#e4e4e7', '#a1a1aa', '#a1a1aa'],
        ];
    @endphp

    <div class="space-y-5">
        @forelse($rows as $prefix => $cells)
            <div class="bg-white rounded-2xl p-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="flex items-center gap-2 mb-3.5">
                    <span class="inline-flex items-center justify-center min-w-7 h-7 px-2 rounded-lg text-sm font-bold text-white" style="background:var(--lt-p);">{{ $prefix }}</span>
                    <span class="text-sm font-semibold text-[#1b2433]">Blok {{ $prefix }}</span>
                    <span class="text-xs text-[#9aa3b2]">· {{ $cells->count() }} lapak</span>
                </div>

                <div class="grid gap-2.5" style="grid-template-columns:repeat(auto-fill, minmax(118px, 1fr));">
                    @foreach($cells as $c)
                        @php $st = $style[$c['status']]; @endphp
                        <a href="{{ route('stalls.show', $c['sid']) }}" wire:navigate
                           class="block rounded-xl px-3 py-2.5 transition hover:brightness-[0.97] hover:-translate-y-0.5"
                           style="background:{{ $st[0] }}; border:1px solid {{ $st[1] }};"
                           title="{{ $prefix }} / {{ $c['number'] }}{{ $c['tenant'] ? ' — '.$c['tenant'] : ($c['status'] === 'inactive' ? ' — Nonaktif' : ' — Kosong') }}">
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="text-sm font-bold" style="color:{{ $st[2] }};">{{ $c['number'] }}</span>
                                @if($c['status'] === 'occupied')
                                    <span class="inline-block w-2 h-2 rounded-full shrink-0" style="background:#16a34a;"></span>
                                @elseif($c['status'] === 'inactive')
                                    <x-icon name="o-no-symbol" class="w-3.5 h-3.5 shrink-0" style="color:#a1a1aa;" />
                                @endif
                            </div>
                            <div class="text-[11px] mt-0.5 truncate" style="color:{{ $st[3] }};">
                                @if($c['status'] === 'occupied')
                                    {{ $c['tenant'] ?? 'Terisi' }}
                                @elseif($c['status'] === 'inactive')
                                    Nonaktif
                                @else
                                    Kosong
                                @endif
                            </div>
                            @if($c['size'])
                                <div class="text-[10px] mt-0.5" style="color:#b4bac6;">{{ $c['size'] }}</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-10 text-center text-[#9aa3b2]" style="border:1px solid #eceef2;">
                Belum ada lapak.
            </div>
        @endforelse
    </div>
</div>
