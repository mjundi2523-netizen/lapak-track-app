<div>
    <x-page-heading title="Edit Aturan Bayar" />

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

            <x-input label="Harga" wire:model="price" type="number" required />

            <x-checkbox label="Untuk pedagang baru" wire:model.live="cond_new"
                hint="Aturan ini khusus pedagang baru." />
            <x-checkbox label="Untuk pedagang eksternal" wire:model.live="cond_external"
                hint="Aturan ini khusus pedagang eksternal (tukang gerobak/keliling). Tanpa dicentang = pedagang reguler." />

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('payment-terms.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
