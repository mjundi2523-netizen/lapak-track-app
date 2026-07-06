<div>
    <x-header title="Batalkan Pembayaran" separator />

    <x-card>
        <div class="mb-4 bg-warning/10 border border-warning/30 rounded-lg p-4">
            <h3 class="font-semibold text-warning">Peringatan</h3>
            <p class="text-sm">Anda akan membatalkan pembayaran ini. Tindakan ini tidak dapat dibatalkan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div><span class="font-semibold">No. Bayar:</span> {{ $payment->bill_id ?? '-' }}</div>
            <div><span class="font-semibold">Pedagang:</span> {{ $payment->dealerBill?->holder?->name ?? '-' }}</div>
            <div><span class="font-semibold">Jumlah:</span> Rp {{ number_format($payment->paid_amount, 2, ',', '.') }}</div>
            <div><span class="font-semibold">Tanggal:</span> {{ $payment->payment_date?->format('d-m-Y') ?? '-' }}</div>
            <div><span class="font-semibold">Metode:</span> {{ ucfirst($payment->payment_method) }}</div>
            <div><span class="font-semibold">Lapak:</span> {{ $payment->dealerBill?->location_label }}</div>
        </div>

        <x-form wire:submit="void" wire:confirm="Batalkan pembayaran ini? Tindakan tidak dapat diurungkan.">
            <x-input label="Alasan Pembatalan" wire:model="voided_reason" required />

            <x-slot:actions>
                <x-button label="Batal" wire:click="cancel" class="btn-ghost" />
                <x-button label="Batalkan Pembayaran" type="submit" class="btn-error" spinner="void" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
