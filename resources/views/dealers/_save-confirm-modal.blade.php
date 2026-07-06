{{-- Modal konfirmasi simpan pedagang: menjelaskan tagihan yang akan dibuat otomatis.
     Dipakai Create & Edit (props komponen sama: cond_external, external_start_date,
     rent_start_date, rent_end_date, selected_stalls, selected_ptid, showSaveConfirm).
     Variabel include: $confirmTitle (judul), $confirmIntro (kalimat pembuka). --}}
@php
    $today = \Illuminate\Support\Carbon::today();
    $start = $cond_external
        ? ($external_start_date ? \Illuminate\Support\Carbon::parse($external_start_date) : null)
        : ($rent_start_date ? \Illuminate\Support\Carbon::parse($rent_start_date) : null);
    $end = (! $cond_external && $rent_end_date) ? \Illuminate\Support\Carbon::parse($rent_end_date) : null;
    $limit = ($end && $end->lt($today)) ? $end : $today;
    $hasBilling = $cond_external ? (bool) $selected_ptid : count($selected_stalls) > 0;
@endphp

<x-modal wire:model="showSaveConfirm" title="{{ $confirmTitle }}" box-class="max-w-md">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:#cffafe;">
            <x-icon name="o-document-text" class="w-6 h-6 text-[#0e7490]" />
        </div>
        <div class="text-sm space-y-2">
            <p>{{ $confirmIntro }}</p>

            @if($hasBilling && $start)
                @if($start->gt($today))
                    <p>
                        Tanggal mulai <span class="font-semibold">{{ $start->format('d-m-Y') }}</span> masih di masa depan —
                        belum ada tagihan yang dibuat sekarang; tagihan akan dibuat otomatis mulai tanggal tersebut.
                    </p>
                @else
                    <p>
                        Tagihan akan <span class="font-semibold">otomatis dibuat</span> sejak
                        <span class="font-semibold">{{ $start->format('d-m-Y') }}</span> hingga
                        <span class="font-semibold">{{ $limit->format('d-m-Y') }}</span>{{ $limit->equalTo($today) ? ' (hari ini)' : ' (akhir sewa)' }},
                        sesuai frekuensi aturan bayar{{ $cond_external ? '' : ' tiap lapak' }}.
                    </p>
                    <p class="text-[#b45309]">
                        Periode yang jatuh temponya sudah lewat akan langsung berstatus <span class="font-semibold">Belum Dibayar</span>.
                    </p>
                @endif
            @else
                <p class="text-[#71717a]">
                    Tidak ada lapak / aturan bayar yang dipilih — pedagang disimpan sebagai data master saja,
                    tanpa tagihan yang dibuat.
                </p>
            @endif

            <p class="font-semibold">Konfirmasi dan lanjutkan?</p>
        </div>
    </div>

    <x-slot:actions>
        <x-button label="Batal" wire:click="$set('showSaveConfirm', false)" class="btn-ghost" />
        <x-button label="Ya, Simpan" wire:click="confirmSave" class="btn-primary" spinner="confirmSave" />
    </x-slot:actions>
</x-modal>
