<div>
    <x-page-heading title="Registrasi Pedagang" />

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
                ]" option-value="value" option-label="label" />
                <x-input label="No. Surat Pedagang" wire:model="letter_no"
                    placeholder="mis. A-004 / PSR-N / VI / 2026"
                    hint="Untuk surat/kartu pedagang (opsional)" />
            </div>

            <div class="flex flex-wrap gap-6">
                <x-checkbox label="Pedagang baru" wire:model.live="cond_new"
                    hint="Memakai aturan bayar khusus pedagang baru." />
                <div class="flex items-start gap-1.5">
                    <x-checkbox label="Pedagang eksternal" wire:model.live="cond_external"
                        hint="Tukang gerobak/keliling — tidak perlu memilih lapak." />
                    @unless(auth()->user()->isPremium())
                        <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full text-[11px] font-semibold" style="background:#fef3c7; color:#b45309;">
                            <x-icon name="s-lock-closed" class="w-3 h-3" /> Premium
                        </span>
                    @endunless
                </div>
            </div>

            <x-input label="Scan KTP" wire:model="scan_id_file" type="file" accept="image/*,.pdf" />

            @unless($cond_external)
            <hr class="my-4" />

            <h3 class="font-bold text-lg mb-2">Lapak</h3>

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
                        <span class="inline-flex items-center gap-2 lt-pill" style="background:#cffafe; color:#0e7490;">
                            {{ $s->code }}
                            @if($s->paymentTerm)
                                <span class="text-[#0e7490]">· Rp {{ number_format($s->paymentTerm->price, 0, ',', '.') }}</span>
                            @endif
                            <button type="button" wire:click="toggleStall({{ $s->sid }})" class="hover:text-error">
                                <x-icon name="o-x-mark" class="w-4 h-4" />
                            </button>
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <x-input label="Tanggal Mulai Sewa" wire:model="rent_start_date" type="date" required />
                <x-input label="Tanggal Akhir Sewa (opsional)" wire:model="rent_end_date" type="date" />
            </div>
            @else
                <hr class="my-4" />
                <h3 class="font-bold text-lg mb-2">Aturan Bayar (Eksternal)</h3>
                <p class="text-sm text-[#9aa3b2] -mt-1 mb-2">Pedagang eksternal tidak menyewa lapak — pilih aturan bayar yang berlaku.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Aturan Bayar" wire:model="selected_ptid"
                        :options="$paymentTerms->map(fn($p) => ['id' => $p->ptid, 'name' => $p->term_name . ' · Rp ' . number_format($p->price, 0, ',', '.') . ' / ' . match($p->frequency) { 'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $p->frequency }])"
                        option-value="id" option-label="name"
                        placeholder="{{ $paymentTerms->isEmpty() ? 'Belum ada aturan bayar eksternal' : 'Pilih aturan bayar' }}" />
                    <x-input label="Tanggal Mulai" wire:model="external_start_date" type="date" />
                </div>
            @endunless

            <x-slot:actions>
                <x-button label="Batal" link="{{ $this->backHref('dealers.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>

    {{-- Modal Konfirmasi Simpan --}}
    @include('dealers._save-confirm-modal', [
        'confirmTitle' => 'Konfirmasi Registrasi Pedagang',
        'confirmIntro' => 'Pedagang "' . $name . '" akan didaftarkan.',
    ])

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

                            {{-- Aturan sewa --}}
                            <div class="text-sm mt-2">
                                @if($stall->paymentTerm)
                                    <span class="text-[#52525b]">Sewa:</span>
                                    <span class="font-medium text-[#18181b]">
                                        Rp {{ number_format($stall->paymentTerm->price, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[#71717a]">/ {{ $stall->paymentTerm->interval_count > 1 ? $stall->paymentTerm->interval_count . ' ' : '' }}{{ match($stall->paymentTerm->frequency) {
                                        'daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'annual' => 'tahun', default => $stall->paymentTerm->frequency,
                                    } }}</span>
                                @else
                                    <span class="text-[#a1a1aa] italic">Belum ada aturan sewa</span>
                                @endif
                            </div>

                            {{-- Biaya tambahan --}}
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
</div>
