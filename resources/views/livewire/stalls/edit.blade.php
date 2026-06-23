<div>
    <x-header title="Edit Lapak" separator />

    <x-card>
        <x-form wire:submit="save">
            <x-input label="Blok" wire:model="block" required />
            <x-input label="Deskripsi" wire:model="description" />

            <x-select label="Aturan Bayar" wire:model="ptid" :options="$paymentTerms->map(fn($pt) => ['value' => $pt->ptid, 'label' => $pt->term_name])->toArray()" option-value="value" option-label="label" placeholder="Pilih aturan bayar" />

            <x-checkbox label="Aktif" wire:model="is_active" />

            <div>
                <label class="label"><span class="label-text">Biaya Lain-lain</span></label>
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
