@php
    $rp0 = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $rp2 = fn ($v) => 'Rp ' . number_format((float) $v, 2, ',', '.');
    $typePill = [
        'MTR' => ['Sewa',          '#ede9fe', '#6d28d9'],
        'MAT' => ['Sewa + Add-on', '#fce7f3', '#be185d'],
        'AAT' => ['Add-on',        '#dbeafe', '#1d4ed8'],
        'ATR' => ['Add-on (jadwal)','#cffafe', '#0e7490'],
        'EXT' => ['Eksternal',      '#fae8ff', '#86198f'],
    ];
    $freqLabel = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan'];
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
        'cancelled'   => ['Dibatalkan',  '#f1f1f3', '#52525b'],
    ];

    $bill = $payment->dealerBill;
    $t = $typePill[$bill?->bill_type] ?? null;
    $bst = $statusMap[$bill?->billing_status] ?? [$bill?->billing_status, '#f1f1f3', '#52525b'];
    $billPaid = $bill ? $bill->payments->sum('paid_amount') : 0;
    $billRemaining = $bill ? max($bill->total_amount - $billPaid, 0) : 0;
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Detail Pembayaran</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Pembayaran&nbsp;/&nbsp;{{ $payment->bill_id ?? $payment->dpid }}</div>
        </div>
        <div class="flex gap-2.5">
            @unless($payment->is_voided)
                <button type="button" wire:click="openReceipt"
                        class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95"
                        style="background:var(--lt-p);">
                    <x-icon name="o-printer" class="w-4 h-4" /> Cetak Kwitansi
                </button>
            @endunless
            <a href="{{ route('payments.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-medium text-[#3f3f46] bg-white transition hover:bg-base-200"
               style="border:1px solid #e5e7eb;">
                <x-icon name="o-arrow-left" class="w-4 h-4" /> Kembali
            </a>
        </div>
    </div>

    {{-- Informasi Pembayaran --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Informasi Pembayaran</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-[18px] p-6 text-sm">
            <div><span class="font-semibold">No. Bayar:</span> {{ $payment->bill_id ?? '-' }}</div>
            <div><span class="font-semibold">Jumlah Dibayar:</span> {{ $rp2($payment->paid_amount) }}</div>
            <div><span class="font-semibold">Tanggal Bayar:</span> {{ $payment->payment_date?->format('d-m-Y') ?? '-' }}</div>
            <div><span class="font-semibold">Metode:</span> {{ ucfirst($payment->payment_method) }}</div>
            <div>
                <span class="font-semibold">Status:</span>
                @if($payment->is_voided)
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#b91c1c;">Dibatalkan</span>
                @else
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7; color:#15803d;">Aktif</span>
                @endif
            </div>
            <div><span class="font-semibold">Dicatat oleh:</span> {{ $payment->createdBy?->name ?? '-' }}</div>
            <div><span class="font-semibold">Dicatat pada:</span> {{ $payment->created_at?->format('d-m-Y H:i') ?? '-' }}</div>
        </div>

        @if($payment->is_voided)
            <div class="mx-6 mb-6 rounded-[10px] p-4 text-sm" style="background:#fef2f2; border:1px solid #fecaca; color:#7f1d1d;">
                <div class="font-semibold mb-1">Pembayaran Dibatalkan</div>
                <div><span class="font-semibold">Alasan:</span> {{ $payment->voided_reason ?: '-' }}</div>
                <div><span class="font-semibold">Oleh:</span> {{ $payment->voidedBy?->name ?? '-' }}
                    @if($payment->voided_at) · {{ $payment->voided_at->format('d-m-Y H:i') }} @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Tagihan Terkait --}}
    <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid #eef0f4;">
            <span class="text-base font-bold text-[#1b2433]">Tagihan Terkait</span>
            @if($bill)
                <a href="{{ route('bills.show', $bill) }}" wire:navigate class="text-sm font-semibold" style="color:var(--lt-p);">Lihat Tagihan →</a>
            @endif
        </div>

        @if($bill)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-[18px] p-6 text-sm">
                <div><span class="font-semibold">No. Tagihan:</span> {{ $bill->bill_id ?? '-' }}</div>
                <div>
                    <span class="font-semibold">Jenis:</span>
                    @if($t)
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $t[1] }}; color:{{ $t[2] }};">{{ $t[0] }}</span>
                        <span class="text-[#71717a]">· {{ $freqLabel[$bill->frequency] ?? $bill->frequency ?? '-' }}</span>
                    @else
                        -
                    @endif
                </div>
                <div><span class="font-semibold">Status Tagihan:</span>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $bst[1] }}; color:{{ $bst[2] }};">{{ $bst[0] }}</span>
                </div>
                <div><span class="font-semibold">Pedagang:</span> {{ $bill->holder?->name ?? '-' }}</div>
                <div><span class="font-semibold">Lapak:</span> {{ $bill->location_label }}</div>
                <div><span class="font-semibold">Periode:</span> {{ $bill->period_start?->format('d-m-Y') }} s/d {{ $bill->period_end?->format('d-m-Y') }}</div>
                <div><span class="font-semibold">Total Tagihan:</span> {{ $rp0($bill->total_amount) }}</div>
                <div><span class="font-semibold">Terbayar (tagihan):</span> {{ $rp2($billPaid) }}</div>
                <div><span class="font-semibold">Sisa:</span> {{ $rp2($billRemaining) }}</div>
            </div>
        @else
            <p class="px-6 py-6 text-sm text-[#71717a] m-0">Tagihan terkait tidak ditemukan.</p>
        @endif
    </div>

    {{-- Modal Kwitansi --}}
    @if($showReceipt && ! $payment->is_voided)
        @include('payments._receipt-modal', ['payment' => $payment])
    @endif
</div>
