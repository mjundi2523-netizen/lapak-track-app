@php
    $condPill = [
        'regular'  => ['Regular',  '#dcfce7', '#15803d'],
        'new'      => ['Baru',     '#dbeafe', '#1d4ed8'],
        'external' => ['Eksternal','#fae8ff', '#86198f'],
    ];
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0">Rekap Tagihan Pedagang</h1>
        </div>
        <a href="{{ route('reports.dealer-summary.export', array_filter([
                'from'        => $this->from,
                'to'          => $this->to,
                'search'      => $this->search,
                'only_active' => $this->onlyActive ? '1' : null,
            ])) }}"
           class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95 shrink-0"
           style="background:#16a34a;">
            <x-icon name="o-arrow-down-tray" class="w-4 h-4" /> Export Excel
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs font-semibold text-[#52525b] mb-1">Dari</label>
            <input type="date" wire:model.live="from" class="input input-bordered input-sm h-10 text-sm" />
        </div>
        <div>
            <label class="block text-xs font-semibold text-[#52525b] mb-1">Sampai</label>
            <input type="date" wire:model.live="to" class="input input-bordered input-sm h-10 text-sm" />
        </div>
        <x-input placeholder="Cari nama/NIK..." wire:model.live.debounce="search" clearable />
        <label class="flex items-center gap-2 text-sm text-[#3f3f46] cursor-pointer h-10 self-end">
            <input type="checkbox" wire:model.live="onlyActive" class="checkbox checkbox-sm" style="accent-color:var(--lt-p);" />
            Hanya yang punya tagihan
        </label>
    </div>

    {{-- Kartu grand total --}}
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-xl p-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="text-xs font-semibold text-[#a1a1aa] uppercase tracking-wide mb-1">Total Tagihan</div>
            <div class="text-2xl font-bold text-[#1b2433]">{{ $rp($grandBilled) }}</div>
        </div>
        <div class="bg-white rounded-xl p-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="text-xs font-semibold text-[#a1a1aa] uppercase tracking-wide mb-1">Total Terbayar</div>
            <div class="text-2xl font-bold text-[#15803d]">{{ $rp($grandPaid) }}</div>
        </div>
        <div class="bg-white rounded-xl p-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="text-xs font-semibold text-[#a1a1aa] uppercase tracking-wide mb-1">Total Tunggakan</div>
            <div class="text-2xl font-bold {{ $grandOutstanding > 0 ? 'text-[#b91c1c]' : 'text-[#15803d]' }}">{{ $rp($grandOutstanding) }}</div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">Nama</th>
                    <th class="lt-th">NIK</th>
                    <th class="lt-th">Kondisi</th>
                    <th class="lt-th">Lokasi</th>
                    <th class="lt-th">Aturan Bayar</th>
                    <th class="lt-th text-center">Tagihan</th>
                    <th class="lt-th text-right">Total</th>
                    <th class="lt-th text-right">Terbayar</th>
                    <th class="lt-th text-right">Tunggakan</th>
                    <th class="lt-th">Terakhir Bayar</th>
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($dealers as $dealer)
                    @php
                        $s = $summaries[$dealer->did] ?? ['bill_count' => 0, 'total_billed' => 0, 'total_paid' => 0, 'outstanding' => 0, 'last_payment' => null];
                        $cp = $condPill[$dealer->dealer_condition] ?? [$dealer->dealer_condition, '#f1f1f3', '#52525b'];
                        $stallCodes = $dealer->dealerStalls->map(fn($ds) => $ds->stall?->code)->filter()->values();
                        $location = $stallCodes->take(2)->implode(', ');
                        if ($stallCodes->count() > 2) $location .= ', …';
                        if ($stallCodes->isEmpty()) $location = $dealer->dealer_condition === 'external' ? 'Eksternal' : '-';
                        $hasDebt = $s['outstanding'] > 0;
                        $terms = $dealer->dealer_condition === 'external'
                            ? $dealer->activeExternal->map(fn($e) => $e->paymentTerm)->filter()->values()
                            : $dealer->dealerStalls->map(fn($ds) => $ds->stallPaymentTerm?->paymentTerm)->filter()->values();
                        $freqShort = fn($f) => match($f) { 'daily'=>'hari','weekly'=>'minggu','monthly'=>'bulan','annual'=>'tahun', default=>$f };
                    @endphp
                    <tr class="lt-row {{ $hasDebt ? 'lt-row-danger' : '' }}">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $dealer->name }}</td>
                        <td class="lt-td tabular-nums text-[#52525b]">{{ $dealer->nik }}</td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:{{ $cp[1] }}; color:{{ $cp[2] }};">{{ $cp[0] }}</span>
                        </td>
                        <td class="lt-td">{{ $location }}</td>
                        <td class="lt-td">
                            @forelse($terms->take(2) as $t)
                                <div class="text-[13px] text-[#3f3f46] whitespace-nowrap">
                                    <span class="font-medium">{{ $t->term_name }}</span>
                                    <span class="text-[#71717a]">· Rp {{ number_format($t->price, 0, ',', '.') }} / {{ $freqShort($t->frequency) }}</span>
                                </div>
                            @empty
                                <span class="text-[#d4d4d8]">—</span>
                            @endforelse
                            @if($terms->count() > 2)
                                <div class="text-[13px] text-[#a1a1aa]">…</div>
                            @endif
                        </td>
                        <td class="lt-td text-center">
                            @if($s['bill_count'] > 0)
                                <span class="lt-pill" style="background:#f1f1f3; color:#52525b;">{{ $s['bill_count'] }}</span>
                            @else
                                <span class="text-[#d4d4d8]">—</span>
                            @endif
                        </td>
                        <td class="lt-td text-right">{{ $s['total_billed'] > 0 ? $rp($s['total_billed']) : '—' }}</td>
                        <td class="lt-td text-right text-[#15803d]">{{ $s['total_paid'] > 0 ? $rp($s['total_paid']) : '—' }}</td>
                        <td class="lt-td text-right font-semibold">
                            @if($s['outstanding'] > 0)
                                <span class="text-error">{{ $rp($s['outstanding']) }}</span>
                            @elseif($s['bill_count'] > 0)
                                <span class="text-[#15803d]">Lunas</span>
                            @else
                                <span class="text-[#d4d4d8]">—</span>
                            @endif
                        </td>
                        <td class="lt-td text-[#71717a]">
                            {{ $s['last_payment'] ? \Illuminate\Support\Carbon::parse($s['last_payment'])->format('d-m-Y') : '—' }}
                        </td>
                        <td class="lt-td">
                            <a href="{{ route('dealers.show', $dealer) }}" wire:navigate class="lt-act" title="Detail Pedagang">
                                <x-icon name="o-eye" class="w-[18px] h-[18px]" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $dealers->links() }}</div>
    </div>
</div>
