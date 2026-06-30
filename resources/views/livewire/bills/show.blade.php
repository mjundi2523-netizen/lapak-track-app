@php
    $rp0 = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $rp2 = fn ($v) => 'Rp ' . number_format((float) $v, 2, ',', '.');
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
    ];
    $paidTotal = $dealerBill->payments->where('is_voided', false)->sum('paid_amount');
    $remaining = $dealerBill->total_amount - $paidTotal;
    $breakdown = $dealerBill->breakdown();
    $breakdownTotal = collect($breakdown)->sum('amount');
    $st = $statusMap[$dealerBill->billing_status] ?? [$dealerBill->billing_status, '#f1f1f3', '#52525b'];
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Detail Tagihan</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Tagihan&nbsp;/&nbsp;{{ $dealerBill->bill_id ?? '-' }}</div>
        </div>
        <div class="flex gap-2.5">
            <x-button label="Cetak Invoice" wire:click="openInvoice" icon="o-printer"
                      class="h-10 text-sm font-semibold border-none text-white" style="background:var(--lt-p);" />
            <x-button label="Hitung Ulang" wire:click="recalculate" icon="o-arrow-path" spinner
                      class="h-10 text-sm font-semibold text-white border-none" style="background:#0891b2;" />
            @if(! in_array($dealerBill->billing_status, ['paid', 'cancelled']))
                <a href="{{ route('payments.create', ['bill' => $dealerBill->dbid]) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95"
                   style="background:#16a34a;">
                    <x-icon name="o-credit-card" class="w-4 h-4" /> Bayar
                </a>
            @endif
            <a href="{{ route('bills.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-medium text-[#3f3f46] bg-white transition hover:bg-base-200"
               style="border:1px solid #e5e7eb;">
                <x-icon name="o-arrow-left" class="w-4 h-4" /> Kembali
            </a>
        </div>
    </div>

    {{-- Informasi Tagihan --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Informasi Tagihan</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-[18px] p-6 text-sm">
            <div><span class="font-semibold">No. Tagihan:</span> {{ $dealerBill->bill_id ?? '-' }}</div>
            <div><span class="font-semibold">Pedagang:</span> {{ $dealerBill->holder?->name ?? '-' }}</div>
            <div><span class="font-semibold">Lapak:</span> {{ $dealerBill->location_label }}</div>
            <div><span class="font-semibold">Jumlah:</span> {{ $rp0($dealerBill->total_amount) }}</div>
            <div><span class="font-semibold">Jatuh Tempo:</span> {{ $dealerBill->due_date?->format('d-m-Y') ?? '-' }}</div>
            <div>
                <span class="font-semibold">Status:</span>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
            </div>
            <div><span class="font-semibold">Terbayar:</span> {{ $rp2($paidTotal) }}</div>
            <div><span class="font-semibold">Sisa:</span> {{ $rp2($remaining) }}</div>
            <div><span class="font-semibold">Periode:</span> {{ $dealerBill->period_start?->format('d-m-Y') }} s/d {{ $dealerBill->period_end?->format('d-m-Y') }}</div>
        </div>
    </div>

    {{-- Rincian Tagihan --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Rincian Tagihan</div>
        @if(count($breakdown) > 0)
            <table class="w-full border-collapse">
                <thead>
                    <tr style="background:color-mix(in srgb, var(--lt-p) 6%, #fff);">
                        <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Komponen</th>
                        <th class="text-right px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breakdown as $item)
                        <tr>
                            <td class="px-6 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $item['label'] }}</td>
                            <td class="px-6 py-3.5 text-sm text-[#27272a] text-right" style="border-top:1px solid #f4f4f5;">{{ $rp0($item['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="px-6 py-3.5 text-sm font-bold text-[#1b2433]" style="border-top:1px solid #e5e7eb;">Total</td>
                        <td class="px-6 py-3.5 text-sm font-bold text-[#1b2433] text-right" style="border-top:1px solid #e5e7eb;">{{ $rp0($breakdownTotal) }}</td>
                    </tr>
                </tbody>
            </table>
            @if($breakdownTotal !== (int) $dealerBill->total_amount)
                <div class="m-6 mt-3">
                    <x-alert icon="o-exclamation-triangle" class="alert-warning">
                        Rincian dihitung dari konfigurasi sewa/add-on saat ini dan tidak cocok dengan
                        total tersimpan ({{ $rp0($dealerBill->total_amount) }}). Kemungkinan konfigurasi lapak berubah setelah tagihan dibuat.
                    </x-alert>
                </div>
            @endif
        @else
            <p class="px-6 py-6 text-sm text-[#71717a] m-0">Rincian tidak tersedia untuk tagihan ini.</p>
        @endif
    </div>

    {{-- Invoice di-render TERSEMBUNYI; openInvoice() langsung memanggil window.print(),
         @media print yang menampilkan isi .lt-print-overlay ini (tanpa preview di layar). --}}
    @if($showInvoice)
        <div class="lt-print-overlay" style="display:none;">
            @include('bills._invoice-card', ['invoiceBill' => $dealerBill])
        </div>
    @endif

    {{-- Riwayat Pembayaran --}}
    <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Riwayat Pembayaran</div>
        @if($dealerBill->payments->count() > 0)
            <table class="w-full border-collapse">
                <thead>
                    <tr style="background:color-mix(in srgb, var(--lt-p) 6%, #fff);">
                        <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">No. Bayar</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jumlah</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Tanggal</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Metode</th>
                        <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dealerBill->payments as $p)
                        <tr style="background:{{ $p->is_voided ? '#fef2f2' : '#fff' }};">
                            <td class="px-6 py-3.5 text-sm font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $p->bill_id ?? '-' }}</td>
                            <td class="px-4 py-3.5 text-sm text-[#27272a] text-right" style="border-top:1px solid #f4f4f5;">{{ $rp2($p->paid_amount) }}</td>
                            <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $p->payment_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ ucfirst($p->payment_method) }}</td>
                            <td class="px-6 py-3.5" style="border-top:1px solid #f4f4f5;">
                                @if($p->is_voided)
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#b91c1c;">Dibatalkan</span>
                                    <span class="text-xs text-[#9aa3b2] ml-1">{{ $p->voided_reason }}</span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7; color:#15803d;">Aktif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="px-6 py-6 text-sm text-[#71717a] m-0">Belum ada pembayaran.</p>
        @endif
    </div>
</div>
