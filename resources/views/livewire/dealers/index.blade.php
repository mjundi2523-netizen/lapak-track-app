<div>
    <x-header title="Pedagang" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Cari nama/NIK..." wire:model.live.debounce="search" clearable />
            <x-button label="Tambah" link="{{ route('dealers.create') }}" class="btn-primary" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'nik', 'label' => 'NIK'],
            ['key' => 'name', 'label' => 'Nama'],
            ['key' => 'phone_number_1', 'label' => 'Telepon'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$dealers" striped>
            @scope('cell_status', $row)
                <x-badge :value="$row->status === 'active' ? 'Aktif' : 'Nonaktif'" :class="$row->status === 'active' ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('cell_actions', $row)
                <div class="flex gap-1">
                    <x-button icon="o-eye" link="{{ route('dealers.show', $row) }}" class="btn-sm btn-ghost" />
                    <x-button icon="o-pencil" link="{{ route('dealers.edit', $row) }}" class="btn-sm btn-ghost" />
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $dealers->links() }}
        </div>
    </x-card>
</div>
