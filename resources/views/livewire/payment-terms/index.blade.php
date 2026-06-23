<div>
    <x-header title="Aturan Bayar" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
            <x-button label="Tambah" link="{{ route('payment-terms.create') }}" class="btn-primary" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'term_name', 'label' => 'Nama'],
            ['key' => 'frequency', 'label' => 'Frekuensi'],
            ['key' => 'price', 'label' => 'Harga'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$paymentTerms" striped>
            @scope('cell_frequency', $row)
                <x-badge :value="match($row->frequency) {
                    'daily' => 'Harian',
                    'weekly' => 'Mingguan',
                    'monthly' => 'Bulanan',
                    'annual' => 'Tahunan',
                    default => $row->frequency,
                }" class="badge-info" />
            @endscope

            @scope('cell_price', $row)
                Rp {{ number_format($row->price, 0, ',', '.') }}
            @endscope

            @scope('cell_actions', $row)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('payment-terms.edit', $row) }}" class="btn-sm btn-ghost" />
                    <x-button icon="o-trash" wire:click="delete({{ $row->ptid }})" wire:confirm="Yakin ingin menghapus?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $paymentTerms->links() }}
        </div>
    </x-card>
</div>
