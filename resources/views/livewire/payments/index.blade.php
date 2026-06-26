<div>
    <x-header title="Pembayaran" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
            <x-select wire:model.live="voidedFilter" :options="[
                ['value' => '', 'label' => 'Semua'],
                ['value' => 'active', 'label' => 'Aktif'],
                ['value' => 'voided', 'label' => 'Dibatalkan'],
            ]" option-value="value" option-label="label" class="w-40" />
            <x-button label="Tambah" link="{{ route('payments.create') }}" class="btn-primary" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'bill_id', 'label' => 'No. Bayar'],
            ['key' => 'dealer', 'label' => 'Pedagang'],
            ['key' => 'jenis', 'label' => 'Jenis'],
            ['key' => 'paid_amount', 'label' => 'Jumlah'],
            ['key' => 'payment_date', 'label' => 'Tanggal'],
            ['key' => 'payment_method', 'label' => 'Metode'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$payments" striped
        :row-decoration="[
            'bg-error/20' => fn($row) => $row->is_voided,
        ]">
            @scope('cell_bill_id', $row)
                {{ $row->bill_id ?? '-' }}
            @endscope

            @scope('cell_dealer', $row)
                {{ $row->dealerBill?->dealerStall?->dealer?->name ?? '-' }}
            @endscope

            @scope('cell_jenis', $row)
                @if($row->dealerBill)
                    <div class="flex flex-col gap-1">
                        <x-badge :value="match($row->dealerBill->bill_type) {
                            'MTR' => 'Sewa',
                            'MAT' => 'Sewa + Add-on',
                            'AAT' => 'Add-on',
                            'ATR' => 'Add-on (jadwal)',
                            default => $row->dealerBill->bill_type,
                        }" :class="match($row->dealerBill->bill_type) {
                            'MTR' => 'badge-primary badge-soft',
                            'MAT' => 'badge-secondary badge-soft',
                            'AAT' => 'badge-info badge-soft',
                            'ATR' => 'badge-accent badge-soft',
                            default => 'badge-ghost',
                        }" />
                        <x-badge :value="match($row->dealerBill->frequency) {
                            'daily' => 'Harian',
                            'weekly' => 'Mingguan',
                            'monthly' => 'Bulanan',
                            'annual' => 'Tahunan',
                            default => $row->dealerBill->frequency ?? '-',
                        }" class="badge-ghost badge-sm" />
                    </div>
                @else
                    -
                @endif
            @endscope

            @scope('cell_paid_amount', $row)
                Rp {{ number_format($row->paid_amount, 2, ',', '.') }}
            @endscope

            @scope('cell_payment_method', $row)
                {{ ucfirst($row->payment_method) }}
            @endscope

            @scope('cell_status', $row)
                @if($row->is_voided)
                    <x-badge value="Dibatalkan" class="badge-error" />
                @else
                    <x-badge value="Aktif" class="badge-success" />
                @endif
            @endscope

            @scope('cell_actions', $row)
                @if(!$row->is_voided)
                    <x-button icon="o-x-mark" link="{{ route('payments.void', $row) }}" class="btn-sm btn-ghost text-error" />
                @endif
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </x-card>
</div>
