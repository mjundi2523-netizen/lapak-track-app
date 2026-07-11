<div>
    <x-page-heading title="Tambah Lapak" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Blok" wire:model="block" required placeholder="Contoh: A01" hint="1 huruf + 2 angka" />
                <x-input label="Nomor" wire:model="number" required placeholder="Contoh: 05" hint="2 angka" />
            </div>
            <x-input label="Ukuran" wire:model="size" placeholder="Contoh: 3x4 m" />
            <x-input label="Deskripsi" wire:model="description" placeholder="Catatan tambahan (opsional)" />

            <x-choices-offline
                label="Aturan Bayar Sewa"
                wire:model="selectedPaymentTerms"
                :options="$paymentTerms"
                option-value="ptid"
                option-label="name"
                option-sub-label="sub"
                searchable
                clearable
                hint="Pilih satu atau lebih. Pedagang memilih salah satu saat menyewa lapak ini." />

            <x-choices-offline
                label="Biaya Tambahan"
                wire:model="selectedAddOns"
                :options="$addOns"
                option-value="aoid"
                option-label="name"
                option-sub-label="sub"
                searchable
                clearable
                hint="Opsional. Biaya tambahan yang melekat pada lapak." />

            <x-slot:actions>
                <x-button label="Batal" link="{{ $this->backHref('stalls.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
