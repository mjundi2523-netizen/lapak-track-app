<div class="space-y-6">
    <x-header title="Dashboard" subtitle="Ringkasan lapak, pedagang, dan tagihan" separator />

    {{-- Lapak --}}
    <div>
        <h3 class="font-semibold text-base-content/70 mb-2">Lapak</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat title="Total Lapak" :value="$stallTotal" icon="o-building-storefront" />
            <x-stat title="Aktif" :value="$stallActive" icon="o-check-circle" color="text-success" />
            <x-stat title="Terisi" :value="$stallOccupied" icon="o-user" color="text-primary" />
            <x-stat title="Kosong" :value="$stallEmpty" icon="o-inbox" color="text-warning" />
        </div>
    </div>

    {{-- Pedagang --}}
    <div>
        <h3 class="font-semibold text-base-content/70 mb-2">Pedagang</h3>
        <div class="grid grid-cols-2 gap-4">
            <x-stat title="Aktif" :value="$dealerActive" icon="o-user-group" color="text-success" />
            <x-stat title="Non-aktif" :value="$dealerInactive" icon="o-user-minus" color="text-base-content/50" />
        </div>
    </div>

    {{-- Tagihan --}}
    <div>
        <h3 class="font-semibold text-base-content/70 mb-2">Tagihan</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat title="Lunas" :value="$billPaid" icon="o-banknotes" color="text-success" />
            <x-stat title="Cicilan" :value="$billInstallment" icon="o-credit-card" color="text-info" />
            <x-stat title="Belum Bayar" :value="$billUnpaid" icon="o-exclamation-triangle" color="text-error" />
            <x-stat title="Menunggu" :value="$billPending" icon="o-clock" color="text-warning" />
        </div>
    </div>

    {{-- Notifikasi jatuh tempo --}}
    <x-card title="Jatuh Tempo & Terlambat" separator>
        <x-slot:menu>
            <x-badge :value="$overdue->count() . ' tagihan'" class="badge-error" />
        </x-slot:menu>

        @if ($overdue->isEmpty())
            <div class="text-center py-8 text-base-content/50">
                <x-icon name="o-check-circle" class="w-10 h-10 mx-auto mb-2 text-success" />
                Tidak ada tagihan yang jatuh tempo. 🎉
            </div>
        @else
            <x-table :headers="[
                ['key' => 'bill_id', 'label' => 'No. Tagihan'],
                ['key' => 'dealer', 'label' => 'Pedagang'],
                ['key' => 'stall', 'label' => 'Lapak'],
                ['key' => 'total_amount', 'label' => 'Tagihan'],
                ['key' => 'due_date', 'label' => 'Jatuh Tempo'],
                ['key' => 'billing_status', 'label' => 'Status'],
            ]" :rows="$overdue" @row-click="$wire.redirect('/tagihan/' + $event.detail.dbid)">
                @scope('cell_dealer', $row)
                    {{ $row->dealerStall?->dealer?->name ?? '-' }}
                @endscope
                @scope('cell_stall', $row)
                    {{ $row->dealerStall?->stall?->block ?? '-' }}
                @endscope
                @scope('cell_total_amount', $row)
                    Rp {{ number_format($row->total_amount, 0, ',', '.') }}
                @endscope
                @scope('cell_due_date', $row)
                    {{ $row->due_date->format('d M Y') }}
                @endscope
                @scope('cell_billing_status', $row)
                    @php
                        $map = ['unpaid' => 'badge-error', 'installment' => 'badge-info', 'pending' => 'badge-warning', 'paid' => 'badge-success'];
                    @endphp
                    <x-badge :value="$row->billing_status" class="{{ $map[$row->billing_status] ?? '' }} badge-sm" />
                @endscope
            </x-table>
        @endif
    </x-card>
</div>
