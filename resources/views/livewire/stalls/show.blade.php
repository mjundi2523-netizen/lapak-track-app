@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
    ];
    $tenantName = $tenant?->dealer?->name;
    $initials = $tenantName ? strtoupper(\Illuminate\Support\Str::substr($tenantName, 0, 2)) : '–';
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Detail Lapak</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Lapak&nbsp;/&nbsp;{{ $stall->block }}</div>
        </div>
        <div class="flex gap-2.5">
            <a href="{{ route('stalls.edit', $stall) }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95"
               style="background:var(--lt-p);">
                <x-icon name="o-pencil-square" class="w-4 h-4" /> Edit
            </a>
            <a href="{{ route('stalls.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-medium text-[#3f3f46] bg-white transition hover:bg-base-200"
               style="border:1px solid #e5e7eb;">
                <x-icon name="o-arrow-left" class="w-4 h-4" /> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-5 items-start">
        {{-- Left column --}}
        <div class="flex flex-col gap-5 min-w-0">
            {{-- Informasi Lapak --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Informasi Lapak</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-[18px] p-6">
                    <div class="text-sm"><span class="font-semibold">Blok:</span> {{ $stall->block }}</div>
                    <div class="text-sm">
                        <span class="font-semibold">Status:</span>
                        @if($stall->is_active)
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7; color:#15803d;">Aktif</span>
                        @else
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#f1f1f3; color:#52525b;">Nonaktif</span>
                        @endif
                    </div>
                    <div class="text-sm"><span class="font-semibold">Deskripsi:</span> {{ $stall->description ?: '-' }}</div>
                    <div class="text-sm"><span class="font-semibold">Aturan Bayar:</span> {{ $stall->paymentTerm?->term_name ?? '-' }}</div>
                </div>
            </div>

            {{-- Tagihan --}}
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Tagihan</div>
                @if($bills->isEmpty())
                    <p class="px-6 py-6 text-sm text-[#71717a] m-0">Belum ada tagihan untuk lapak ini.</p>
                @else
                    <table class="w-full border-collapse">
                        <thead>
                            <tr style="background:color-mix(in srgb, var(--lt-p) 6%, #fff);">
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">No. Tagihan</th>
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Pedagang</th>
                                <th class="text-right px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jumlah</th>
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jatuh Tempo</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bills as $b)
                                @php $st = $statusMap[$b->billing_status] ?? [$b->billing_status, '#f1f1f3', '#52525b']; @endphp
                                <tr class="cursor-pointer transition-colors hover:bg-[#fafafa]"
                                    onclick="window.location='{{ route('bills.show', $b) }}'">
                                    <td class="px-6 py-3.5 text-sm font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $b->bill_id ?? '-' }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $b->dealerStall?->dealer?->name ?? '-' }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a] text-right" style="border-top:1px solid #f4f4f5;">{{ $rp($b->total_amount) }}</td>
                                    <td class="px-4 py-3.5 text-sm text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $b->due_date?->format('d-m-Y') ?? '-' }}</td>
                                    <td class="px-6 py-3.5" style="border-top:1px solid #f4f4f5;">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3.5" style="border-top:1px solid #f4f4f5;">
                        {{ $bills->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column: Penyewa Saat Ini --}}
        <div class="flex flex-col gap-5">
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
                <div class="px-[22px] py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Penyewa Saat Ini</div>
                @if($tenant && $tenant->dealer)
                    <div class="px-[22px] py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-[46px] h-[46px] rounded-full flex items-center justify-center text-base font-bold shrink-0"
                                 style="background:color-mix(in srgb, var(--lt-p) 14%, #fff); color:var(--lt-p);">{{ $initials }}</div>
                            <div class="min-w-0">
                                <div class="text-[15px] font-semibold text-[#1b2433] truncate">{{ $tenant->dealer->name }}</div>
                                <div class="text-xs text-[#9aa3b2]">{{ $tenant->dealer->product_type ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2.5 text-[13px]">
                            <div class="flex justify-between gap-3"><span class="text-[#9aa3b2]">NIK</span><span class="text-[#27272a] tabular-nums">{{ $tenant->dealer->nik ?: '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-[#9aa3b2]">Telepon</span><span class="text-[#27272a]">{{ $tenant->dealer->phone_number_1 ?: '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-[#9aa3b2]">Mulai Sewa</span><span class="text-[#27272a]">{{ $tenant->rent_start_date?->format('d-m-Y') ?? '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-[#9aa3b2]">Akhir Sewa</span><span class="text-[#27272a]">{{ $tenant->rent_end_date?->format('d-m-Y') ?? '-' }}</span></div>
                        </div>
                        <a href="{{ route('dealers.show', $tenant->dealer) }}" wire:navigate
                           class="mt-[18px] w-full h-[38px] inline-flex items-center justify-center gap-1.5 bg-white rounded-[9px] text-[13px] font-semibold text-[#3f3f46] transition hover:bg-base-200"
                           style="border:1px solid #e5e7eb;">
                            Lihat Pedagang <x-icon name="o-arrow-right" class="w-[15px] h-[15px]" />
                        </a>
                    </div>
                @else
                    <div class="px-[22px] py-7 text-center">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3" style="background:#fdf1da;">
                            <x-icon name="o-exclamation-triangle" class="w-6 h-6" style="color:#d97706;" />
                        </div>
                        <div class="text-sm font-semibold text-[#3f3f46]">Lapak Kosong</div>
                        <div class="text-[13px] text-[#9aa3b2] mt-0.5">Belum ada penyewa.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
