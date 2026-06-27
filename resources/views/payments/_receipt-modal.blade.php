{{-- Overlay modal kwitansi. Komponen pemanggil wajib punya method closeReceipt(). --}}
<div wire:click="closeReceipt"
     style="position:fixed;inset:0;z-index:60;background:rgba(15,18,28,0.55);display:flex;align-items:center;justify-content:center;padding:24px;overflow:auto;">
    <div onclick="event.stopPropagation()" style="width:540px;max-width:100%;">
        @include('payments._receipt-card', ['payment' => $payment])

        <div class="no-print" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <button type="button" wire:click="closeReceipt"
                    class="h-[42px] px-[18px] rounded-[10px] text-sm font-semibold text-[#3f3f46] bg-white hover:bg-[#f4f4f5]">Tutup</button>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 h-[42px] px-[22px] rounded-[10px] text-sm font-semibold text-white" style="background:var(--lt-p);">
                <x-icon name="o-printer" class="w-[17px] h-[17px]" /> Cetak
            </button>
        </div>
    </div>
</div>
