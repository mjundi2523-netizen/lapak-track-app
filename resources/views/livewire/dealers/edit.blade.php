<div>
    <x-header title="Edit Pedagang" separator />

    <x-card>
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="NIK" wire:model="nik" required />
                <x-input label="Nama" wire:model="name" required />
                <x-input label="Tanggal Lahir" wire:model="birth_date" type="date" required />
                <x-input label="Alamat" wire:model="address" required />
                <x-input label="No. Telepon 1" wire:model="phone_number_1" required />
                <x-input label="No. Telepon 2" wire:model="phone_number_2" />
                <x-input label="Jenis Dagangan" wire:model="product_type" />
                <x-select label="Status" wire:model="status" :options="[
                    ['value' => 'active', 'label' => 'Aktif'],
                    ['value' => 'inactive', 'label' => 'Nonaktif'],
                ]" option-value="value" option-label="label" />
            </div>

            <x-input label="Scan KTP" wire:model="scan_id_file" type="file" accept="image/*,.pdf" />

            @if($scan_id)
                <p class="text-sm text-base-content/60 mt-1">File saat ini: {{ basename($scan_id) }}</p>
            @endif

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('dealers.show', $dealer) }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
