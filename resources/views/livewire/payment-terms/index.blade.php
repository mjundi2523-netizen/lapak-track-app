<div>
    <x-index-header title="Aturan Bayar">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
        <x-button label="Tambah" link="{{ route('payment-terms.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'term_name', 'label' => 'Nama'])
                    @include('partials.sort-th', ['field' => 'dealer_condition', 'label' => 'Kondisi Pedagang'])
                    @include('partials.sort-th', ['field' => 'frequency', 'label' => 'Frekuensi'])
                    @include('partials.sort-th', ['field' => 'price', 'label' => 'Harga', 'align' => 'right'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentTerms as $row)
                    <tr class="lt-row">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->term_name }}</td>
                        <td class="lt-td">
                            @php
                                $cond = match($row->dealer_condition) {
                                    'regular'  => ['Regular',  '#dcfce7', '#15803d'],
                                    'external' => ['Eksternal','#fae8ff', '#86198f'],
                                    default    => [$row->dealer_condition ?? '-', '#f1f1f3', '#52525b'],
                                };
                            @endphp
                            <span class="lt-pill" style="background:{{ $cond[1] }}; color:{{ $cond[2] }};">{{ $cond[0] }}</span>
                        </td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:#dbeafe; color:#1d4ed8;">{{ match($row->frequency) {
                                'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan', default => $row->frequency,
                            } }}</span>
                        </td>
                        <td class="lt-td text-right">Rp {{ number_format($row->price, 0, ',', '.') }}</td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('payment-terms.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil" class="w-[18px] h-[18px]" /></a>
                                <button type="button" wire:click="delete({{ $row->ptid }})" wire:confirm="Yakin ingin menghapus?" class="lt-act text-error" title="Hapus"><x-icon name="o-trash" class="w-[18px] h-[18px]" /></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $paymentTerms->links() }}</div>
    </div>
</div>
