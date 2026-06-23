<div>
    <x-header title="Tambah Biaya Lain-lain" separator />

    <x-card>
        <x-form wire:submit="save">
            <x-input label="Nama Biaya" wire:model="add_on" required />
            <x-input label="Harga" wire:model="price" type="number" required />
            <x-select label="Frekuensi" wire:model="frequency" :options="[
                ['value' => 'daily', 'label' => 'Harian'],
                ['value' => 'weekly', 'label' => 'Mingguan'],
                ['value' => 'monthly', 'label' => 'Bulanan'],
                ['value' => 'annual', 'label' => 'Tahunan'],
            ]" option-value="value" option-label="label" required />

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('add-ons.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
