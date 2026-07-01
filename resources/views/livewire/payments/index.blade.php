@php
    $typePill = [
        'MTR' => ['Sewa',          '#ede9fe', '#6d28d9'],
        'MAT' => ['Sewa + Add-on', '#fce7f3', '#be185d'],
        'AAT' => ['Add-on',        '#dbeafe', '#1d4ed8'],
        'ATR' => ['Add-on (jadwal)','#cffafe', '#0e7490'],
        'EXT' => ['Eksternal',      '#fae8ff', '#86198f'],
    ];
    $freqLabel = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan'];
@endphp

<div>
    <x-index-header title="Pembayaran">
        <x-button label="Tambah" link="{{ route('payments.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    {{-- Segmen filter --}}
    <div class="bg-white rounded-2xl p-4 sm:p-5 mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="flex items-center justify-between gap-3 mb-3.5">
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1b2433]">
                <x-icon name="o-funnel" class="w-4 h-4 text-[#9aa3b2]" /> Filter
            </div>
            <button type="button" wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#71717a] hover:text-[#dc2626] transition">
                <x-icon name="o-arrow-path" class="w-3.5 h-3.5" /> Reset
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
            <x-input label="Cari" placeholder="No. bayar / nama pedagang" wire:model.live.debounce="search" clearable />
            <x-choices label="Pedagang" wire:model.live="dealerId" :options="$dealersList" option-label="name" option-value="did"
                search-function="searchDealer" placeholder="Semua pedagang" single searchable clearable />
            <x-select label="Frekuensi" wire:model.live="frequencyFilter" :options="[
                ['value' => '', 'label' => 'Semua Frekuensi'],
                ['value' => 'daily', 'label' => 'Harian'],
                ['value' => 'weekly', 'label' => 'Mingguan'],
                ['value' => 'monthly', 'label' => 'Bulanan'],
                ['value' => 'annual', 'label' => 'Tahunan'],
            ]" option-value="value" option-label="label" />
            <x-select label="Status" wire:model.live="voidedFilter" :options="[
                ['value' => '', 'label' => 'Semua Status'],
                ['value' => 'active', 'label' => 'Aktif'],
                ['value' => 'voided', 'label' => 'Dibatalkan'],
            ]" option-value="value" option-label="label" />
            <x-input label="Tanggal Bayar Dari" type="date" wire:model.live="from" />
            <x-input label="Tanggal Bayar Sampai" type="date" wire:model.live="to" />
        </div>
    </div>

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
                        <td class="lt-td">{{ $row->dealerBill?->holder?->name ?? '-' }}</td>
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
                                <a href="{{ route('payments.show', $row) }}" wire:navigate class="lt-act" title="Detail"><x-icon name="o-eye" class="w-[18px] h-[18px]" /></a>
                                @if(!$row->is_voided)
                                    <button type="button" wire:click="openReceipt({{ $row->dpid }})" class="lt-act text-primary" title="Cetak Kwitansi"><x-icon name="o-printer" class="w-[18px] h-[18px]" /></button>
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

    {{-- Modal Kwitansi (preview + cetak) --}}
    @if($showReceipt && $receiptPayment)
        @include('payments._receipt-modal', ['payment' => $receiptPayment])
    @endif
</div>
