@php
    use Illuminate\Support\Carbon;

    $bill     = $invoiceBill;
    $paid     = $bill->payments->where('is_voided', false)->sum('paid_amount');
    $sisa     = max((float) $bill->total_amount - (float) $paid, 0);
    $breakdown = $bill->breakdown();

    $dealer   = $bill->holder?->name ?? '-';
    $location = $bill->location_label;
    $dueDate  = $bill->due_date?->translatedFormat('d F Y') ?? '-';
    $period   = ($bill->period_start?->format('d/m/Y') ?? '-') . ' s/d ' . ($bill->period_end?->format('d/m/Y') ?? '-');

    $statusLabel = [
        'unpaid'      => 'Belum Bayar',
        'installment' => 'Cicilan',
        'pending'     => 'Menunggu',
        'paid'        => 'Lunas',
        'cancelled'   => 'Dibatalkan',
    ];
    $freqLabel = [
        'daily'   => 'Harian',
        'weekly'  => 'Mingguan',
        'monthly' => 'Bulanan',
        'annual'  => 'Tahunan',
    ];

    $signer = auth()->user()->name ?? 'Pengelola';
    $city   = 'Bekasi';
    $printDate = Carbon::now()->locale('id')->translatedFormat('d F Y');
@endphp

<div id="lt-invoice" style="background:#fff; padding:8px; box-shadow:0 24px 60px rgba(0,0,0,0.35); max-width:640px; margin:0 auto;">
    <div style="border:2px solid #1a1a1a; padding:32px 36px; font-family:'Times New Roman', Georgia, serif; color:#111;">

        {{-- Header --}}
        <div style="text-align:center; border-bottom:2px solid #1a1a1a; padding-bottom:14px; margin-bottom:20px;">
            <div style="font-size:20px; font-weight:700; letter-spacing:2px; text-transform:uppercase;">Pasar Swasta Nusantara</div>
            <div style="font-size:13px; margin-top:2px; color:#444;">Dikelola oleh PT. Bintang Inter Nusantara</div>
            <div style="font-size:22px; font-weight:700; letter-spacing:6px; margin-top:12px;">INVOICE / TAGIHAN</div>
            <div style="font-size:13px; margin-top:4px; color:#444;">No. {{ $bill->bill_id ?? '-' }}</div>
        </div>

        {{-- Info tagihan --}}
        <table style="width:100%; font-size:14px; line-height:2; margin-bottom:20px;">
            <tr>
                <td style="width:150px; font-weight:600;">Kepada</td>
                <td style="width:12px;">:</td>
                <td><strong>{{ $dealer }}</strong></td>
                <td style="width:130px; font-weight:600; text-align:right;">Tanggal Cetak</td>
                <td style="width:12px;">:</td>
                <td style="text-align:right;">{{ $printDate }}</td>
            </tr>
            <tr>
                <td style="font-weight:600;">Lokasi</td>
                <td>:</td>
                <td>{{ $location }}</td>
                <td style="font-weight:600; text-align:right;">Jatuh Tempo</td>
                <td>:</td>
                <td style="text-align:right;">{{ $dueDate }}</td>
            </tr>
            <tr>
                <td style="font-weight:600;">Periode</td>
                <td>:</td>
                <td>{{ $period }}</td>
                <td style="font-weight:600; text-align:right;">Frekuensi</td>
                <td>:</td>
                <td style="text-align:right;">{{ $freqLabel[$bill->frequency] ?? $bill->frequency }}</td>
            </tr>
        </table>

        {{-- Rincian --}}
        <table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:20px;">
            <thead>
                <tr style="background:#1a1a1a; color:#fff;">
                    <th style="padding:8px 12px; text-align:left;">Keterangan</th>
                    <th style="padding:8px 12px; text-align:right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdown as $item)
                    <tr>
                        <td style="padding:8px 12px; border-bottom:1px solid #ddd;">{{ $item['label'] }}</td>
                        <td style="padding:8px 12px; border-bottom:1px solid #ddd; text-align:right;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="padding:8px 12px; border-bottom:1px solid #ddd;">Tagihan</td>
                        <td style="padding:8px 12px; border-bottom:1px solid #ddd; text-align:right;">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight:700; font-size:15px;">
                    <td style="padding:10px 12px; border-top:2px solid #1a1a1a;">Total</td>
                    <td style="padding:10px 12px; border-top:2px solid #1a1a1a; text-align:right;">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                </tr>
                @if($paid > 0)
                    <tr style="color:#15803d;">
                        <td style="padding:4px 12px;">Sudah Dibayar</td>
                        <td style="padding:4px 12px; text-align:right;">Rp {{ number_format($paid, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight:700; color:#b91c1c;">
                        <td style="padding:4px 12px;">Sisa Tagihan</td>
                        <td style="padding:4px 12px; text-align:right;">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>

        {{-- Status --}}
        <div style="text-align:center; margin-bottom:24px;">
            <span style="display:inline-block; padding:4px 18px; border:1.5px solid #1a1a1a; border-radius:4px; font-weight:700; font-size:13px; letter-spacing:1px; text-transform:uppercase;">
                {{ $statusLabel[$bill->billing_status] ?? $bill->billing_status }}
            </span>
        </div>

        {{-- Footer TTD --}}
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:16px;">
            <div style="font-size:13px; color:#555; max-width:300px; line-height:1.6;">
                Harap segera melunasi tagihan ini sebelum atau pada tanggal jatuh tempo. Hubungi pengelola pasar untuk informasi lebih lanjut.
            </div>
            <div style="text-align:center; font-size:13px;">
                <div>{{ $city }}, {{ $printDate }}</div>
                <div style="height:52px;"></div>
                <div style="border-top:1px solid #1a1a1a; padding-top:4px; min-width:160px; font-weight:700;">{{ $signer }}</div>
                <div style="font-size:12px; color:#555;">Pengelola</div>
            </div>
        </div>

    </div>
</div>
