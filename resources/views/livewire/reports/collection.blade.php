@php $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); @endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Rekap Penerimaan</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Laporan&nbsp;/&nbsp;Rekap Penerimaan</div>
        </div>
        <a href="{{ route('reports.collection.export', array_filter([
                'from'   => $this->from,
                'to'     => $this->to,
                'search' => $this->search,
            ])) }}"
           class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95 shrink-0"
           style="background:#16a34a;">
            <x-icon name="o-arrow-down-tray" class="w-4 h-4" /> Export Excel
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs font-semibold text-[#52525b] mb-1">Dari</label>
            <input type="date" wire:model.live="from" class="input input-bordered input-sm h-10 text-sm" />
        </div>
        <div>
            <label class="block text-xs font-semibold text-[#52525b] mb-1">Sampai</label>
            <input type="date" wire:model.live="to" class="input input-bordered input-sm h-10 text-sm" />
        </div>
        <x-input placeholder="Cari nama/no. tagihan..." wire:model.live.debounce="search" clearable />
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg,#14a07a,#21c08f); box-shadow:0 10px 24px rgba(20,160,122,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3">Total Penerimaan</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($grandTotal) }}</div>
            <div class="text-xs opacity-80 mt-2">{{ $grandCount }} pembayaran</div>
        </div>
        <div class="bg-white rounded-[14px] px-6 py-[22px] min-h-[110px]" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="o-banknotes" class="w-4 h-4 text-[#15803d]" />
                <span class="text-sm font-medium text-[#52525b]">Tunai</span>
            </div>
            <div class="text-[26px] font-bold leading-none text-[#1b2433]">{{ $rp($tunaiTotal) }}</div>
            <div class="text-xs text-[#9aa3b2] mt-2">{{ $tunaiCount }} pembayaran</div>
        </div>
        <div class="bg-white rounded-[14px] px-6 py-[22px] min-h-[110px]" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="o-arrows-right-left" class="w-4 h-4 text-[#1d4ed8]" />
                <span class="text-sm font-medium text-[#52525b]">Transfer</span>
            </div>
            <div class="text-[26px] font-bold leading-none text-[#1b2433]">{{ $rp($transferTotal) }}</div>
            <div class="text-xs text-[#9aa3b2] mt-2">{{ $transferCount }} pembayaran</div>
        </div>
    </div>

    {{-- Tabel detail --}}
    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">Tanggal</th>
                    <th class="lt-th">No. Tagihan</th>
                    <th class="lt-th">Pedagang</th>
                    <th class="lt-th">Lokasi</th>
                    <th class="lt-th">Metode</th>
                    <th class="lt-th text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr class="lt-row cursor-pointer" onclick="window.location='{{ route('payments.show', $p) }}'">
                        <td class="lt-td whitespace-nowrap">{{ $p->payment_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td font-semibold text-[#18181b]">{{ $p->bill_id ?? '-' }}</td>
                        <td class="lt-td">{{ $p->dealerBill?->holder?->name ?? '-' }}</td>
                        <td class="lt-td">{{ $p->dealerBill?->location_label ?? '-' }}</td>
                        <td class="lt-td">
                            @php $isTunai = $p->payment_method === 'tunai'; @endphp
                            <span class="lt-pill" style="background:{{ $isTunai ? '#dcfce7' : '#dbeafe' }}; color:{{ $isTunai ? '#15803d' : '#1d4ed8' }};">{{ ucfirst($p->payment_method) }}</span>
                        </td>
                        <td class="lt-td text-right font-medium text-[#15803d]">{{ $rp($p->paid_amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada penerimaan pada rentang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($payments->hasPages())
            <div class="lt-table-foot">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
