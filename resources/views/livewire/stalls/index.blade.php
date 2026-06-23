<div>
    <x-header title="Lapak" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari blok..." wire:model.live.debounce="search" clearable />
            <x-button label="Tambah" link="{{ route('stalls.create') }}" class="btn-primary" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'block', 'label' => 'Blok'],
            ['key' => 'description', 'label' => 'Deskripsi'],
            ['key' => 'payment_term', 'label' => 'Aturan Bayar'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$stalls" striped>
            @scope('cell_payment_term', $row)
                {{ $row->paymentTerm?->term_name ?? '-' }}
            @endscope

            @scope('cell_is_active', $row)
                <x-badge :value="$row->is_active ? 'Aktif' : 'Nonaktif'" :class="$row->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('cell_actions', $row)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('stalls.edit', $row) }}" class="btn-sm btn-ghost" />
                    <x-button
                        :icon="$row->is_active ? 'o-eye-slash' : 'o-eye'"
                        wire:click="toggleActive({{ $row->sid }})"
                        class="btn-sm btn-ghost"
                    />
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $stalls->links() }}
        </div>
    </x-card>
</div>
