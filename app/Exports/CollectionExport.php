<?php

namespace App\Exports;

use App\Models\DealerPayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $from,
        private readonly string $to,
        private readonly string $search = '',
    ) {}

    public function title(): string
    {
        return 'Rekap Penerimaan';
    }

    public function collection(): Collection
    {
        return DealerPayment::query()
            ->where('is_voided', false)
            ->when($this->from, fn ($q) => $q->whereDate('payment_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('payment_date', '<=', $this->to))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('bill_id', 'like', "%{$this->search}%")
                ->orWhereHas('dealerBill.dealerStall.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('dealerBill.externalDealer.dealer', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"))
            ))
            ->with(['dealerBill.dealerStall.dealer', 'dealerBill.dealerStall.stall', 'dealerBill.externalDealer.dealer'])
            ->orderBy('payment_date')
            ->orderBy('dpid')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Tagihan', 'Pedagang', 'Lokasi', 'Metode', 'Jumlah (Rp)'];
    }

    public function map($p): array
    {
        return [
            $p->payment_date?->format('d/m/Y') ?? '-',
            $p->bill_id ?? '-',
            $p->dealerBill?->holder?->name ?? '-',
            $p->dealerBill?->location_label ?? '-',
            ucfirst($p->payment_method),
            (int) $p->paid_amount,
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
