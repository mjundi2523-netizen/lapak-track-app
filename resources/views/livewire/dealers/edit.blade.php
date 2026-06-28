<div>
    <x-page-heading title="Edit Pedagang" />

    <x-card class="max-w-[820px]">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="NIK" wire:model="nik" required />
                <x-input label="Nama" wire:model="name" required />
                <x-input label="Tanggal Lahir" wire:model="birth_date" type="date" required />
                <x-input label="Alamat" wire:model="address" required />
                <x-input label="No. Telepon 1" wire:model="phone_number_1" required />
                <x-input label="No. Telepon 2" wire:model="phone_number_2" />
                <x-input label="Jenis Dagangan" wire:model="product_type" />
                <x-select label="Status" wire:model="status" :options="[
                    ['value' => 'active', 'label' => 'Aktif'],
                    ['value' => 'inactive', 'label' => 'Nonaktif'],
                ]" option-value="value" option-label="label"
                    :hint="$hasActiveRental ? 'Masih ada sewa aktif — akhiri sewa dulu untuk menonaktifkan.' : null" />
            </div>

            <x-input label="No. Surat Pedagang" wire:model="letter_no"
                placeholder="mis. A-004 / PSR-N / VI / 2026"
                hint="Untuk surat/kartu pedagang (opsional)" />

            <x-checkbox label="Pedagang baru" wire:model.live="is_new"
                hint="Pedagang baru memakai aturan bayar khusus — daftar lapak yang bisa dipilih menyesuaikan." />

            <x-input label="Scan KTP" wire:model="scan_id_file" type="file" accept="image/*,.pdf" />

            @if($scan_id)
                <p class="text-sm text-base-content/60 mt-1">File saat ini: {{ basename($scan_id) }}</p>
            @endif

            {{-- Lapak yang disewa --}}
            <hr class="my-2" />
            <h3 class="font-bold text-base text-[#1b2433]">Lapak Disewa</h3>

            @if($hasActiveRental)
                {{-- Task 2: tampilkan readonly --}}
                <div class="rounded-[10px] overflow-hidden" style="border:1px solid #f0f0f1;">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr style="background:#fafafa;">
                                <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Lapak</th>
                                <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Mulai Sewa</th>
                                <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Akhir Sewa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeRentals as $r)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $r->stall?->block ?? '-' }}</td>
                                    <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $r->rent_start_date?->format('d-m-Y') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $r->rent_end_date?->format('d-m-Y') ?? '— (berjalan)' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-[#9aa3b2]">Lapak hanya bisa diubah lewat aksi "Akhiri Sewa" di halaman detail pedagang.</p>
            @else
                {{-- Task 4: pedagang tanpa sewa aktif boleh memilih lapak lagi --}}
                <p class="text-sm text-[#9aa3b2] -mt-1">Pedagang ini belum menyewa lapak. Pilih lapak untuk memulai sewa baru.</p>

                <div class="flex items-center gap-3">
                    <x-button label="Pilih Lapak" icon="o-building-storefront" wire:click="$set('showStallModal', true)" class="btn-outline" />
                    <span class="text-sm text-[#52525b]">{{ count($selected_stalls) }} lapak dipilih</span>
                </div>

                @error('selected_stalls')
                    <p class="text-error text-sm mt-2">{{ $message }}</p>
                @enderror

                @if($selectedStallDetails->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach($selectedStallDetails as $s)
                            <span class="inline-flex items-center gap-2 lt-pill" style="background:#eef2ff; color:#4338ca;">
                                {{ $s->block }}
                                @if($s->paymentTerm)
                                    <span class="text-[#6366f1]">· Rp {{ number_format($s->paymentTerm->price, 0, ',', '.') }}</span>
                                @endif
                                <button type="button" wire:click="toggleStall({{ $s->sid }})" class="hover:text-error">
                                    <x-icon name="o-x-mark" class="w-4 h-4" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <x-input label="Tanggal Mulai Sewa" wire:model="rent_start_date" type="date" />
                    <x-input label="Tanggal Akhir Sewa (opsional)" wire:model="rent_end_date" type="date" />
                </div>
            @endif

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('dealers.show', $dealer) }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>

    @unless($hasActiveRental)
        {{-- Modal Pemilihan Lapak --}}
        <x-modal wire:model="showStallModal" title="Pilih Lapak" subtitle="Lapak yang sudah tersewa tidak dapat dipilih." box-class="max-w-2xl">
            <x-input placeholder="Cari blok lapak..." wire:model.live.debounce="stallSearch" clearable icon="o-magnifying-glass" class="mb-4" />

            <div class="max-h-[60vh] overflow-y-auto space-y-2">
                @forelse($stalls as $stall)
                    @php
                        $occupied = $stall->active_rentals_count > 0;
                        $checked = in_array($stall->sid, $selected_stalls, true);
                    @endphp
                    <div
                        @class([
                            'rounded-lg border p-3 transition',
                            'border-[#e4e4e7] opacity-60 bg-[#fafafa]' => $occupied,
                            'border-indigo-400 bg-indigo-50 cursor-pointer' => !$occupied && $checked,
                            'border-[#e4e4e7] hover:border-indigo-300 cursor-pointer' => !$occupied && !$checked,
                        ])
                        @if(!$occupied) wire:click="toggleStall({{ $stall->sid }})" @endif
                    >
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="checkbox checkbox-sm mt-1" @checked($checked) @disabled($occupied) wire:click.stop="toggleStall({{ $stall->sid }})" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-[#18181b]">{{ $stall->block }}</span>
                                    @if($occupied)
                                        <span class="lt-pill" style="background:#fee2e2; color:#b91c1c;">Tersewa</span>
                                    @else
                                        <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Tersedia</span>
                                    @endif
                                </div>

                                @if($stall->description)
                                    <p class="text-sm text-[#71717a] mt-0.5">{{ $stall->description }}</p>
                                @endif

                                <div class="text-sm mt-2">
                                    @if($stall->paymentTerm)
                                        <span class="text-[#52525b]">Sewa:</span>
                                        <span class="font-medium text-[#18181b]">Rp {{ number_format($stall->paymentTerm->price, 0, ',', '.') }}</span>
                                        <span class="text-[#71717a]">/ {{ $stall->paymentTerm->interval_count > 1 ? $stall->paymentTerm->interval_count . ' ' : '' }}{{ match($stall->paymentTerm->frequency) {
                                            'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $stall->paymentTerm->frequency,
                                        } }}</span>
                                    @else
                                        <span class="text-[#a1a1aa] italic">Belum ada aturan sewa</span>
                                    @endif
                                </div>

                                @if($stall->addOns->isNotEmpty())
                                    <div class="text-sm mt-1.5">
                                        <span class="text-[#52525b]">Biaya tambahan:</span>
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            @foreach($stall->addOns as $addOn)
                                                <span class="lt-pill" style="background:#fef3c7; color:#92400e;">
                                                    {{ $addOn->add_on }}: Rp {{ number_format($addOn->price, 0, ',', '.') }} / {{ match($addOn->frequency) {
                                                        'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $addOn->frequency,
                                                    } }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-[#9aa3b2] py-8">Tidak ada lapak ditemukan.</p>
                @endforelse
            </div>

            <x-slot:actions>
                <span class="text-sm text-[#52525b] mr-auto">{{ count($selected_stalls) }} dipilih</span>
                <x-button label="Selesai" wire:click="$set('showStallModal', false)" class="btn-primary" />
            </x-slot:actions>
        </x-modal>
    @endunless
</div>
