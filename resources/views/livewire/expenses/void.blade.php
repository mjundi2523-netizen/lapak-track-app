<div>
    <x-header title="Batalkan Pengeluaran" separator />

    <x-card>
        <div class="mb-4 bg-warning/10 border border-warning/30 rounded-lg p-4">
            <h3 class="font-semibold text-warning">Peringatan</h3>
            <p class="text-sm">Anda akan membatalkan pengeluaran ini. Data tetap tersimpan (dengan jejak), tapi tidak lagi dihitung.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div><span class="font-semibold">Judul:</span> {{ $expense->title }}</div>
            <div><span class="font-semibold">Kategori:</span> {{ $expense->category?->name ?? '-' }}</div>
            <div><span class="font-semibold">Jumlah:</span> Rp {{ number_format($expense->amount, 0, ',', '.') }}</div>
            <div><span class="font-semibold">Tanggal:</span> {{ $expense->expense_date?->format('d-m-Y') ?? '-' }}</div>
            <div><span class="font-semibold">Metode:</span> {{ ucfirst($expense->payment_method) }}</div>
        </div>

        <x-form wire:submit="void">
            <x-input label="Alasan Pembatalan" wire:model="voided_reason" required />

            <x-slot:actions>
                <x-button label="Batal" wire:click="cancel" class="btn-ghost" />
                <x-button label="Batalkan Pengeluaran" type="submit" class="btn-error" spinner="void" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
