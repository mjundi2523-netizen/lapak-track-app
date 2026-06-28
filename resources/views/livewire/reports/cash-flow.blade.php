@php $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); @endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Laporan Arus Kas</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Laporan&nbsp;/&nbsp;Arus Kas</div>
        </div>
        <x-select wire:model.live="year"
            :options="$years->map(fn($y) => ['id' => $y, 'name' => $y])" option-value="id" option-label="name" class="w-32" />
    </div>

    {{-- Kartu ringkasan tahun --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg,#14a07a,#21c08f); box-shadow:0 10px 24px rgba(20,160,122,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3">Total Pemasukan {{ $year }}</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($totalIncome) }}</div>
        </div>
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]" style="background:linear-gradient(135deg,#ef4444,#f97316); box-shadow:0 10px 24px rgba(239,68,68,0.22);">
            <div class="text-sm font-medium opacity-90 mb-3">Total Pengeluaran {{ $year }}</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($totalExpense) }}</div>
        </div>
        <div class="rounded-[14px] px-6 py-[22px] text-white min-h-[110px]"
             style="background:linear-gradient(135deg, {{ $totalNet >= 0 ? 'var(--lt-p),#7c8cff' : '#b91c1c,#ef4444' }}); box-shadow:0 10px 24px color-mix(in srgb, var(--lt-p) 24%, transparent);">
            <div class="text-sm font-medium opacity-90 mb-3">Laba / Rugi {{ $year }}</div>
            <div class="text-[28px] font-bold leading-none">{{ $rp($totalNet) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">
        {{-- Tabel bulanan --}}
        <div class="lt-card-table">
            <table class="lt-table">
                <thead>
                    <tr>
                        <th class="lt-th">Bulan</th>
                        <th class="lt-th text-right">Pemasukan</th>
                        <th class="lt-th text-right">Pengeluaran</th>
                        <th class="lt-th text-right">Laba/Rugi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr class="lt-row">
                            <td class="lt-td">{{ $r['month'] }}</td>
                            <td class="lt-td text-right text-[#15803d]">{{ $rp($r['income']) }}</td>
                            <td class="lt-td text-right text-[#b91c1c]">{{ $rp($r['expense']) }}</td>
                            <td class="lt-td text-right font-medium {{ $r['net'] >= 0 ? 'text-[#15803d]' : 'text-[#b91c1c]' }}">{{ $rp($r['net']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#fafafa;">
                        <td class="lt-td font-bold text-[#1b2433]">Total</td>
                        <td class="lt-td text-right font-bold text-[#15803d]">{{ $rp($totalIncome) }}</td>
                        <td class="lt-td text-right font-bold text-[#b91c1c]">{{ $rp($totalExpense) }}</td>
                        <td class="lt-td text-right font-bold {{ $totalNet >= 0 ? 'text-[#15803d]' : 'text-[#b91c1c]' }}">{{ $rp($totalNet) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pengeluaran per kategori --}}
        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="px-5 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Pengeluaran per Kategori</div>
            @forelse($byCategory as $c)
                <div class="flex items-center justify-between px-5 py-3 text-sm" @if(!$loop->last) style="border-bottom:1px solid #f4f4f5;" @endif>
                    <span class="text-[#27272a]">{{ $c['name'] }}</span>
                    <span class="font-semibold text-[#b91c1c]">{{ $rp($c['total']) }}</span>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-[#9aa3b2] m-0">Belum ada pengeluaran tahun ini.</p>
            @endforelse
        </div>
    </div>
</div>
