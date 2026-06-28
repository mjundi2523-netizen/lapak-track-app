@php
    $typePill = [
        'MTR' => ['Sewa',           '#ede9fe', '#6d28d9'],
        'MAT' => ['Sewa + Add-on',  '#fce7f3', '#be185d'],
        'AAT' => ['Add-on',         '#dbeafe', '#1d4ed8'],
        'ATR' => ['Add-on (jadwal)','#cffafe', '#0e7490'],
        'EXT' => ['Eksternal',      '#fae8ff', '#86198f'],
    ];
    $freqLabel = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan'];
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
        'cancelled'   => ['Dibatalkan',  '#f1f1f3', '#52525b'],
    ];
@endphp

<div>
    <x-index-header title="Tagihan">
        <x-input placeholder="Cari no. tagihan..." wire:model.live.debounce="search" clearable />
        <div class="w-52 shrink-0">
            <x-choices wire:model.live="dealerId" :options="$dealersList" option-label="name" option-value="did"
                search-function="searchDealer" placeholder="Filter pedagang..." single searchable clearable />
        </div>
        <x-select wire:model.live="frequencyFilter" :options="[
            ['value' => '', 'label' => 'Semua Frekuensi'],
            ['value' => 'daily', 'label' => 'Harian'],
            ['value' => 'weekly', 'label' => 'Mingguan'],
            ['value' => 'monthly', 'label' => 'Bulanan'],
            ['value' => 'annual', 'label' => 'Tahunan'],
        ]" option-value="value" option-label="label" class="w-40" />
        <x-select wire:model.live="statusFilter" :options="[
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'unpaid', 'label' => 'Belum Bayar'],
            ['value' => 'installment', 'label' => 'Cicilan'],
            ['value' => 'paid', 'label' => 'Lunas'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'cancelled', 'label' => 'Dibatalkan'],
        ]" option-value="value" option-label="label" class="w-40" />
        <a href="{{ route('bills.export', array_filter([
                'search'    => $search,
                'status'    => $statusFilter,
                'frequency' => $frequencyFilter,
                'dealer'    => $dealerId,
            ])) }}"
           class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95 shrink-0 no-print"
           style="background:#16a34a;" title="Export ke Excel">
            <x-icon name="o-arrow-down-tray" class="w-4 h-4" /> Excel
        </a>
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">No. Tagihan</th>
                    <th class="lt-th">Jenis</th>
                    <th class="lt-th">Pedagang</th>
                    <th class="lt-th">Lapak</th>
                    <th class="lt-th text-right">Jumlah</th>
                    <th class="lt-th text-right">Terbayar</th>
                    <th class="lt-th text-right">Sisa</th>
                    <th class="lt-th">Jatuh Tempo</th>
                    <th class="lt-th">Status</th>
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $row)
                    @php
                        $t = $typePill[$row->bill_type] ?? null;
                        $st = $statusMap[$row->billing_status] ?? [$row->billing_status, '#f1f1f3', '#52525b'];
                        $paid = $row->payments->sum('paid_amount');
                        $sisa = max($row->total_amount - $paid, 0);
                        $overdue = $row->billing_status === 'unpaid' && $row->due_date < now();
                    @endphp
                    <tr class="lt-row {{ $overdue ? 'lt-row-danger' : '' }}">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->bill_id ?? '-' }}</td>
                        <td class="lt-td">
                            @if($t)
                                <div class="flex flex-col items-start gap-1">
                                    <span class="lt-pill" style="background:{{ $t[1] }}; color:{{ $t[2] }};">{{ $t[0] }}</span>
                                    <span class="lt-pill" style="background:#f1f1f3; color:#52525b; font-size:11px;">{{ $freqLabel[$row->frequency] ?? $row->frequency ?? '-' }}</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="lt-td">{{ $row->holder?->name ?? '-' }}</td>
                        <td class="lt-td">{{ $row->location_label }}</td>
                        <td class="lt-td text-right">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                        <td class="lt-td text-right text-[#71717a]">Rp {{ number_format($paid, 0, ',', '.') }}</td>
                        <td class="lt-td text-right">
                            <span class="font-medium {{ $sisa > 0 ? 'text-error' : 'text-success' }}">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                        </td>
                        <td class="lt-td">{{ $row->due_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('bills.show', $row) }}" wire:navigate class="lt-act" title="Detail"><x-icon name="o-eye" class="w-[18px] h-[18px]" /></a>
                                @if(! in_array($row->billing_status, ['paid', 'cancelled']))
                                    <a href="{{ route('payments.create', ['bill' => $row->dbid]) }}" wire:navigate class="lt-act text-success" title="Bayar"><x-icon name="o-credit-card" class="w-[18px] h-[18px]" /></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $bills->links() }}</div>
    </div>
</div>
