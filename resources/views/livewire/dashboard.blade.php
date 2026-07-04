@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
    ];
    $ptsStr = fn ($pts) => collect($pts)->map(fn ($p) => $p[0] . ',' . $p[1])->implode(' ');
    $areaStr = fn ($pts, $baseY) => $ptsStr($pts) . ' ' . end($pts)[0] . ',' . $baseY . ' ' . $pts[0][0] . ',' . $baseY;
@endphp

<div>
    {{-- Heading --}}
    <div class="mb-[22px]">
        <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Dashboard</h1>
        <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Dashboard</div>
    </div>

    @php
        // Drilldown: rentang bulan berjalan (sama dengan query kartu hero).
        $mStart = \Illuminate\Support\Carbon::today()->startOfMonth()->toDateString();
        $mEnd = \Illuminate\Support\Carbon::today()->endOfMonth()->toDateString();
        $isPremiumUser = auth()->user()->isPremium();
        // Kelas interaksi kartu drilldown.
        $cardHover = 'block cursor-pointer transition hover:-translate-y-0.5 hover:brightness-[0.97]';
    @endphp

    {{-- Hero cards (klik = drilldown ke data acuan) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-5">
        <a href="{{ route('bills.index', ['from' => $mStart, 'to' => $mEnd]) }}" wire:navigate
           title="Lihat tagihan bulan ini"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[120px]"
           style="background:linear-gradient(135deg,#14a07a,#21c08f); box-shadow:0 10px 24px rgba(20,160,122,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Total Tagihan Bulan Ini</div>
            <div class="text-[28px] font-bold leading-none relative z-10">{{ $rp($heroTotal) }}</div>
            <span class="absolute right-[22px] -bottom-2.5 text-[90px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">Rp</span>
        </a>
        <a href="{{ route('payments.index', ['from' => $mStart, 'to' => $mEnd]) }}" wire:navigate
           title="Lihat pembayaran bulan ini"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[120px]"
           style="background:linear-gradient(135deg,#2b7fc2,#3ea7dc); box-shadow:0 10px 24px rgba(43,127,194,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Sudah Terbayar</div>
            <div class="text-[28px] font-bold leading-none relative z-10">{{ $rp($heroPaid) }}</div>
            <span class="absolute right-[22px] -bottom-2.5 text-[90px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">Rp</span>
        </a>
        <a href="{{ route('bills.index', ['statusFilter' => 'unpaid', 'from' => $mStart, 'to' => $mEnd]) }}" wire:navigate
           title="Lihat tagihan belum bayar bulan ini"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[120px]"
           style="background:linear-gradient(135deg, var(--lt-p), color-mix(in srgb, var(--lt-p) 55%, #7aa0ff)); box-shadow:0 10px 24px color-mix(in srgb, var(--lt-p) 30%, transparent);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Belum Terbayar</div>
            <div class="text-[28px] font-bold leading-none relative z-10">{{ $rp($heroUnpaid) }}</div>
            <span class="absolute right-[22px] -bottom-2.5 text-[90px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">Rp</span>
        </a>
        <a @if($isPremiumUser) href="{{ route('reports.dealer-summary') }}" wire:navigate
           @else href="#" @click.prevent="$dispatch('premium-required')" @endif
           title="Lihat rekap tunggakan per pedagang"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[120px]"
           style="background:linear-gradient(135deg,#dc2626,#f87171); box-shadow:0 10px 24px rgba(220,38,38,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Pedagang Menunggak</div>
            <div class="text-[44px] font-bold leading-none relative z-10">{{ $dealersWithDebt }}</div>
            <span class="absolute right-[18px] -bottom-2.5 text-[90px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">!</span>
            <div class="text-xs opacity-75 mt-2 relative z-10">pedagang</div>
        </a>
    </div>

    {{-- Pengeluaran & Laba bersih bulan ini (drilldown; keduanya fitur premium) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <a @if($isPremiumUser) href="{{ route('expenses.index', ['dateFrom' => $mStart, 'dateTo' => $mEnd]) }}" wire:navigate
           @else href="#" @click.prevent="$dispatch('premium-required')" @endif
           title="Lihat pengeluaran bulan ini"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[110px]"
           style="background:linear-gradient(135deg,#ef4444,#f97316); box-shadow:0 10px 24px rgba(239,68,68,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Pengeluaran Bulan Ini</div>
            <div class="text-[30px] font-bold leading-none relative z-10">{{ $rp($heroExpense) }}</div>
            <span class="absolute right-[22px] -bottom-2.5 text-[80px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">Rp</span>
        </a>
        <a @if($isPremiumUser) href="{{ route('reports.cash-flow', ['year' => now()->year, 'month' => now()->month]) }}" wire:navigate
           @else href="#" @click.prevent="$dispatch('premium-required')" @endif
           title="Lihat laporan arus kas"
           class="{{ $cardHover }} relative overflow-hidden rounded-[14px] px-6 py-[22px] text-white min-h-[110px]"
           style="background:linear-gradient(135deg, {{ $heroNet >= 0 ? '#0f766e,#14b8a6' : '#b91c1c,#ef4444' }}); box-shadow:0 10px 24px rgba(15,118,110,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3 relative z-10">Laba Bersih Bulan Ini <span class="opacity-75">(pemasukan − pengeluaran)</span></div>
            <div class="text-[30px] font-bold leading-none relative z-10">{{ $rp($heroNet) }}</div>
            <span class="absolute right-[22px] -bottom-2.5 text-[80px] font-extrabold leading-none" style="color:rgba(255,255,255,0.15);">Rp</span>
        </a>
    </div>

    {{-- Two-column grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_580px] gap-5 items-start">
        {{-- Left column --}}
        <div class="flex flex-col gap-5 min-w-0">
            {{-- Statistik chart --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="flex items-center justify-between px-6 pt-5">
                    <h3 class="text-[17px] font-bold text-[#1b2433] m-0">Statistik</h3>
                    <span class="text-[13px] text-[#9aa3b2]">8 bulan terakhir</span>
                </div>
                <div class="flex gap-5 px-6 pt-3.5 pb-0.5">
                    <div class="flex items-center gap-[7px] text-[13px] text-[#52525b]"><span class="w-[9px] h-[9px] rounded-full" style="background:var(--lt-p);"></span>Tagihan</div>
                    <div class="flex items-center gap-[7px] text-[13px] text-[#52525b]"><span class="w-[9px] h-[9px] rounded-full" style="background:#14a07a;"></span>Terbayar</div>
                    <div class="flex items-center gap-[7px] text-[13px] text-[#52525b]"><span class="w-[9px] h-[9px] rounded-full" style="background:#ef4444;"></span>Pengeluaran</div>
                </div>
                <div class="px-4 pt-1 pb-[18px]">
                    <svg viewBox="0 0 {{ $chart['w'] }} {{ $chart['h'] }}" width="100%" style="display:block; overflow:visible;">
                        <defs>
                            <linearGradient id="lt_gA" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgba(8,145,178,0.28)"></stop>
                                <stop offset="100%" stop-color="rgba(8,145,178,0.02)"></stop>
                            </linearGradient>
                            <linearGradient id="lt_gB" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgba(20,160,122,0.26)"></stop>
                                <stop offset="100%" stop-color="rgba(20,160,122,0.02)"></stop>
                            </linearGradient>
                        </defs>
                        @foreach($chart['grid'] as $g)
                            <line x1="44" y1="{{ $g['y'] }}" x2="712" y2="{{ $g['y'] }}" stroke="#eef0f4" stroke-width="1" stroke-dasharray="4 5"></line>
                            <text x="34" y="{{ $g['y'] + 3 }}" text-anchor="end" font-size="11" fill="#9aa3b2" font-family="sans-serif">{{ $g['label'] }}</text>
                        @endforeach
                        <polygon points="{{ $areaStr($chart['a'], $chart['baseY']) }}" fill="url(#lt_gA)" stroke="none"></polygon>
                        <polygon points="{{ $areaStr($chart['b'], $chart['baseY']) }}" fill="url(#lt_gB)" stroke="none"></polygon>
                        <polyline points="{{ $ptsStr($chart['a']) }}" fill="none" stroke="#0891b2" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <polyline points="{{ $ptsStr($chart['b']) }}" fill="none" stroke="#14a07a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <polyline points="{{ $ptsStr($chart['c']) }}" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-dasharray="5 4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        @foreach($chart['xlabels'] as $xl)
                            <text x="{{ $xl['x'] }}" y="294" text-anchor="middle" font-size="11" fill="#9aa3b2" font-family="sans-serif">{{ $xl['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </div>

            {{-- Jatuh Tempo & Terlambat --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="flex items-center justify-between px-6 py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold text-[#1b2433] m-0">Jatuh Tempo &amp; Terlambat</h3>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#b91c1c;">{{ $overdueTotal }} tagihan</span>
                </div>

                @if($overdue->isEmpty())
                    <div class="text-center py-10 text-[#9aa3b2]">
                        <x-icon name="o-check-circle" class="w-10 h-10 mx-auto mb-2 text-success" />
                        Tidak ada tagihan yang jatuh tempo. 🎉
                    </div>
                @else
                    <table class="w-full border-collapse">
                        <thead>
                            <tr style="background:color-mix(in srgb, var(--lt-p) 6%, #fff);">
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">No. Tagihan</th>
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Pedagang</th>
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Lapak</th>
                                <th class="text-right px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Tagihan</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdue as $row)
                                @php $st = $statusMap[$row->billing_status] ?? [$row->billing_status, '#f1f1f3', '#52525b']; @endphp
                                <tr class="cursor-pointer transition-colors hover:bg-[#fff7f7]"
                                    style="background:#fef2f2;"
                                    onclick="window.location='{{ route('bills.show', $row) }}'">
                                    <td class="px-6 py-3.5 text-sm font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $row->bill_id ?? '-' }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $row->holder?->name ?? '-' }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $row->location_label }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a] text-right" style="border-top:1px solid #f4f4f5;">{{ $rp($row->total_amount) }}</td>
                                    <td class="px-6 py-3.5" style="border-top:1px solid #f4f4f5;">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3.5" style="border-top:1px solid #f4f4f5;">
                        {{ $overdue->links() }}
                    </div>
                @endif
            </div>
            {{-- Top 10 Tunggakan Terbesar --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="flex items-center justify-between px-6 py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold text-[#1b2433] m-0">10 Tunggakan Terbesar</h3>
                    <a href="{{ route('reports.dealer-summary') }}" wire:navigate
                       class="text-xs font-semibold transition hover:underline" style="color:var(--lt-p);">
                        Lihat semua →
                    </a>
                </div>
                @if($top10Debtors->isEmpty())
                    <div class="text-center py-10 text-[#9aa3b2]">
                        <x-icon name="o-check-circle" class="w-10 h-10 mx-auto mb-2 text-success" />
                        Tidak ada tunggakan.
                    </div>
                @else
                    <table class="w-full border-collapse">
                        <thead>
                            <tr style="background:color-mix(in srgb, var(--lt-p) 6%, #fff);">
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">#</th>
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Pedagang</th>
                                <th class="text-right px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Total Tunggakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($top10Debtors as $i => $d)
                                <tr class="cursor-pointer transition-colors hover:bg-[#fff7f7]"
                                    onclick="window.location='{{ route('dealers.show', $d['did']) }}'">
                                    <td class="px-6 py-3.5 text-sm text-[#9aa3b2] font-semibold" style="border-top:1px solid #f4f4f5;">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3.5 text-sm font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $d['name'] }}</td>
                                    <td class="px-6 py-3.5 text-sm font-bold text-right text-[#b91c1c]" style="border-top:1px solid #f4f4f5;">
                                        {{ $rp($d['outstanding']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="flex flex-col gap-5">
            {{-- Pembayaran Terbaru --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-[22px] py-[18px]" style="border-bottom:1px solid #eef0f4;">
                    <h3 class="text-base font-bold text-[#1b2433] m-0">Pembayaran Terbaru</h3>
                </div>
                <div class="px-[22px] pt-1.5 pb-3.5">
                    @forelse($recentPayments as $p)
                        <div class="flex items-start gap-3 py-3.5" @if(!$loop->last) style="border-bottom:1px solid #f4f4f5;" @endif>
                            <span class="w-[9px] h-[9px] rounded-full mt-1.5 shrink-0" style="background:#14a07a;"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#27272a] truncate">{{ $p->dealerBill?->holder?->name ?? 'Pedagang' }}</div>
                                <div class="text-xs text-[#9aa3b2] mt-0.5">{{ $p->dealerBill?->bill_id ?? '-' }} · {{ $p->payment_date?->diffForHumans() }}</div>
                            </div>
                            <div class="text-sm font-bold text-[#27272a] whitespace-nowrap">{{ $rp($p->paid_amount) }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-[#9aa3b2] text-sm">Belum ada pembayaran.</div>
                    @endforelse
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="bg-white rounded-2xl p-[22px]" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="flex items-start justify-between mb-[18px]">
                    <h3 class="text-base font-bold text-[#1b2433] m-0">Ringkasan</h3>
                    <div class="text-right">
                        <div class="text-xs text-[#9aa3b2]">Total Pemasukan</div>
                        <div class="text-[26px] font-bold text-[#1b2433] leading-tight">{{ $rp($totalPemasukan) }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('dealers.index') }}" wire:navigate title="Lihat daftar pedagang"
                       class="block rounded-[11px] p-3.5 transition hover:brightness-[0.97]" style="background:#f6f7fb;">
                        <div class="text-xs text-[#71717a] mb-1.5">Pedagang Aktif</div>
                        <div class="text-[22px] font-bold text-[#15803d]">{{ $dealerActive }}</div>
                    </a>
                    <a href="{{ route('stalls.index') }}" wire:navigate title="Lihat daftar lapak"
                       class="block rounded-[11px] p-3.5 transition hover:brightness-[0.97]" style="background:#f6f7fb;">
                        <div class="text-xs text-[#71717a] mb-1.5">Lapak Terisi</div>
                        <div class="text-[22px] font-bold" style="color:var(--lt-p);">{{ $stallOccupied }}</div>
                    </a>
                    <a href="{{ route('bills.index', ['statusFilter' => 'paid']) }}" wire:navigate title="Lihat tagihan lunas"
                       class="block rounded-[11px] p-3.5 transition hover:brightness-[0.97]" style="background:#f6f7fb;">
                        <div class="text-xs text-[#71717a] mb-1.5">Tagihan Lunas</div>
                        <div class="text-[22px] font-bold text-[#15803d]">{{ $billPaid }}</div>
                    </a>
                    <a href="{{ route('bills.index', ['statusFilter' => 'unpaid']) }}" wire:navigate title="Lihat tagihan belum bayar"
                       class="block rounded-[11px] p-3.5 transition hover:brightness-[0.97]" style="background:#f6f7fb;">
                        <div class="text-xs text-[#71717a] mb-1.5">Belum Bayar</div>
                        <div class="text-[22px] font-bold text-[#b91c1c]">{{ $billUnpaid }}</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
