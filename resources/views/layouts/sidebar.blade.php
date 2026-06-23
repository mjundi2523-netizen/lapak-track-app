<x-menu activate-by-route>
    <x-menu-separator title="Master Data" />
    <x-menu-item title="Dashboard" icon="o-home" link="{{ route('dashboard') }}" />
    <x-menu-item title="Aturan Bayar" icon="o-banknotes" link="{{ route('payment-terms.index') }}" />
    <x-menu-item title="Biaya Lain-lain" icon="o-plus-circle" link="{{ route('add-ons.index') }}" />
    <x-menu-item title="Lapak" icon="o-building-storefront" link="{{ route('stalls.index') }}" />

    <x-menu-separator title="Transaksi" />
    <x-menu-item title="Registrasi Pedagang" icon="o-user-plus" link="{{ route('dealers.index') }}" />
    <x-menu-item title="Tagihan" icon="o-document-text" link="{{ route('bills.index') }}" />
    <x-menu-item title="Pembayaran" icon="o-credit-card" link="{{ route('payments.index') }}" />
</x-menu>
