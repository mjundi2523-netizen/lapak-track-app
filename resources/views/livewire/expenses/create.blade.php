<div>
    <x-page-heading title="Catat Pengeluaran" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <x-input label="Judul" wire:model="title" placeholder="Mis. Gaji petugas kebersihan Juni" required />

            <x-select label="Kategori" wire:model="ecid"
                :options="$categories->map(fn($c) => ['id' => $c->ecid, 'name' => $c->name])"
                option-value="id" option-label="name"
                placeholder="{{ $categories->isEmpty() ? 'Belum ada kategori — buat dulu di Kategori Pengeluaran' : 'Pilih kategori' }}" required />

            <div x-data="{
                    fmt(v) {
                        let s = String(Math.round(Number(v) || 0));
                        return s === '0' ? '' : s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    },
                    onInput(e) {
                        let raw = e.target.value.replace(/\D/g, '');
                        e.target.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                        $wire.set('amount', raw ? Number(raw) : 0);
                    },
                    init() {
                        let v = $wire.amount;
                        if (v) this.$refs.amtInput.value = this.fmt(v);
                    }
                }">
                <label class="label"><span class="label-text font-semibold">Jumlah</span></label>
                <input type="text" inputmode="numeric" x-ref="amtInput" @input="onInput($event)"
                    class="input input-bordered w-full" placeholder="0" required />
                @error('amount')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Tanggal" wire:model="expense_date" type="date" :max="now()->format('Y-m-d')" required />
                <x-select label="Metode" wire:model="payment_method" :options="[
                    ['value' => 'tunai', 'label' => 'Tunai'],
                    ['value' => 'transfer', 'label' => 'Transfer'],
                    ['value' => 'lainnya', 'label' => 'Lainnya'],
                ]" option-value="value" option-label="label" required />
            </div>

            <x-textarea label="Keterangan (opsional)" wire:model="note" rows="2" />

            <x-slot:actions>
                <x-button label="Batal" link="{{ $this->backHref('expenses.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
