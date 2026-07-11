<?php

namespace App\Exports;

use App\Models\DealerBill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BillsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $statusFilter = '',
        private readonly string $frequencyFilter = '',
        private readonly array $dealerIds = [],
        private readonly string $from = '',
        private readonly string $to = '',
    ) {}

    public function collection(): Collection
    {
        return DealerBill::with([
            'dealerStall.dealer',
            'dealerStall.stall',
            'externalDealer.dealer',
            'payments' => fn ($q) => $q->where('is_voided', false),
        ])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->statusFilter, fn ($q) => $q->where('billing_status', $this->statusFilter))
            ->when($this->frequencyFilter, fn ($q) => $q->where('frequency', $this->frequencyFilter))
            ->when($this->from, fn ($q) => $q->whereDate('due_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('due_date', '<=', $this->to))
            ->when($this->dealerIds, fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('dealerStall', fn ($q2) => $q2->whereIn('did', $this->dealerIds))
                ->orWhereHas('externalDealer', fn ($q2) => $q2->whereIn('did', $this->dealerIds))
            ))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No. Tagihan',
            'Jenis',
            'Frekuensi',
            'Pedagang',
            'Lapak',
            'Jumlah (Rp)',
            'Terbayar (Rp)',
            'Sisa (Rp)',
            'Jatuh Tempo',
            'Periode Mulai',
            'Periode Selesai',
            'Status',
        ];
    }

    public function map($row): array
    {
        $typeLabel = [
            'MTR' => 'Sewa',
            'MAT' => 'Sewa + Add-on',
            'AAT' => 'Add-on',
            'ATR' => 'Add-on (jadwal)',
            'EXT' => 'Eksternal',
        ];
        $freqLabel = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'annual' => 'Tahunan',
        ];
        $statusLabel = [
            'unpaid' => 'Belum Bayar',
            'installment' => 'Cicilan',
            'pending' => 'Pending',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
        ];

        $paid = $row->payments->sum('paid_amount');

        return [
            $row->bill_id ?? '-',
            $typeLabel[$row->bill_type] ?? $row->bill_type,
            $freqLabel[$row->frequency] ?? $row->frequency,
            $row->holder?->name ?? '-',
            $row->location_label,
            (int) $row->total_amount,
            (int) $paid,
            max((int) $row->total_amount - (int) $paid, 0),
            $row->due_date?->format('d/m/Y') ?? '-',
            $row->period_start?->format('d/m/Y') ?? '-',
            $row->period_end?->format('d/m/Y') ?? '-',
            $statusLabel[$row->billing_status] ?? $row->billing_status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F39F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
