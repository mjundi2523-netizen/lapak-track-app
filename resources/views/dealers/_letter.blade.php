@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();
    $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

    // Lapak yang ditampilkan di surat: rental aktif pertama, fallback rental terbaru.
    $rentals = $dealer->dealerStalls;
    $rental = $rentals->first(fn ($r) => is_null($r->rent_end_date) || $r->rent_end_date->gt($today))
        ?? $rentals->sortByDesc('rent_start_date')->first();
    $stall = $rental?->stall;

    $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    $issue = $rental?->rent_start_date ? Carbon::parse($rental->rent_start_date) : $today;
    // Nomor surat: input bebas user (dealer.letter_no); fallback auto bila kosong.
    $noNomor = $dealer->letter_no ?: (($stall?->block ?? '—') . ' / PSR-N / ' . ($romans[$issue->month] ?? '') . ' / ' . $issue->year);

    $masaBerlaku = $rental ? ($fmt($rental->rent_start_date) . ' s/d ' . $fmt($rental->rent_end_date)) : '-';

    $fields = [
        'Nama Lengkap'    => $dealer->name,
        'Tempat/Tgl Lahir' => $fmt($dealer->birth_date),
        'Alamat'          => $dealer->address ?: '-',
        'N I K'           => $dealer->nik,
        'Warga Negara'    => 'INDONESIA',
        'Kios / Los Nomor' => $stall?->block ?? '-',
        'Ukuran'          => $stall?->size ?: '-',
        'Status Bangunan' => 'Sewa/Kontrak',
        'Jenis Usaha'     => $dealer->product_type ?: '-',
        'Masa Berlaku'    => $masaBerlaku,
    ];

    $rules = [
        'Kartu Pedagang ini bukan sebagai Bukti Kepemilikan, akan tetapi hanya sebagai Bukti diakuinya sebagai Anggota Pedagang pada Pasar Swasta Nusantara di Wilayah Pengelolaan PT. Bintang Inter Nusantara.',
        'Dilarang Keras memindahtangankan atau mengoperalihkan tempat Berdagang dan akan dikenakan Sanksi Pidana bila Melanggar ketentuan yang berlaku.',
        'Pedagang Wajib membayar Iuran seperti Iuran Kebersihan, Listrik, Keamanan, Air Bersih serta Meja untuk setiap harinya dan apabila kedapatan dalam 1 (satu) bulan terjadi 3 (tiga) kali menunggak atau tidak membayar maka Kartu Pedagang ini akan di Cabut dan Pengelola akan menggantikan dengan Pedagang yang baru tanpa mengembalikan uang apapun pada Pedagang yang Melanggar.',
        'Bila tempat Berdagang tutup berturut-turut (Tidak difungsikan) selama 10 (sepuluh) hari, maka Kartu Pedagang ini akan di Cabut secara sepihak dan dinyatakan tidak berlaku tanpa memberikan ganti rugi kepada Pemegang Kartu.',
        'Pedagang diwajibkan memelihara dan menjaga Kebersihan, Keindahan dan Keamanan di Lokasi Sekitar tempat Berdagang.',
        'Tempat Berdagang tidak boleh dipergunakan sebagai Gudang atau Penyimpanan barang.',
        'Tidak diperbolehkan merubah atau menambah bentuk tempat berdagang tanpa seizin PT. Bintang Inter Nusantara.',
        'Tidak boleh menghalangi lalu lintas Pejalan Kaki dan Konsumen pada Gang di Area Bangunan maupun Jalan Kendaraan di Lingkungan Pengelolaan PT. Bintang Inter Nusantara.',
        'Bila PT. Bintang Inter Nusantara memerlukan Tempat Lahan yang dipergunakan sebagai tempat berdagang maka Pemegang Kartu ini Wajib mengosongkan tempat Berdagang tersebut tanpa meminta ganti rugi.',
        'Diwajibkan mendaftar ulang apabila Masa Berlaku Kartu Pedagang ini habis.',
        'Jika Butir 1 s/d 10 tidak dilaksanakan maka tempat berdagang di ambil alih oleh PT. Bintang Inter Nusantara, serta ditarik kembali Kartu Pedagangnya dan dinyatakan tidak berlaku lagi.',
    ];
@endphp

