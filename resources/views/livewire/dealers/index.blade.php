<div>
    <x-index-header title="Pedagang">
        <x-input placeholder="Cari nama/NIK..." wire:model.live.debounce="search" clearable />
        <x-button label="Tambah" link="{{ route('dealers.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    <th class="lt-th">NIK</th>
                    <th class="lt-th">Nama</th>
                    <th class="lt-th">Kondisi</th>
                    <th class="lt-th">Telepon</th>
                    <th class="lt-th">Status</th>
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($dealers as $row)
                    <tr class="lt-row">
                        <td class="lt-td tabular-nums text-[#52525b]">{{ $row->nik }}</td>
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->name }}</td>
                        <td class="lt-td">
                            @php
                                $cond = match($row->dealer_condition) {
                                    'regular'  => ['Regular',  '#dcfce7', '#15803d'],
                                    'new'      => ['Baru',     '#dbeafe', '#1d4ed8'],
                                    'external' => ['Eksternal','#fae8ff', '#86198f'],
                                    default    => [$row->dealer_condition ?? '-', '#f1f1f3', '#52525b'],
                                };
                            @endphp
                            <span class="lt-pill" style="background:{{ $cond[1] }}; color:{{ $cond[2] }};">{{ $cond[0] }}</span>
                        </td>
                        <td class="lt-td">{{ $row->phone_number_1 ?: '-' }}</td>
                        <td class="lt-td">
                            @if($row->status === 'active')
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                            @else
                                <span class="lt-pill" style="background:#f1f1f3; color:#52525b;">Nonaktif</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('dealers.show', $row) }}" wire:navigate class="lt-act" title="Detail"><x-icon name="o-eye" class="w-[18px] h-[18px]" /></a>
                                <a href="{{ route('dealers.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil" class="w-[18px] h-[18px]" /></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="lt-td text-center text-[#9aa3b2] py-8">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $dealers->links() }}</div>
    </div>
</div>
