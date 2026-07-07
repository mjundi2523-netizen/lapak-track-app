<div>
    <x-index-header title="Biaya Tambahan">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
        <x-button label="Tambah" link="{{ route('add-ons.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'add_on', 'label' => 'Nama'])
                    @include('partials.sort-th', ['field' => 'frequency', 'label' => 'Frekuensi'])
                    @include('partials.sort-th', ['field' => 'price', 'label' => 'Harga', 'align' => 'right'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($addOns as $row)
                    <tr class="lt-row">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->add_on }}</td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:#dbeafe; color:#1d4ed8;">{{ match($row->frequency) {
                                'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan', default => $row->frequency,
                            } }}</span>
                        </td>
                        <td class="lt-td text-right">Rp {{ number_format($row->price, 0, ',', '.') }}</td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('add-ons.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil" class="w-[18px] h-[18px]" /></a>
                                <button type="button" wire:click="delete({{ $row->aoid }})" wire:confirm="Yakin ingin menghapus?" class="lt-act text-error" title="Hapus"><x-icon name="o-trash" class="w-[18px] h-[18px]" /></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $addOns->links() }}</div>
    </div>
</div>
