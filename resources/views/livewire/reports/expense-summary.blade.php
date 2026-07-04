@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $catColors = [
        ['#ede9fe', '#6d28d9'], ['#dbeafe', '#1d4ed8'], ['#dcfce7', '#15803d'],
        ['#fef3c7', '#92400e'], ['#fce7f3', '#be185d'], ['#cffafe', '#0e7490'],
        ['#fae8ff', '#86198f'], ['#ffedd5', '#9a3412'],
    ];
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Rekap Pengeluaran</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Laporan&nbsp;/&nbsp;Rekap Pengeluaran</div>
        </div>
        <div class="flex items-center gap-2.5">
            <x-select wire:model.live="categoryFilter" placeholder="Semua Kategori"
                :options="$categories->map(fn($c) => ['id' => $c->ecid, 'name' => $c->name])" option-value="id" option-label="name" class="w-44" />
            <x-select wire:model.live="month" :options="$months" option-value="id" option-label="name" class="w-40" />
            <x-select wire:model.live="year" :options="$years->map(fn($y) => ['id' => $y, 'name' => $y])" option-value="id" option-label="name" class="w-28" />
            <a href="{{ route('reports.expense-summary.export', array_filter([
                    'year' => $year, 'month' => $month, 'category' => $categoryFilter,
                ])) }}"
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95 shrink-0"
               style="background:#16a34a;">
                <x-icon name="o-arrow-down-tray" class="w-4 h-4" /> Excel
            </a>
        </div>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg,#ef4444,#f97316); box-shadow:0 10px 24px rgba(239,68,68,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3">Total Pengeluaran {{ $periodLabel }}</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($grandTotal) }}</div>
            <div class="text-xs opacity-80 mt-2">{{ $grandCount }} transaksi</div>
        </div>
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg,#6d28d9,#9333ea); box-shadow:0 10px 24px rgba(109,40,217,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3">Rata-rata / Transaksi</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($avg) }}</div>
        </div>
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg, var(--lt-p),#22d3ee); box-shadow:0 10px 24px color-mix(in srgb, var(--lt-p) 24%, transparent);">
            <div class="text-sm font-medium opacity-90 mb-3">Rincian Metode</div>
            <div class="text-[13px] leading-relaxed mt-1">
                <div class="flex justify-between"><span class="opacity-85">Tunai</span><span class="font-semibold">{{ $rp($tunaiTotal) }}</span></div>
                <div class="flex justify-between"><span class="opacity-85">Transfer</span><span class="font-semibold">{{ $rp($transferTotal) }}</span></div>
                <div class="flex justify-between"><span class="opacity-85">Lainnya</span><span class="font-semibold">{{ $rp($lainnyaTotal) }}</span></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-5 items-start mb-5">
        {{-- Tabel per bulan --}}
        <div class="lt-card-table">
            <table class="lt-table">
                <thead>
                    <tr>
                        <th class="lt-th">Bulan</th>
                        <th class="lt-th text-right">Pengeluaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyRows as $r)
                        <tr class="lt-row">
                            <td class="lt-td">{{ $r['month'] }}</td>
                            <td class="lt-td text-right {{ $r['total'] > 0 ? 'text-[#b91c1c]' : 'text-[#9aa3b2]' }}">{{ $rp($r['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#fafafa;">
                        <td class="lt-td font-bold text-[#1b2433]">Total</td>
                        <td class="lt-td text-right font-bold text-[#b91c1c]">{{ $rp($grandTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pengeluaran per kategori --}}
        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="px-5 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Pengeluaran per Kategori</div>
            @forelse($byCategory as $i => $c)
                @php $col = $catColors[$i % count($catColors)]; @endphp
                <div class="px-5 py-3" @if(!$loop->last) style="border-bottom:1px solid #f4f4f5;" @endif>
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-[#27272a] font-medium">{{ $c['name'] }} <span class="text-[#9aa3b2] font-normal">· {{ $c['count'] }}x</span></span>
                        <span class="font-semibold text-[#b91c1c]">{{ $rp($c['total']) }}</span>
                    </div>
                    <div class="h-1.5 rounded-full overflow-hidden" style="background:#f1f1f3;">
                        <div class="h-full rounded-full" style="width:{{ $c['pct'] }}%; background:{{ $col[1] }};"></div>
                    </div>
                    <div class="text-[11px] text-[#9aa3b2] mt-1">{{ $c['pct'] }}% dari total</div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-[#9aa3b2] m-0">Belum ada pengeluaran pada periode ini.</p>
            @endforelse
        </div>
    </div>

    {{-- Tabel detail --}}
    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">Tanggal</th>
                    <th class="lt-th">Judul</th>
                    <th class="lt-th">Kategori</th>
                    <th class="lt-th">Metode</th>
                    <th class="lt-th text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $row)
                    @php $c = $catColors[($row->ecid ?? 0) % count($catColors)]; @endphp
                    <tr class="lt-row">
                        <td class="lt-td">{{ $row->expense_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td font-semibold text-[#18181b]">
                            {{ $row->title }}
                            @if($row->rxid)
                                <span class="lt-pill ml-1" style="background:#cffafe; color:#0e7490;">Rutin</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:{{ $c[0] }}; color:{{ $c[1] }};">{{ $row->category?->name ?? '-' }}</span>
                        </td>
                        <td class="lt-td">{{ ucfirst($row->payment_method) }}</td>
                        <td class="lt-td text-right font-medium">{{ $rp($row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $expenses->links() }}</div>
    </div>
</div>
