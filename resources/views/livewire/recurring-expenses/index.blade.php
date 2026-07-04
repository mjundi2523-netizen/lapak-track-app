@php
    $freqLabel = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'annual' => 'Tahunan'];
    $catColors = [
        ['#ede9fe', '#6d28d9'], ['#dbeafe', '#1d4ed8'], ['#dcfce7', '#15803d'],
        ['#fef3c7', '#92400e'], ['#fce7f3', '#be185d'], ['#cffafe', '#0e7490'],
        ['#fae8ff', '#86198f'], ['#ffedd5', '#9a3412'],
    ];
@endphp

<div>
    <x-index-header title="Pengeluaran Rutin">
        <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable class="w-40" />
        <x-select wire:model.live="activeFilter" :options="[
            ['value' => '', 'label' => 'Semua'],
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'inactive', 'label' => 'Nonaktif'],
        ]" option-value="value" option-label="label" class="w-32" />
        <x-button label="Tambah" link="{{ route('recurring-expenses.create') }}" class="btn-primary" icon="o-plus" />
    </x-index-header>

    <div class="lt-card-table">
        <table class="lt-table">
            <thead>
                <tr>
                    @include('partials.sort-th', ['field' => 'title', 'label' => 'Judul'])
                    @include('partials.sort-th', ['field' => 'category', 'label' => 'Kategori'])
                    @include('partials.sort-th', ['field' => 'frequency', 'label' => 'Frekuensi'])
                    @include('partials.sort-th', ['field' => 'amount', 'label' => 'Nominal', 'align' => 'right'])
                    @include('partials.sort-th', ['field' => 'auto_post', 'label' => 'Mode'])
                    @include('partials.sort-th', ['field' => 'start_date', 'label' => 'Mulai'])
                    @include('partials.sort-th', ['field' => 'is_active', 'label' => 'Status'])
                    <th class="lt-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $row)
                    @php $c = $catColors[($row->ecid ?? 0) % count($catColors)]; @endphp
                    <tr class="lt-row {{ $row->is_active ? '' : 'opacity-60' }}">
                        <td class="lt-td font-semibold text-[#18181b]">{{ $row->title }}</td>
                        <td class="lt-td">
                            <span class="lt-pill" style="background:{{ $c[0] }}; color:{{ $c[1] }};">{{ $row->category?->name ?? '-' }}</span>
                        </td>
                        <td class="lt-td">
                            Setiap {{ $row->interval_count > 1 ? $row->interval_count . ' ' : '' }}{{ strtolower($freqLabel[$row->frequency] ?? $row->frequency) }}
                        </td>
                        <td class="lt-td text-right font-medium">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                        <td class="lt-td">
                            @if($row->auto_post)
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Otomatis</span>
                            @else
                                <span class="lt-pill" style="background:#fef9c3; color:#a16207;">Perlu konfirmasi</span>
                            @endif
                        </td>
                        <td class="lt-td">{{ $row->start_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="lt-td">
                            @if($row->is_active)
                                <span class="lt-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                            @else
                                <span class="lt-pill" style="background:#f1f1f3; color:#52525b;">Nonaktif</span>
                            @endif
                        </td>
                        <td class="lt-td">
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('recurring-expenses.edit', $row) }}" wire:navigate class="lt-act" title="Edit"><x-icon name="o-pencil-square" class="w-[18px] h-[18px]" /></a>
                                <button type="button" wire:click="toggleActive({{ $row->rxid }})" class="lt-act {{ $row->is_active ? 'text-error' : 'text-success' }}" title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <x-icon name="{{ $row->is_active ? 'o-pause-circle' : 'o-play-circle' }}" class="w-[18px] h-[18px]" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="lt-td text-center text-[#9aa3b2] py-8">Belum ada pengeluaran rutin.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="lt-table-foot">{{ $templates->links() }}</div>
    </div>

    {{-- Popup konfirmasi occurrence pending (muncul otomatis saat ada yang jatuh waktu) --}}
    @if($showConfirm && $duePending->isNotEmpty())
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             x-data x-trap.noscroll="true">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showConfirm', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden"
                 style="border:1px solid #eceef2;">
                <div class="flex items-start gap-3 p-5" style="border-bottom:1px solid #eef0f4;">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:#fef9c3;">
                        <x-icon name="o-bell-alert" class="w-6 h-6 text-[#a16207]" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-[#18181b]">Konfirmasi Pengeluaran Rutin</h3>
                        <p class="text-sm text-[#71717a] mt-0.5">Ada {{ $duePending->count() }} pengeluaran rutin yang perlu ditinjau. Konfirmasi nominal, tunda, atau batalkan.</p>
                    </div>
                    <button type="button" wire:click="$set('showConfirm', false)" class="lt-act text-[#9aa3b2]" title="Tutup">
                        <x-icon name="o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-3">
                    @foreach($duePending as $e)
                        <div class="rounded-xl p-4" style="border:1px solid #eceef2; background:#f7f8fb;"
                             x-data="{ snooze: false }" wire:key="due-{{ $e->xpid }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-semibold text-[#18181b] truncate">{{ $e->title }}</div>
                                    <div class="text-xs text-[#71717a] mt-0.5">
                                        {{ $e->category?->name ?? '-' }} · Jadwal {{ $e->expense_date?->format('d-m-Y') }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-end gap-2">
                                <div class="w-40">
                                    <label class="text-[11px] font-semibold text-[#71717a]">Nominal (Rp)</label>
                                    <input type="number" min="1" wire:model="confirmAmounts.{{ $e->xpid }}"
                                           class="input input-bordered input-sm w-full" />
                                    @error("confirmAmounts.{$e->xpid}")
                                        <span class="text-[11px] text-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex gap-2 ml-auto">
                                    <button type="button" wire:click="confirmPending({{ $e->xpid }})"
                                            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold text-white transition hover:brightness-95"
                                            style="background:#16a34a;">
                                        <x-icon name="o-check" class="w-4 h-4" /> Konfirmasi
                                    </button>
                                    <button type="button" @click="snooze = !snooze"
                                            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold transition"
                                            style="background:#fef9c3; color:#a16207;">
                                        <x-icon name="o-clock" class="w-4 h-4" /> Tunda
                                    </button>
                                    <button type="button" wire:click="cancelPending({{ $e->xpid }})"
                                            wire:confirm="Batalkan occurrence ini? Tidak akan dicatat sebagai pengeluaran."
                                            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold transition"
                                            style="background:#fee2e2; color:#b91c1c;">
                                        <x-icon name="o-trash" class="w-4 h-4" /> Batalkan
                                    </button>
                                </div>
                            </div>

                            {{-- Panel tunda: pilih tanggal --}}
                            <div x-show="snooze" x-cloak class="mt-3 flex flex-wrap items-end gap-2 pt-3" style="border-top:1px dashed #e4e7ec;">
                                <div class="w-44">
                                    <label class="text-[11px] font-semibold text-[#71717a]">Tunda ke tanggal</label>
                                    <input type="date" wire:model="snoozeDates.{{ $e->xpid }}"
                                           min="{{ \Illuminate\Support\Carbon::tomorrow()->toDateString() }}"
                                           class="input input-bordered input-sm w-full" />
                                    @error("snoozeDates.{$e->xpid}")
                                        <span class="text-[11px] text-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="button" wire:click="snoozePending({{ $e->xpid }})"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold text-white transition hover:brightness-95"
                                        style="background:var(--lt-p);">
                                    <x-icon name="o-arrow-right" class="w-4 h-4" /> Tunda ke tanggal ini
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 flex justify-end" style="border-top:1px solid #eef0f4;">
                    <x-button label="Tutup" wire:click="$set('showConfirm', false)" class="btn-ghost btn-sm" />
                </div>
            </div>
        </div>
    @endif
</div>
