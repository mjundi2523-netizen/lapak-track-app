<x-form wire:submit="save">
    <x-input label="Judul" wire:model="title" placeholder="Mis. Gaji petugas kebersihan" required />

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
        <label class="label"><span class="label-text font-semibold">Nominal</span></label>
        <input type="text" inputmode="numeric" x-ref="amtInput" @input="onInput($event)"
            class="input input-bordered w-full" placeholder="0" required />
        <label class="label"><span class="label-text-alt text-[#9aa3b2]">Untuk mode "perlu konfirmasi", ini jadi nominal saran yang bisa diubah saat konfirmasi.</span></label>
        @error('amount')
            <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Setiap (pengali)" wire:model="interval_count" type="number" min="1" max="365" required />
        <x-select label="Frekuensi" wire:model="frequency" :options="[
            ['value' => 'daily', 'label' => 'Harian'],
            ['value' => 'weekly', 'label' => 'Mingguan'],
            ['value' => 'monthly', 'label' => 'Bulanan'],
            ['value' => 'annual', 'label' => 'Tahunan'],
        ]" option-value="value" option-label="label" required />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Tanggal Mulai" wire:model="start_date" type="date" required />
        <x-input label="Tanggal Berakhir (opsional)" wire:model="end_date" type="date"
            hint="Kosongkan bila berjalan tanpa batas. Occurrence berhenti dibuat setelah tanggal ini." />
    </div>

    <x-select label="Metode" wire:model="payment_method" :options="[
        ['value' => 'tunai', 'label' => 'Tunai'],
        ['value' => 'transfer', 'label' => 'Transfer'],
        ['value' => 'lainnya', 'label' => 'Lainnya'],
    ]" option-value="value" option-label="label" required />

    {{-- Mode pencatatan --}}
    <div class="rounded-xl p-4" style="border:1px solid #eceef2; background:#f7f8fb;">
        <x-checkbox label="Nominal tetap — catat otomatis" wire:model="auto_post"
            hint="Cocok untuk biaya tetap (gaji, sewa). Hilangkan centang untuk biaya yang nominalnya berubah (listrik, air) — tiap periode dibuat draft menunggu konfirmasi nominal aktual." />
    </div>

    @isset($showActive)
        <x-checkbox label="Aktif" wire:model="is_active"
            hint="Nonaktifkan untuk menghentikan pembuatan pengeluaran periode berikutnya." />
    @endisset

    <x-textarea label="Keterangan (opsional)" wire:model="note" rows="2" />

    <x-slot:actions>
        <x-button label="Batal" link="{{ $this->backHref('recurring-expenses.index') }}" class="btn-ghost" />
        <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
    </x-slot:actions>
</x-form>
