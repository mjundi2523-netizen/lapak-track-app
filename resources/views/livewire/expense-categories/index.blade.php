<div>
    <x-index-header title="Kategori Pengeluaran">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable />
        <x-button label="Tambah" link="{{ route('expense-categories.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'name', 'label' => 'Nama Kategori'])
                    @include('partials.sort-th', ['field' => 'expenses_count', 'label' => 'Jumlah Pengeluaran', 'align' => 'right'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $row)
                    <tr class="lt-row">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->name }}</td>
                        <td class="lt-td text-right text-[#71717a]">{{ $row->expenses_count }}</td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('expense-categories.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil" class="w-[18px] h-[18px]" /></a>
                                <button type="button" wire:click="delete({{ $row->ecid }})" wire:confirm="Yakin ingin menghapus kategori ini?" class="lt-act text-error" title="Hapus"><x-icon name="o-trash" class="w-[18px] h-[18px]" /></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="lt-td text-center text-[#9aa3b2] py-8">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $categories->links() }}</div>
    </div>
</div>
