@php
    $catColors = [
        ['#ede9fe', '#6d28d9'], ['#dbeafe', '#1d4ed8'], ['#dcfce7', '#15803d'],
        ['#fef3c7', '#92400e'], ['#fce7f3', '#be185d'], ['#cffafe', '#0e7490'],
        ['#fae8ff', '#86198f'], ['#ffedd5', '#9a3412'],
    ];
@endphp

<div>
    <x-index-header title="Pengeluaran">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable class="w-40" />
        <x-select wire:model.live="categoryFilter" placeholder="Semua Kategori"
            :options="$categories->map(fn($c) => ['id' => $c->ecid, 'name' => $c->name])" option-value="id" option-label="name" class="w-44" />
        <x-input type="date" wire:model.live="dateFrom" class="w-36" />
        <x-input type="date" wire:model.live="dateTo" class="w-36" />
        <x-select wire:model.live="voidedFilter" :options="[
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'voided', 'label' => 'Dibatalkan'],
            ['value' => '', 'label' => 'Semua'],
        ]" option-value="value" option-label="label" class="w-32" />
        <x-button label="Tambah" link="{{ route('expenses.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    {{-- Ringkasan total (sesuai filter, hanya yang aktif) --}}
    <div class="mb-4 inline-flex items-center gap-3 rounded-[12px] px-5 py-3"
         style="background:linear-gradient(135deg,#ef4444,#f97316); box-shadow:0 8px 20px rgba(239,68,68,0.20);">
        <x-icon name="o-banknotes" class="w-6 h-6 text-white/90" />
        <div>
            <div class="text-[11px] font-medium text-white/85 uppercase tracking-wide">Total Pengeluaran (filter)</div>
            <div class="text-xl font-bold text-white leading-none mt-0.5">Rp {{ number_format($total, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'expense_date', 'label' => 'Tanggal'])
                    @include('partials.sort-th', ['field' => 'title', 'label' => 'Judul'])
                    @include('partials.sort-th', ['field' => 'category', 'label' => 'Kategori'])
                    @include('partials.sort-th', ['field' => 'payment_method', 'label' => 'Metode'])
                    @include('partials.sort-th', ['field' => 'amount', 'label' => 'Jumlah', 'align' => 'right'])
                    @include('partials.sort-th', ['field' => 'status', 'label' => 'Status'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $row)
                    @php $c = $catColors[($row->ecid ?? 0) % count($catColors)]; @endphp
                    <tr class="lt-row {{ $row->is_voided ? 'lt-row-danger' : '' }}">
                        <td class="lt-td">{{ $row->expense_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->title }}</td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:{{ $c[0] }}; color:{{ $c[1] }};">{{ $row->category?->name ?? '-' }}</span>
                        </td>
                        <td class="lt-td">{{ ucfirst($row->payment_method) }}</td>
                        <td class="lt-td text-right font-medium">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                        <td class="lt-td">
                            @if($row->is_voided)
                                <span class="lt-pill" style="background:#fee2e2; color:#b91c1c;">Dibatalkan</span>
                            @elseif($row->status === 'pending')
                                <span class="lt-pill" style="background:#fef9c3; color:#a16207;">Menunggu Konfirmasi</span>
                            @else
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                            @endif
                            @if($row->rxid)
                                <span class="lt-pill ml-1" style="background:#cffafe; color:#0e7490;">Rutin</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                @if($row->status === 'pending' && ! $row->is_voided)
                                    <a href="{{ route('recurring-expenses.index') }}" wire:navigate class="lt-act text-primary" title="Konfirmasi di Pengeluaran Rutin"><x-icon name="o-bell-alert" class="w-[18px] h-[18px]" /></a>
                                @elseif(! $row->is_voided)
                                    <a href="{{ route('expenses.void', $row) }}" wire:navigate class="lt-act text-error" title="Batalkan"><x-icon name="o-x-mark" class="w-[18px] h-[18px]" /></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $expenses->links() }}</div>
    </div>
</div>
