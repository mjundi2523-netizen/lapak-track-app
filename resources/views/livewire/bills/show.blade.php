<div>
    <x-header title="Detail Tagihan" separator>
        <x-slot:actions>
            <x-button label="Kembali" link="{{ route('bills.index') }}" class="btn-ghost" icon="o-arrow-left" />
            <x-button label="Hitung Ulang" wire:click="recalculate" class="btn-info" icon="o-arrow-path" spinner />
        </x-slot:actions>
    </x-header>

    @php
        $paidTotal = $dealerBill->payments->where('is_voided', false)->sum('paid_amount');
        $remaining = $dealerBill->total_amount - $paidTotal;
    @endphp

    <x-card title="Informasi Tagihan">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><span class="font-semibold">No. Tagihan:</span> {{ $dealerBill->bill_id ?? '-' }}</div>
            <div><span class="font-semibold">Pedagang:</span> {{ $dealerBill->dealerStall?->dealer?->name ?? '-' }}</div>
            <div><span class="font-semibold">Lapak:</span> {{ $dealerBill->dealerStall?->stall?->block ?? '-' }}</div>
            <div><span class="font-semibold">Jumlah:</span> Rp {{ number_format($dealerBill->total_amount, 0, ',', '.') }}</div>
            <div><span class="font-semibold">Jatuh Tempo:</span> {{ $dealerBill->due_date?->format('d-m-Y') ?? '-' }}</div>
            <div>
                <span class="font-semibold">Status:</span>
                <x-badge :value="match($dealerBill->billing_status) {
                    'paid' => 'Lunas', 'installment' => 'Cicilan', 'unpaid' => 'Belum Bayar', 'pending' => 'Pending', default => $dealerBill->billing_status
                }" :class="match($dealerBill->billing_status) {
                    'paid' => 'badge-success', 'installment' => 'badge-warning', 'unpaid' => 'badge-error', 'pending' => 'badge-ghost', default => 'badge-ghost'
                }" />
            </div>
            <div><span class="font-semibold">Terbayar:</span> Rp {{ number_format($paidTotal, 2, ',', '.') }}</div>
            <div><span class="font-semibold">Sisa:</span> Rp {{ number_format($remaining, 2, ',', '.') }}</div>
            <div><span class="font-semibold">Periode:</span> {{ $dealerBill->period_start?->format('d-m-Y') }} s/d {{ $dealerBill->period_end?->format('d-m-Y') }}</div>
        </div>
    </x-card>

    @php
        $breakdown = $dealerBill->breakdown();
        $breakdownTotal = collect($breakdown)->sum('amount');
    @endphp

    <x-card title="Rincian Tagihan" class="mt-4">
        @if(count($breakdown) > 0)
            <x-table :headers="[
                ['key' => 'label', 'label' => 'Komponen'],
                ['key' => 'amount', 'label' => 'Jumlah', 'class' => 'text-right'],
            ]" :rows="$breakdown">
                @scope('cell_amount', $row)
                    <div class="text-right">Rp {{ number_format($row['amount'], 0, ',', '.') }}</div>
                @endscope
            </x-table>

            <div class="flex justify-between border-t mt-2 pt-2 font-semibold">
                <span>Total</span>
                <span>Rp {{ number_format($breakdownTotal, 0, ',', '.') }}</span>
            </div>

            @if($breakdownTotal !== (int) $dealerBill->total_amount)
                <x-alert icon="o-exclamation-triangle" class="alert-warning mt-3">
                    Rincian dihitung dari konfigurasi sewa/add-on saat ini dan tidak cocok dengan
                    total tersimpan (Rp {{ number_format($dealerBill->total_amount, 0, ',', '.') }}).
                    Kemungkinan konfigurasi lapak berubah setelah tagihan dibuat.
                </x-alert>
            @endif
        @else
            <p class="text-base-content/60">Rincian tidak tersedia untuk tagihan ini.</p>
        @endif
    </x-card>

    <x-card title="Riwayat Pembayaran" class="mt-4">
        @if($dealerBill->payments->count() > 0)
            <x-table :headers="[
                ['key' => 'bill_id', 'label' => 'No. Bayar'],
                ['key' => 'paid_amount', 'label' => 'Jumlah'],
                ['key' => 'payment_date', 'label' => 'Tanggal'],
                ['key' => 'payment_method', 'label' => 'Metode'],
                ['key' => 'status', 'label' => 'Status'],
            ]" :rows="$dealerBill->payments" striped
            :row-decoration="[
                'bg-error/20' => fn($row) => $row->is_voided,
            ]">
                @scope('cell_paid_amount', $row)
                    Rp {{ number_format($row->paid_amount, 2, ',', '.') }}
                @endscope

                @scope('cell_payment_method', $row)
                    {{ ucfirst($row->payment_method) }}
                @endscope

                @scope('cell_status', $row)
                    @if($row->is_voided)
                        <x-badge value="Dibatalkan" class="badge-error" />
                        <span class="text-xs text-base-content/60">{{ $row->voided_reason }}</span>
                    @else
                        <x-badge value="Aktif" class="badge-success" />
                    @endif
                @endscope
            </x-table>
        @else
            <p class="text-base-content/60">Belum ada pembayaran.</p>
        @endif
    </x-card>
</div>
