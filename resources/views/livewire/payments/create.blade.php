<div>
    <x-page-heading title="Catat Pembayaran" />

    <x-card class="max-w-[680px]">
        <x-form wire:submit="save">
            {{-- Tagihan yang dibayar (selalu terpilih; dibuka dari ikon Bayar di Tagihan) --}}
            <div class="mb-4">
                <label class="label"><span class="label-text">Tagihan</span></label>
                @if($selectedBill)
                    <div class="bg-base-200 rounded-lg p-3">
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
                @endif
            </div>

            <div x-data="{
                    fmt(v) {
                        let s = String(Math.round(Number(v) || 0));
                        return s === '0' ? '' : s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    },
                    onInput(e) {
                        let raw = e.target.value.replace(/\D/g, '');
                        e.target.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                        $wire.set('paid_amount', raw ? Number(raw) : 0);
                    },
                    init() {
                        let v = $wire.paid_amount;
                        if (v) this.$refs.amtInput.value = this.fmt(v);
                        $wire.$watch('paid_amount', v => {
                            this.$refs.amtInput.value = this.fmt(v);
                        });
                    }
                }">
                <label class="label"><span class="label-text font-semibold">Jumlah Bayar</span></label>
                <input type="text" inputmode="numeric" x-ref="amtInput" @input="onInput($event)"
                    class="input input-bordered w-full {{ $payInFull ? 'opacity-60 cursor-not-allowed' : '' }}"
                    placeholder="0" required @disabled($payInFull) />
                @error('paid_amount')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
                @if($remaining !== null)
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Sisa tagihan: Rp {{ number_format($remaining, 0, ',', '.') }} (nominal tidak boleh lebih)</span>
                    </label>
                @endif
                @if($remaining !== null && $remaining > 0)
                    <label class="flex items-center gap-2 mt-1 cursor-pointer select-none w-fit">
                        <input type="checkbox" wire:model.live="payInFull" class="checkbox checkbox-sm" style="accent-color:var(--lt-p);" />
                        <span class="text-sm font-semibold" style="color:var(--lt-p);">Lunasi (Rp {{ number_format($remaining, 0, ',', '.') }})</span>
                    </label>
                @endif
            </div>
            <x-input label="Tanggal Bayar" wire:model="payment_date" type="date" :max="now()->format('Y-m-d')" required />
            <x-select label="Metode" wire:model="payment_method" :options="[
                ['value' => 'tunai', 'label' => 'Tunai'],
                ['value' => 'transfer', 'label' => 'Transfer'],
                ['value' => 'lainnya', 'label' => 'Lainnya'],
            ]" option-value="value" option-label="label" required />

            <x-slot:actions>
                <x-button label="Batal" link="{{ $this->backHref('payments.index') }}" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-card>
</div>
