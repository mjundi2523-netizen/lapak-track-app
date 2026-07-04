<?php

namespace App\Exports;

use App\Models\Expense;
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

class ExpenseSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $year,
        private readonly int $month = 0,
        private readonly string $category = '',
    ) {}

    public function title(): string
    {
        return 'Rekap Pengeluaran';
    }

    public function collection(): Collection
    {
        return Expense::query()
            ->where('is_voided', false)
            ->where('status', 'posted')
            ->whereYear('expense_date', $this->year)
            ->when($this->month, fn ($q) => $q->whereMonth('expense_date', $this->month))
            ->when($this->category, fn ($q) => $q->where('ecid', $this->category))
            ->with('category')
            ->orderBy('expense_date')
            ->orderBy('xpid')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Judul', 'Kategori', 'Metode', 'Sumber', 'Jumlah (Rp)'];
    }

    public function map($e): array
    {
        return [
            $e->expense_date?->format('d/m/Y') ?? '-',
            $e->title,
            $e->category?->name ?? '-',
            ucfirst($e->payment_method),
            $e->rxid ? 'Rutin' : 'Manual',
            (int) $e->amount,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEF4444']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
