<div>
    <x-page-heading title="Tambah Kategori Pengeluaran" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            <x-input label="Nama Kategori" wire:model="name" placeholder="Mis. Gaji, Listrik, Kebersihan" required />

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('expense-categories.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
