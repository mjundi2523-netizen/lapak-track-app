@php
    use App\Support\Terbilang;
    use Illuminate\Support\Carbon;

    $amount = (float) $payment->paid_amount;
    $date = Carbon::parse($payment->payment_date)->locale('id');

    $dealer = $payment->dealerBill?->dealerStall?->dealer?->name ?? '-';
    $block = $payment->dealerBill?->dealerStall?->stall?->code;
    $billId = $payment->dealerBill?->bill_id;

    $note = 'Pembayaran ' . ($billId ? 'tagihan ' . $billId : 'tagihan')
        . ($block ? ' — Lapak ' . $block : '');

    $hari = $date->dayName;                       // mis. "Sabtu"
    $tanggal = $date->translatedFormat('d F Y');  // mis. "27 Juni 2026"
    $terbilang = Terbilang::make($amount) . ' Rupiah';
    $amountPlain = number_format($amount, 0, ',', '.');

    $signer = auth()->user()->name ?? 'Pengelola';
    $city = 'Bekasi';
@endphp

<div id="lt-receipt" style="background:#fff;border:2px solid #1a1a1a;padding:8px;box-shadow:0 24px 60px rgba(0,0,0,0.35);">
    <div style="border:1px solid #1a1a1a;padding:30px 34px;font-family:'Times New Roman', Georgia, serif;color:#111;">
        <div style="text-align:center;font-size:27px;font-weight:700;letter-spacing:7px;margin-bottom:28px;">TANDA TERIMA</div>

        <div style="font-size:15.5px;line-height:2.5;">
            <div>Pada hari <span style="font-style:italic;font-weight:600;">{{ $hari }}</span>, tanggal <span style="font-style:italic;font-weight:600;">{{ $tanggal }}</span> telah diterima</div>
            <div>dari : <span style="font-style:italic;font-weight:600;">{{ $dealer }}</span></div>
            <div style="display:flex;gap:8px;align-items:baseline;">
                <span style="width:140px;flex-shrink:0;">Uang Sejumlah</span>
                <span style="flex-shrink:0;">:</span>
                <span style="flex:1;border-bottom:1px dotted #1a1a1a;font-style:italic;font-weight:600;">{{ $terbilang }}</span>
            </div>
            <div style="display:flex;gap:8px;align-items:baseline;">
                <span style="width:140px;flex-shrink:0;">Untuk Pembayaran</span>
                <span style="flex-shrink:0;">:</span>
                <span style="flex:1;border-bottom:1px dotted #1a1a1a;font-style:italic;font-weight:600;">{{ $note }}</span>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:34px;gap:20px;">
            <div style="border:2px solid #1a1a1a;border-radius:6px;padding:9px 20px;font-size:19px;font-weight:700;letter-spacing:0.5px;">Rp. {{ $amountPlain }},-</div>
            <div style="text-align:center;font-size:14px;">
                <div style="margin-bottom:2px;">{{ $city }}, {{ $tanggal }}</div>
                <div style="height:46px;display:flex;align-items:center;justify-content:center;font-style:italic;font-size:20px;font-family:'Brush Script MT', cursive;">{{ $signer }}</div>
                <div style="border-top:1px solid #1a1a1a;padding-top:4px;min-width:160px;font-weight:600;">{{ $signer }}</div>
                <div style="font-size:12px;color:#555;">Penerima</div>
            </div>
        </div>
    </div>
</div>
