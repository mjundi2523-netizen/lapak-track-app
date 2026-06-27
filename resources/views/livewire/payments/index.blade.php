@php
    $typePill = [
        'MTR' => ['Sewa',          '#ede9fe', '#6d28d9'],
        'MAT' => ['Sewa + Add-on', '#fce7f3', '#be185d'],
        'AAT' => ['Add-on',        '#dbeafe', '#1d4ed8'],
        'ATR' => ['Add-on (jadwal)','#cffafe', '#0e7490'],
    ];
    $freqLabel = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan'];
@endphp

<div>
    <x-index-header title="Pembayaran">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
        <x-select wire:model.live="voidedFilter" :options="[
            ['value' => '', 'label' => 'Semua'],
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'voided', 'label' => 'Dibatalkan'],
        ]" option-value="value" option-label="label" class="w-40" />
        <x-button label="Tambah" link="{{ route('payments.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">No. Bayar</th>
                    <th class="lt-th">Pedagang</th>
                    <th class="lt-th">Jenis</th>
                    <th class="lt-th text-right">Jumlah</th>
                    <th class="lt-th">Tanggal</th>
                    <th class="lt-th">Metode</th>
                    <th class="lt-th">Status</th>
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $row)
                    @php $t = $typePill[$row->dealerBill?->bill_type] ?? null; @endphp
                    <tr class="lt-row {{ $row->is_voided ? 'lt-row-danger' : '' }}">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->bill_id ?? '-' }}</td>
                        <td class="lt-td">{{ $row->dealerBill?->dealerStall?->dealer?->name ?? '-' }}</td>
                        <td class="lt-td">
                            @if($t)
                                <div class="flex flex-col items-start gap-1">
                                    <span class="lt-pill" style="background:{{ $t[1] }}; color:{{ $t[2] }};">{{ $t[0] }}</span>
                                    <span class="lt-pill" style="background:#f1f1f3; color:#52525b; font-size:11px;">{{ $freqLabel[$row->dealerBill->frequency] ?? $row->dealerBill->frequency ?? '-' }}</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="lt-td text-right">Rp {{ number_format($row->paid_amount, 2, ',', '.') }}</td>
                        <td class="lt-td">{{ $row->payment_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td">{{ ucfirst($row->payment_method) }}</td>
                        <td class="lt-td">
                            @if($row->is_voided)
                                <span class="lt-pill" style="background:#fee2e2; color:#b91c1c;">Dibatalkan</span>
                            @else
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                @if(!$row->is_voided)
                                    <a href="{{ route('payments.void', $row) }}" wire:navigate class="lt-act text-error" title="Batalkan"><x-icon name="o-x-mark" class="w-[18px] h-[18px]" /></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $payments->links() }}</div>
    </div>
</div>
