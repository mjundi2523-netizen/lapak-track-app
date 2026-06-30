{{-- Kwitansi di-render TERSEMBUNYI (display:none). Tidak ada preview di layar:
     openReceipt() langsung memanggil window.print(); @media print yang menampilkan
     hanya isi .lt-print-overlay ini. --}}
<div class="lt-print-overlay" style="display:none;">
    @include('payments._receipt-card', ['payment' => $payment])
</div>
