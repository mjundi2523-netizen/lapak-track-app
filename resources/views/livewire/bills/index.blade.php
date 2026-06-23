<div>
    <x-header title="Tagihan" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
            <x-select wire:model.live="statusFilter" :options="[
                ['value' => '', 'label' => 'Semua Status'],
                ['value' => 'unpaid', 'label' => 'Belum Bayar'],
                ['value' => 'installment', 'label' => 'Cicilan'],
                ['value' => 'paid', 'label' => 'Lunas'],
                ['value' => 'pending', 'label' => 'Pending'],
            ]" option-value="value" option-label="label" class="w-40" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'bill_id', 'label' => 'No. Tagihan'],
            ['key' => 'dealer', 'label' => 'Pedagang'],
            ['key' => 'stall', 'label' => 'Lapak'],
            ['key' => 'total_amount', 'label' => 'Jumlah'],
            ['key' => 'paid', 'label' => 'Terbayar'],
            ['key' => 'due_date', 'label' => 'Jatuh Tempo'],
            ['key' => 'billing_status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$bills" striped
        :row-decoration="[
            'bg-error/10' => fn($row) => $row->billing_status === 'unpaid' && $row->due_date < now(),
        ]">
            @scope('cell_bill_id', $row)
                {{ $row->bill_id ?? '-' }}
            @endscope

            @scope('cell_dealer', $row)
                {{ $row->dealerStall?->dealer?->name ?? '-' }}
            @endscope

            @scope('cell_stall', $row)
                {{ $row->dealerStall?->stall?->block ?? '-' }}
            @endscope

            @scope('cell_total_amount', $row)
                Rp {{ number_format($row->total_amount, 0, ',', '.') }}
            @endscope

            @scope('cell_paid', $row)
                Rp {{ number_format($paidTotals[$row->dbid] ?? 0, 0, ',', '.') }}
            @endscope

            @scope('cell_billing_status', $row)
                <x-badge :value="match($row->billing_status) {
                    'paid' => 'Lunas',
                    'installment' => 'Cicilan',
                    'unpaid' => 'Belum Bayar',
                    'pending' => 'Pending',
                    default => $row->billing_status,
                }" :class="match($row->billing_status) {
                    'paid' => 'badge-success',
                    'installment' => 'badge-warning',
                    'unpaid' => 'badge-error',
                    'pending' => 'badge-ghost',
                    default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_actions', $row)
                <div class="flex gap-1">
                    <x-button icon="o-eye" link="{{ route('bills.show', $row) }}" class="btn-sm btn-ghost" />
                    @if($row->billing_status !== 'paid')
                        <x-button icon="o-credit-card" link="{{ route('payments.create', ['bill' => $row->dbid]) }}" class="btn-sm btn-ghost text-success" />
                    @endif
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $bills->links() }}
        </div>
    </x-card>
</div>
