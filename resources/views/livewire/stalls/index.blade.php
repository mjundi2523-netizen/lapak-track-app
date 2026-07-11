<div>
    <x-index-header title="Lapak">
        <x-input placeholder="Cari lokasi (blok/nomor)..." wire:model.live.debounce="search" clearable />
        <x-button label="Tambah" link="{{ route('stalls.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'location', 'label' => 'Lokasi'])
                    @include('partials.sort-th', ['field' => 'size', 'label' => 'Ukuran'])
                    @include('partials.sort-th', ['field' => 'term', 'label' => 'Aturan Bayar Sewa'])
                    @include('partials.sort-th', ['field' => 'is_active', 'label' => 'Status'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($stalls as $row)
                    <tr class="lt-row">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->code }}</td>
                        <td class="lt-td">{{ $row->size ?: '-' }}</td>
                        <td class="lt-td">{{ $row->paymentTerms->pluck('term_name')->join(', ') ?: '-' }}</td>
                        <td class="lt-td">
                            @if($row->is_active)
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                            @else
                                <span class="lt-pill" style="background:#f1f1f3; color:#52525b;">Nonaktif</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('stalls.show', $row) }}" wire:navigate class="lt-act" title="Detail"><x-icon name="o-eye" class="w-[18px] h-[18px]" /></a>
                                <a href="{{ route('stalls.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil" class="w-[18px] h-[18px]" /></a>
                                <button type="button" wire:click="toggleActive({{ $row->sid }})"
                                        class="lt-act {{ $row->is_active ? 'text-warning' : 'text-success' }}"
                                        title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <x-icon name="{{ $row->is_active ? 'o-pause-circle' : 'o-play-circle' }}" class="w-[18px] h-[18px]" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $stalls->links() }}</div>
    </div>
</div>
