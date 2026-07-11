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

            <div class="flex items-start gap-3">
                <div class="w-full max-w-xs">
                    <x-select label="Jenis Pedagang" wire:model.live="dealer_condition" :options="[
                        ['value' => 'regular', 'label' => 'Regular'],
                        ['value' => 'new', 'label' => 'Baru'],
                        ['value' => 'external', 'label' => 'Eksternal'],
                    ]" option-value="value" option-label="label"
                        hint="{{ ($hasActiveRental || $hasActiveExternal)
                            ? 'Masih ada kontrak aktif — akhiri dulu di halaman detail untuk mengubah.'
                            : match($dealer_condition) {
                                'new' => 'Memakai aturan bayar khusus pedagang baru.',
                                'external' => 'Tukang gerobak/keliling — tidak menyewa lapak.',
                                default => 'Pedagang reguler yang menyewa lapak.',
                            } }}" />
                    @error('dealer_condition')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @unless(auth()->user()->isPremium())
                    <span class="inline-flex items-center gap-1 mt-7 px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0" style="background:#fef3c7; color:#b45309;">
                        <x-icon name="s-lock-closed" class="w-3 h-3" /> Eksternal = Premium
                    </span>
                @endunless
            </div>

            <x-input label="Scan KTP" wire:model="scan_id_file" type="file" accept="image/*,.pdf" />

            @if($scan_id)
                <p class="text-sm text-base-content/60 mt-1">File saat ini: {{ basename($scan_id) }}</p>
            @endif

            {{-- Lapak yang disewa --}}
            <hr class="my-2" />
            <h3 class="font-bold text-base text-[#1b2433]">{{ $dealer_condition === 'external' ? 'Aturan Bayar (Eksternal)' : 'Lapak Disewa' }}</h3>

            @if($dealer_condition === 'external')
                @if($hasActiveExternal)
                    <div class="rounded-[10px] overflow-hidden" style="border:1px solid #f0f0f1;">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr style="background:#fafafa;">
                                    <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Aturan Bayar</th>
                                    <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Mulai</th>
                                    <th class="text-left px-4 py-2.5 text-[11px] font-bold text-[#a1a1aa] uppercase tracking-[0.04em]">Berakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeExternal as $e)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $e->paymentTerm?->term_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $e->start_date?->format('d-m-Y') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $e->end_date?->format('d-m-Y') ?? '— (berjalan)' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-[#9aa3b2]">Pedagang eksternal — langganan aktif.</p>
                @else
                    <p class="text-sm text-[#9aa3b2] -mt-1 mb-2">Pedagang eksternal tidak menyewa lapak — pilih aturan bayar yang berlaku.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select label="Aturan Bayar" wire:model="selected_ptid"
                            :options="$paymentTerms->map(fn($p) => ['id' => $p->ptid, 'name' => $p->term_name . ' · Rp ' . number_format($p->price, 0, ',', '.') . ' / ' . match($p->frequency) { 'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $p->frequency }])"
                            option-value="id" option-label="name"
                            placeholder="{{ $paymentTerms->isEmpty() ? 'Belum ada aturan bayar eksternal' : 'Pilih aturan bayar' }}" />
                        <x-input label="Tanggal Mulai" wire:model="external_start_date" type="date" />
                    </div>
                @endif
            @else
                {{-- Sewa aktif yang sudah ada: readonly (diubah lewat "Akhiri Sewa"). --}}
                @if($hasActiveRental)
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
                                        <td class="px-4 py-3 font-semibold text-[#18181b]" style="border-top:1px solid #f4f4f5;">{{ $r->stall?->code ?? '-' }}</td>
                                        <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $r->rent_start_date?->format('d-m-Y') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-[#27272a]" style="border-top:1px solid #f4f4f5;">{{ $r->rent_end_date?->format('d-m-Y') ?? '— (berjalan)' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-[#9aa3b2]">Sewa yang sedang berjalan hanya bisa diubah lewat aksi "Akhiri Sewa" di halaman detail pedagang. Kamu tetap bisa menambah lapak baru di bawah.</p>
                @else
                    <p class="text-sm text-[#9aa3b2] -mt-1">Pedagang ini belum menyewa lapak. Pilih lapak untuk memulai sewa baru.</p>
                @endif

                {{-- Tambah lapak baru (selalu tersedia; sama seperti form registrasi). --}}
                <p class="text-sm font-semibold text-[#1b2433] mt-3 mb-1">{{ $hasActiveRental ? 'Tambah Lapak Baru' : 'Pilih Lapak' }}</p>

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
                            @php $term = $s->paymentTerms->first(fn ($t) => (int) $t->pivot->sptid === (int) ($stall_term_choice[$s->sid] ?? 0)); @endphp
                            <span class="inline-flex items-center gap-2 lt-pill" style="background:#cffafe; color:#0e7490;">
                                {{ $s->code }}
                                @if($term)
                                    <span class="text-[#0e7490]">· {{ $term->term_name }} · Rp {{ number_format($term->price, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-amber-600 font-semibold">· pilih aturan bayar</span>
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
                <x-button label="Batal" link="{{ $this->backHref('dealers.show', $dealer) }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>

    {{-- Modal Konfirmasi Simpan (hanya dipicu saat ada penyewaan/langganan baru) --}}
    @include('dealers._save-confirm-modal', [
        'confirmTitle' => 'Konfirmasi Penyewaan Baru',
        'confirmIntro' => 'Penyewaan/langganan baru akan dibuat untuk pedagang "' . $name . '".',
        'cond_external' => $dealer_condition === 'external',
    ])

    @unless($dealer_condition === 'external')
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
                            'border-cyan-400 bg-cyan-50 cursor-pointer' => !$occupied && $checked,
                            'border-[#e4e4e7] hover:border-cyan-300 cursor-pointer' => !$occupied && !$checked,
                        ])
                        @if(!$occupied) wire:click="toggleStall({{ $stall->sid }})" @endif
                    >
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="checkbox checkbox-sm mt-1" @checked($checked) @disabled($occupied) wire:click.stop="toggleStall({{ $stall->sid }})" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-[#18181b]">{{ $stall->code }}</span>
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
                                    <p class="text-[#52525b] mb-1">Aturan bayar:</p>
                                    @forelse($stall->paymentTerms as $term)
                                        @php $picked = (int) ($stall_term_choice[$stall->sid] ?? 0) === (int) $term->pivot->sptid; @endphp
                                        <label class="flex items-start gap-2 py-0.5 cursor-pointer" wire:click.stop="setStallTerm({{ $stall->sid }}, {{ $term->pivot->sptid }})">
                                            <input type="radio" class="radio radio-xs mt-0.5" @checked($picked) @disabled($occupied) />
                                            <span>
                                                <span class="font-medium text-[#18181b]">Rp {{ number_format($term->price, 0, ',', '.') }}</span>
                                                <span class="text-[#71717a]">/ {{ $term->interval_count > 1 ? $term->interval_count . ' ' : '' }}{{ match($term->frequency) {
                                                    'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $term->frequency,
                                                } }}</span>
                                                <span class="text-[#a1a1aa]">· {{ $term->term_name }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <span class="text-[#a1a1aa] italic">Belum ada aturan sewa</span>
                                    @endforelse
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
