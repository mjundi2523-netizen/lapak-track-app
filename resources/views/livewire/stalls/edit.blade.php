<div>
    <x-page-heading title="Edit Lapak" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Blok" wire:model="block" required placeholder="Contoh: A01" hint="1 huruf + 2 angka" />
                <x-input label="Nomor" wire:model="number" required placeholder="Contoh: 05" hint="2 angka" />
            </div>
            <x-input label="Ukuran" wire:model="size" placeholder="Contoh: 3x4 m" />
            <x-input label="Deskripsi" wire:model="description" placeholder="Catatan tambahan (opsional)" />

            <x-select label="Aturan Bayar" wire:model="ptid" :options="$paymentTerms->map(fn($pt) => ['value' => $pt->ptid, 'label' => $pt->term_name])->toArray()" option-value="value" option-label="label" placeholder="Pilih aturan bayar" />

            <x-checkbox label="Aktif" wire:model="is_active" />

            <div>
                <label class="label"><span class="label-text mb-4">Biaya Lain-lain</span></label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($addOns as $ao)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" value="{{ $ao->aoid }}" wire:model="selectedAddOns" class="checkbox checkbox-sm" />
                            <span>{{ $ao->add_on }} (Rp {{ number_format($ao->price, 0, ',', '.') }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('stalls.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
