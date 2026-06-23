<div>
    <x-header title="Registrasi Pedagang" separator />

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

            <hr class="my-4" />

            <h3 class="font-bold text-lg mb-2">Lapak</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="Pilih Lapak" wire:model="selected_stall" :options="$stalls->map(fn($s) => ['value' => $s->sid, 'label' => $s->block])->toArray()" option-value="value" option-label="label" required />
                <x-input label="Tanggal Mulai Sewa" wire:model="rent_start_date" type="date" required />
                <x-input label="Tanggal Akhir Sewa" wire:model="rent_end_date" type="date" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('dealers.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
