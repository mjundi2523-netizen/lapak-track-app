<div>
    <x-header title="Biaya Lain-lain" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
            <x-button label="Tambah" link="{{ route('add-ons.create') }}" class="btn-primary" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'add_on', 'label' => 'Nama'],
            ['key' => 'frequency', 'label' => 'Frekuensi'],
            ['key' => 'price', 'label' => 'Harga'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$addOns" striped>
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
                    <x-button icon="o-pencil" link="{{ route('add-ons.edit', $row) }}" class="btn-sm btn-ghost" />
                    <x-button icon="o-trash" wire:click="delete({{ $row->aoid }})" wire:confirm="Yakin ingin menghapus?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $addOns->links() }}
        </div>
    </x-card>
</div>
