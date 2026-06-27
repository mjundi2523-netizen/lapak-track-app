<div>
    <x-page-heading title="Tambah Biaya Lain-lain" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <x-input label="Nama Biaya" wire:model="add_on" required />
            <x-input label="Harga" wire:model="price" type="number" required />
            <x-select label="Frekuensi" wire:model="frequency" :options="[
                ['value' => 'daily', 'label' => 'Harian'],
                ['value' => 'weekly', 'label' => 'Mingguan'],
                ['value' => 'monthly', 'label' => 'Bulanan'],
                ['value' => 'annual', 'label' => 'Tahunan'],
            ]" option-value="value" option-label="label" required />

            <x-checkbox label="Ikut tanggal sewa"
                hint="Penagihan mengikuti tanggal mulai sewa lapak. Lepas centang untuk atur tanggal mulai sendiri."
                wire:model.live="is_rent_date" />

            @if(! $is_rent_date)
                <x-input label="Tanggal Mulai Penagihan" wire:model="start_date" type="date" required
                    hint="Add-on ditagih mulai tanggal ini, lalu berulang sesuai frekuensi." />
            @endif

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('add-ons.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
