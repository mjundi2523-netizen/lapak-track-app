<div>
    <x-header title="Detail Pedagang" separator>
        <x-slot:actions>
            <x-button label="Edit" link="{{ route('dealers.edit', $dealer) }}" class="btn-primary" icon="o-pencil" />
            <x-button label="Kembali" link="{{ route('dealers.index') }}" class="btn-ghost" icon="o-arrow-left" />
        </x-slot:actions>
    </x-header>

    <x-card title="Informasi Pedagang">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="font-semibold">NIK:</span> {{ $dealer->nik }}</div>
            <div><span class="font-semibold">Nama:</span> {{ $dealer->name }}</div>
            <div><span class="font-semibold">Tanggal Lahir:</span> {{ $dealer->birth_date?->format('d-m-Y') ?? '-' }}</div>
            <div><span class="font-semibold">Alamat:</span> {{ $dealer->address }}</div>
            <div><span class="font-semibold">Telepon 1:</span> {{ $dealer->phone_number_1 }}</div>
            <div><span class="font-semibold">Telepon 2:</span> {{ $dealer->phone_number_2 ?? '-' }}</div>
            <div><span class="font-semibold">Jenis Dagangan:</span> {{ $dealer->product_type ?? '-' }}</div>
            <div>
                <span class="font-semibold">Status:</span>
                <x-badge :value="$dealer->status === 'active' ? 'Aktif' : 'Nonaktif'" :class="$dealer->status === 'active' ? 'badge-success' : 'badge-ghost'" />
            </div>
        </div>
    </x-card>

    @foreach($dealer->dealerStalls as $ds)
        <x-card :title="'Lapak: ' . $ds->stall->block" class="mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><span class="font-semibold">Mulai Sewa:</span> {{ $ds->rent_start_date }}</div>
                <div><span class="font-semibold">Akhir Sewa:</span> {{ $ds->rent_end_date ?? '-' }}</div>
            </div>

            @if($ds->dealerBills->count() > 0)
                <h4 class="font-semibold mb-2">Tagihan</h4>
                <x-table :headers="[
                    ['key' => 'bill_id', 'label' => 'No. Tagihan'],
                    ['key' => 'total_amount', 'label' => 'Jumlah'],
                    ['key' => 'due_date', 'label' => 'Jatuh Tempo'],
                    ['key' => 'billing_status', 'label' => 'Status'],
                ]" :rows="$ds->dealerBills" striped>
                    @scope('cell_total_amount', $row)
                        Rp {{ number_format($row->total_amount, 0, ',', '.') }}
                    @endscope
                    @scope('cell_billing_status', $row)
                        <x-badge :value="match($row->billing_status) {
                            'paid' => 'Lunas',
                            'installment' => 'Cicilan',
                            'unpaid' => 'Belum Bayar',
                            'pending' => 'Pending',
                            default => $row->billing_status,
                        }" :class="match($row->billing_status) {
                            'paid' => 'badge-success',
                            'installment' => 'badge-warning',
                            'unpaid' => 'badge-error',
                            'pending' => 'badge-ghost',
                            default => 'badge-ghost',
                        }" />
                    @endscope
                </x-table>
            @endif
        </x-card>
    @endforeach
</div>
