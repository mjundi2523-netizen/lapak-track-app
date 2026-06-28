@php
    $rp0 = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $today = \Illuminate\Support\Carbon::today();
    $statusMap = [
        'unpaid'      => ['Belum Bayar', '#fee2e2', '#b91c1c'],
        'installment' => ['Cicilan',     '#dbeafe', '#1d4ed8'],
        'pending'     => ['Pending',     '#fef9c3', '#a16207'],
        'paid'        => ['Lunas',       '#dcfce7', '#15803d'],
        'cancelled'   => ['Dibatalkan',  '#f1f1f3', '#52525b'],
    ];
@endphp

<div>
    {{-- Heading --}}
    <div class="flex items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
        <div>
            <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0 mb-1.5">Detail Pedagang</h1>
            <div class="text-[13px] text-[#9aa3b2]">Beranda&nbsp;/&nbsp;Pedagang&nbsp;/&nbsp;{{ $dealer->name }}</div>
        </div>
        <div class="flex gap-2.5">
            <button type="button" wire:click="openLetter"
                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-[#3f3f46] bg-white transition hover:bg-base-200"
                    style="border:1px solid #e5e7eb;">
                <x-icon name="o-printer" class="w-4 h-4" /> Cetak Surat
            </button>
            <a href="{{ route('dealers.edit', $dealer) }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-semibold text-white transition hover:brightness-95"
               style="background:var(--lt-p);">
                <x-icon name="o-pencil-square" class="w-4 h-4" /> Edit
            </a>
            <a href="{{ route('dealers.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 h-10 px-4 rounded-[9px] text-sm font-medium text-[#3f3f46] bg-white transition hover:bg-base-200"
               style="border:1px solid #e5e7eb;">
                <x-icon name="o-arrow-left" class="w-4 h-4" /> Kembali
            </a>
        </div>
    </div>

    {{-- Informasi Pedagang --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-5" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="px-6 py-4 text-base font-bold text-[#1b2433]" style="border-bottom:1px solid #eef0f4;">Informasi Pedagang</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-[18px] p-6 text-sm">
            <div><span class="font-semibold">NIK:</span> {{ $dealer->nik }}</div>
            <div><span class="font-semibold">Nama:</span> {{ $dealer->name }}</div>
            <div><span class="font-semibold">Tanggal Lahir:</span> {{ $dealer->birth_date?->format('d-m-Y') ?? '-' }}</div>
            <div><span class="font-semibold">Alamat:</span> {{ $dealer->address ?: '-' }}</div>
            <div><span class="font-semibold">Telepon 1:</span> {{ $dealer->phone_number_1 ?: '-' }}</div>
            <div><span class="font-semibold">Telepon 2:</span> {{ $dealer->phone_number_2 ?: '-' }}</div>
            <div><span class="font-semibold">Jenis Dagangan:</span> {{ $dealer->product_type ?: '-' }}</div>
            <div>
                <span class="font-semibold">Status:</span>
                @if($dealer->status === 'active')
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#dcfce7; color:#15803d;">Aktif</span>
                @else
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#f1f1f3; color:#52525b;">Nonaktif</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Per-lapak --}}
    @foreach($dealer->dealerStalls as $ds)
        {{-- "Sudah diakhiri" begitu rent_end_date di-set (tombol Akhiri Sewa jadi non-aktif). --}}
        @php $ended = (bool) $ds->rent_end_date; @endphp
        <div class="bg-white rounded-2xl overflow-hidden mb-4" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
            <div class="flex items-center justify-between gap-3 px-6 py-4" style="border-bottom:1px solid #eef0f4;">
                <span class="text-base font-bold text-[#1b2433]">Lapak: {{ $ds->stall?->block ?? '-' }}</span>
                <div class="flex items-center gap-2">
                    {{-- Tombol Hapus (salah input) — selalu tampil, ikon saja --}}
                    <button type="button" wire:click="startDelete({{ $ds->dsid }})"
                            title="Hapus rental ini (hanya untuk salah input)"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-[9px] transition hover:brightness-95"
                            style="background:#f4f4f6; color:#71717a;">
                        <x-icon name="o-trash" class="w-4 h-4" />
                    </button>

                    @if($ended)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:#f1f1f3; color:#52525b;">
                            <x-icon name="o-check-circle" class="w-4 h-4" /> Sewa berakhir {{ $ds->rent_end_date->format('d-m-Y') }}
                        </span>
                    @else
                        <button type="button" wire:click="startEnd({{ $ds->dsid }})"
                                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[9px] text-sm font-semibold transition hover:brightness-95"
                                style="background:#fee2e2; color:#b91c1c;">
                            <x-icon name="o-x-circle" class="w-4 h-4" /> Akhiri Sewa
                        </button>
                    @endif
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-[18px] mb-[18px] text-sm">
                    <div><span class="font-semibold">Mulai Sewa:</span> {{ $ds->rent_start_date?->format('d-m-Y') ?? '-' }}</div>
                    <div><span class="font-semibold">Akhir Sewa:</span> {{ $ds->rent_end_date?->format('d-m-Y') ?? '-' }}</div>
                </div>

                {{-- Tidak ada bills di sini lagi — lihat card "Riwayat Tagihan" di bawah --}}
            </div>
        </div>
    @endforeach

    {{-- Riwayat Tagihan (semua lapak + eksternal, paginated) --}}
    <div class="bg-white rounded-2xl overflow-hidden mb-4" style="border:1px solid #eceef2; box-shadow:0 1px 2px rgba(16,12,40,0.04);">
        <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid #eef0f4;">
            <h3 class="text-base font-bold text-[#1b2433] m-0">Riwayat Tagihan</h3>
            <span class="text-xs text-[#9aa3b2]">{{ $bills->total() }} tagihan</span>
        </div>
        @if($bills->isEmpty())
            <p class="px-6 py-6 text-sm text-[#71717a] m-0">Belum ada tagihan.</p>
        @else
            <table class="w-full border-collapse">
                <thead>
                    <tr style="background:color-mix(in srgb, var(--lt-p) 5%, #fff);">
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">No. Tagihan</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Lokasi</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jatuh Tempo</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Jumlah</th>
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $b)
                        @php $st = $statusMap[$b->billing_status] ?? [$b->billing_status, '#f1f1f3', '#52525b']; @endphp
                        <tr class="cursor-pointer transition-colors hover:bg-[#fafafa]"
                            onclick="window.location='{{ route('bills.show', $b) }}'">
                            <td class="px-5 py-3 text-sm font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $b->bill_id ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-[#52525b]" style="border-top:1px solid #f4f4f5;">{{ $b->location_label }}</td>
                            <td class="px-4 py-3 text-sm text-[#52525b] tabular-nums" style="border-top:1px solid #f4f4f5;">{{ $b->due_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-[#27272a] text-right tabular-nums" style="border-top:1px solid #f4f4f5;">{{ $rp0($b->total_amount) }}</td>
                            <td class="px-5 py-3" style="border-top:1px solid #f4f4f5;">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-3.5" style="border-top:1px solid #f4f4f5;">
                {{ $bills->links() }}
            </div>
        @endif
    </div>

    {{-- Modal: Hapus Rental (Salah Input) --}}
    <x-modal wire:model="deleteModal" title="Hapus Rental (Salah Input)" separator>
        <div class="space-y-3">
            <div class="flex items-start gap-3 p-4 rounded-xl" style="background:#fff7ed; border:1px solid #fed7aa;">
                <x-icon name="o-exclamation-triangle" class="w-5 h-5 mt-0.5 shrink-0" style="color:#c2410c;" />
                <p class="text-sm m-0" style="color:#9a3412;">
                    <span class="font-semibold">Gunakan hanya jika data ini diinput secara tidak sengaja.</span>
                    Record rental akan dihapus dari sistem dan lapak kembali kosong.
                    Semua tagihan yang belum dibayar akan dibatalkan otomatis.
                </p>
            </div>
            <p class="text-sm text-[#52525b] m-0">
                Hapus rental lapak <span class="font-semibold">{{ $deleteBlock }}</span>?
            </p>
            @error('deleteRental')
                <p class="text-sm font-semibold" style="color:#b91c1c;">{{ $message }}</p>
            @enderror
        </div>
        <x-slot:actions>
            <x-button label="Batal" @click="$wire.deleteModal = false" class="btn-ghost" />
            <x-button label="Ya, Hapus" wire:click="deleteRental" spinner="deleteRental"
                      class="text-white border-0" style="background:#b91c1c;" />
        </x-slot:actions>
    </x-modal>

    {{-- Modal: Akhiri Sewa --}}
    <x-modal wire:model="endModal" title="Akhiri Sewa Lapak" separator persistent>
        <div class="space-y-4">
            <p class="text-sm text-[#52525b]">
                Mengakhiri sewa lapak <span class="font-semibold">{{ $endBlock }}</span>.
                Setelah tanggal berakhir, lapak otomatis menjadi kosong dan tidak ada tagihan baru.
            </p>

            <x-input type="date" label="Tanggal Berakhir Sewa" wire:model="endDate" />

            <div>
                <div class="text-sm font-semibold text-[#1b2433] mb-2">Tagihan yang belum dibayar (tunggakan)</div>
                <x-radio wire:model="arrearAction" :options="[
                    ['id' => 'keep', 'name' => 'Biarkan jadi utang (tetap tercatat & bisa ditagih)'],
                    ['id' => 'cancel', 'name' => 'Batalkan (ditandai Dibatalkan)'],
                ]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$wire.endModal = false" class="btn-ghost" />
            <x-button label="Akhiri Sewa" wire:click="endRental" spinner="endRental"
                      class="text-white border-0" style="background:var(--lt-p);" />
        </x-slot:actions>
    </x-modal>

    {{-- Modal: Cetak Surat Pedagang --}}
    @if($showLetter)
        @include('dealers._letter', ['dealer' => $dealer])
    @endif
</div>
