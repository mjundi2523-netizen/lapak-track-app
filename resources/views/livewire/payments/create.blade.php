<div>
    <x-page-heading title="Catat Pembayaran" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            {{-- Bill Selection --}}
            <div class="mb-4">
                <label class="label"><span class="label-text">Tagihan</span></label>
                @if($selectedBill)
                    <div class="bg-base-200 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold">{{ $selectedBill->bill_id ?? 'Tagihan #' . $selectedBill->dbid }}</div>
                                <div class="text-sm text-base-content/70">
                                    {{ $selectedBill->holder?->name }} - {{ $selectedBill->location_label }}
                                </div>
                                <div class="text-sm">
                                    Total: Rp {{ number_format($selectedBill->total_amount, 0, ',', '.') }} |
                                    Terbayar: Rp {{ number_format($selectedBill->payments->sum('paid_amount'), 2, ',', '.') }} |
                                    Sisa: Rp {{ number_format($selectedBill->total_amount - $selectedBill->payments->sum('paid_amount'), 2, ',', '.') }}
                                </div>
                            </div>
                            <x-button icon="o-x-mark" wire:click="clearBill" class="btn-sm btn-ghost" />
                        </div>
                    </div>
                @else
                    <x-button label="Pilih Tagihan" wire:click="$set('showBillModal', true)" class="btn-outline" icon="o-document-text" />
                @endif
            </div>

            <div>
                <x-input label="Jumlah Bayar" wire:model="paid_amount" type="number" step="0.01" min="0.01"
                    :max="$remaining"
                    hint="{{ $remaining !== null ? 'Sisa tagihan: Rp ' . number_format($remaining, 0, ',', '.') . ' (nominal tidak boleh lebih)' : '' }}"
                    required />
                @if($remaining !== null && $remaining > 0)
                    <button type="button" wire:click="payFull" class="text-sm font-semibold mt-1" style="color:var(--lt-p);">
                        Bayar penuh (Rp {{ number_format($remaining, 0, ',', '.') }})
                    </button>
                @endif
            </div>
            <x-input label="Tanggal Bayar" wire:model="payment_date" type="date" :max="now()->format('Y-m-d')" required />
            <x-select label="Metode" wire:model="payment_method" :options="[
                ['value' => 'tunai', 'label' => 'Tunai'],
                ['value' => 'transfer', 'label' => 'Transfer'],
                ['value' => 'lainnya', 'label' => 'Lainnya'],
            ]" option-value="value" option-label="label" required />

            <x-slot:actions>
                <x-button label="Batal" link="{{ route('payments.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>

    {{-- Bill Picker Modal --}}
    <x-modal wire:model="showBillModal" title="Pilih Tagihan" box-class="max-w-2xl">
        <x-input placeholder="Cari tagihan..." wire:model.live.debounce="billSearch" clearable class="mb-4" />
        <div class="max-h-96 overflow-y-auto space-y-2">
            @foreach($billResults as $bill)
                <div class="bg-base-200 rounded-lg p-3 cursor-pointer hover:bg-base-300" wire:click="selectBill({{ $bill->dbid }})">
                    <div class="font-semibold">{{ $bill->bill_id ?? 'Tagihan #' . $bill->dbid }}</div>
                    <div class="text-sm text-base-content/70">
                        {{ $bill->holder?->name ?? '-' }} - {{ $bill->location_label }}
                    </div>
                    <div class="text-sm">
                        Rp {{ number_format($bill->total_amount, 0, ',', '.') }} |
                        Jatuh Tempo: {{ $bill->due_date }}
                    </div>
                </div>
            @endforeach
        </div>
        <x-slot:actions>
            <x-button label="Tutup" wire:click="$set('showBillModal', false)" class="btn-ghost" />
        </x-slot:actions>
    </x-modal>
</div>