<div class="lt-print-overlay" wire:click="closeLetter"
     style="position:fixed;inset:0;z-index:60;background:rgba(15,18,28,0.55);display:flex;align-items:flex-start;justify-content:center;padding:24px;overflow:auto;">
    <div onclick="event.stopPropagation()" style="width:1040px;max-width:100%;">
        <div id="lt-letter"
             style="background:#fff;padding:18px 28px 14px;font-family:'Times New Roman', Georgia, serif;color:#1a1a1a;box-shadow:0 24px 60px rgba(0,0,0,0.35);">

            {{-- ===== Satu grid 2 kolom: tiap kolom = konten atas + ttd di bawah.
                     border-left kolom kanan = garis tengah yang memanjang penuh ke bawah. ===== --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:stretch;">

                {{-- KIRI: data diri + ttd pedagang (didorong ke bawah, rata kanan/mentok garis tengah) --}}
                <div style="display:flex;flex-direction:column;">
                    <div>
                        <div style="display:inline-block;border:1px solid #1a1a1a;padding:4px 11px;font-size:12.5px;font-weight:700;margin-bottom:14px;">
                            No. : {{ $noNomor }}
                        </div>

                        <div style="text-align:center;font-size:19px;font-weight:700;letter-spacing:1px;margin-bottom:16px;">PASAR SWASTA NUSANTARA</div>

                        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                            @foreach($fields as $label => $value)
                                <tr>
                                    <td style="vertical-align:top;padding:4px 0;width:17px;">{{ $loop->iteration }}.</td>
                                    <td style="vertical-align:top;padding:4px 0;width:126px;">{{ $label }}</td>
                                    <td style="vertical-align:top;padding:4px 6px;">:</td>
                                    <td style="vertical-align:top;padding:4px 0;border-bottom:1px dotted #1a1a1a;font-weight:600;">{{ $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>

                    <div style="margin-top:auto;padding-top:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;">
                        {{-- Pas foto: kiri jauh, vertical-center terhadap kolom ttd --}}
                        <div style="width:58px;height:74px;border:1px solid #1a1a1a;display:flex;align-items:center;justify-content:center;text-align:center;font-size:9px;color:#555;line-height:1.25;flex-shrink:0;">Pas Photo<br>Uk. 3x4</div>

                        {{-- Kolom ttd pedagang: kanan (mentok dekat garis tengah) --}}
                        <div style="width:230px;text-align:center;font-size:12.5px;">
                            <div>Bekasi, {{ $fmt($issue) }}</div>
                            <div style="margin-bottom:6px;">Pedagang,</div>
                            <div style="height:42px;"></div>
                            <div style="border-bottom:1px dotted #1a1a1a;"></div>
                            <div style="margin-top:4px;font-weight:700;">{{ $dealer->name }}</div>
                        </div>
                    </div>
                </div>

                {{-- KANAN: peraturan + ttd direktur (didorong ke bawah). border-left = garis tengah penuh. --}}
                <div style="border-left:1px solid #1a1a1a;padding-left:30px;display:flex;flex-direction:column;">
                    <div>
                        <div style="text-align:center;font-size:12.5px;font-weight:700;line-height:1.3;margin-bottom:10px;">
                            PERATURAN - PERATURAN / KETENTUAN-KETENTUAN<br>YANG HARUS DITAATI DAN DILAKSANAKAN
                        </div>

                        <ol style="margin:0;padding-left:18px;font-size:10.5px;line-height:1.38;text-align:justify;">
                            @foreach($rules as $rule)
                                <li style="margin-bottom:4.5px;">{{ $rule }}</li>
                            @endforeach
                        </ol>
                    </div>

                    <div style="margin-top:auto;padding-top:18px;display:flex;justify-content:flex-end;">
                        <div style="width:245px;text-align:center;font-size:12.5px;">
                            <div>Bekasi, ………………, 20……</div>
                            <div style="font-weight:700;margin-bottom:2px;">PT. BINTANG INTER NUSANTARA</div>
                            <div style="height:48px;"></div>
                            <div style="font-weight:700;text-decoration:underline;">M. FARHAN YAKOB</div>
                            <div>Direktur Utama</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <button type="button" wire:click="closeLetter"
                    class="h-[42px] px-[18px] rounded-[10px] text-sm font-semibold text-[#3f3f46] bg-white hover:bg-[#f4f4f5]">Tutup</button>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 h-[42px] px-[22px] rounded-[10px] text-sm font-semibold text-white" style="background:var(--lt-p);">
                <x-icon name="o-printer" class="w-[17px] h-[17px]" /> Cetak Surat
            </button>
        </div>
    </div>
</div>
