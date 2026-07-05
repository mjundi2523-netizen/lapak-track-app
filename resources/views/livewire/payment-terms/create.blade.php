<div>
    <x-page-heading title="Tambah Aturan Bayar" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <x-input label="Nama Aturan" wire:model="term_name" placeholder="Mis. Sewa Bulanan" required />

            <x-select label="Frekuensi" wire:model="frequency" :options="[
                ['value' => 'daily', 'label' => 'Harian'],
                ['value' => 'weekly', 'label' => 'Mingguan'],
                ['value' => 'monthly', 'label' => 'Bulanan'],
                ['value' => 'annual', 'label' => 'Tahunan'],
            ]" option-value="value" option-label="label" required />

            <x-input label="Setiap (interval)" wire:model="interval_count" type="number" min="1"
                hint="Mis. Bulanan + 3 = ditagih tiap 3 bulan" required />

            <div x-data="{
                    fmt(v) {
                        let s = String(Math.round(Number(v) || 0));
                        return s === '0' ? '' : s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    },
                    onInput(e) {
                        let raw = e.target.value.replace(/\D/g, '');
                        e.target.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                        $wire.set('price', raw ? Number(raw) : 0);
                    },
                    init() {
                        let v = $wire.price;
                        if (v) this.$refs.priceInput.value = this.fmt(v);
                    }
                }">
                <label class="label"><span class="label-text font-semibold">Harga</span></label>
                <input type="text" inputmode="numeric" x-ref="priceInput" @input="onInput($event)"
                    class="input input-bordered w-full" placeholder="0" required />
                @error('price')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
            </div>

            <x-checkbox label="Untuk pedagang baru" wire:model.live="cond_new"
                hint="Aturan ini khusus pedagang baru." />
            <div class="flex items-start gap-1.5">
                <x-checkbox label="Untuk pedagang eksternal" wire:model.live="cond_external"
                    hint="Aturan ini khusus pedagang eksternal (tukang gerobak/keliling). Tanpa dicentang = pedagang reguler." />
                @unless(auth()->user()->isPremium())
                    <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full text-[11px] font-semibold" style="background:#fef3c7; color:#b45309;">
                        <x-icon name="s-lock-closed" class="w-3 h-3" /> Premium
                    </span>
                @endunless
            </div>

            <x-slot:actions>
                <x-button label="Batal" link="{{ $this->backHref('payment-terms.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
