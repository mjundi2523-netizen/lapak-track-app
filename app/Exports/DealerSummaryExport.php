<?php

namespace App\Exports;

use App\Models\Dealer;
use App\Models\DealerBill;
use App\Models\DealerPayment;
use Illuminate\Support\Carbon;
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

class DealerSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    private Collection $summaries;

    public function __construct(
        private readonly string $from,
        private readonly string $to,
        private readonly string $search = '',
        private readonly bool $onlyActive = false,
    ) {}

    public function title(): string
    {
        return 'Rekap Pedagang';
    }

    public function collection(): Collection
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to   = Carbon::parse($this->to)->endOfDay();

        $allDids = Dealer::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
            ))
            ->pluck('did');

        $stallBills = DealerBill::join('dealer_stall', 'dealer_bills.dsid', '=', 'dealer_stall.dsid')
            ->whereIn('dealer_stall.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->selectRaw('dealer_stall.did as did, COUNT(*) as bill_count, SUM(dealer_bills.total_amount) as total_billed')
            ->groupBy('dealer_stall.did')->get()->keyBy('did');

        $stallPaid = DealerPayment::join('dealer_bills', 'dealer_payment.dbid', '=', 'dealer_bills.dbid')
            ->join('dealer_stall', 'dealer_bills.dsid', '=', 'dealer_stall.dsid')
            ->whereIn('dealer_stall.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->where('dealer_payment.is_voided', false)
            ->selectRaw('dealer_stall.did as did, SUM(dealer_payment.paid_amount) as total_paid, MAX(dealer_payment.payment_date) as last_payment_date')
            ->groupBy('dealer_stall.did')->get()->keyBy('did');

        $extBills = DealerBill::join('external_dealers', 'dealer_bills.edid', '=', 'external_dealers.edid')
            ->whereIn('external_dealers.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->selectRaw('external_dealers.did as did, COUNT(*) as bill_count, SUM(dealer_bills.total_amount) as total_billed')
            ->groupBy('external_dealers.did')->get()->keyBy('did');

        $extPaid = DealerPayment::join('dealer_bills', 'dealer_payment.dbid', '=', 'dealer_bills.dbid')
            ->join('external_dealers', 'dealer_bills.edid', '=', 'external_dealers.edid')
            ->whereIn('external_dealers.did', $allDids)
            ->whereBetween('dealer_bills.due_date', [$from, $to])
            ->whereNotIn('dealer_bills.billing_status', ['cancelled'])
            ->where('dealer_payment.is_voided', false)
            ->selectRaw('external_dealers.did as did, SUM(dealer_payment.paid_amount) as total_paid, MAX(dealer_payment.payment_date) as last_payment_date')
            ->groupBy('external_dealers.did')->get()->keyBy('did');

        $this->summaries = $allDids->mapWithKeys(function ($did) use ($stallBills, $stallPaid, $extBills, $extPaid) {
            $billCount   = ($stallBills[$did]->bill_count ?? 0) + ($extBills[$did]->bill_count ?? 0);
            $totalBilled = (float) ($stallBills[$did]->total_billed ?? 0) + (float) ($extBills[$did]->total_billed ?? 0);
            $totalPaid   = (float) ($stallPaid[$did]->total_paid ?? 0) + (float) ($extPaid[$did]->total_paid ?? 0);
            $d1 = $stallPaid[$did]->last_payment_date ?? null;
            $d2 = $extPaid[$did]->last_payment_date ?? null;
            return [$did => [
                'bill_count'   => (int) $billCount,
                'total_billed' => $totalBilled,
                'total_paid'   => $totalPaid,
                'outstanding'  => max($totalBilled - $totalPaid, 0),
                'last_payment' => $d1 && $d2 ? max($d1, $d2) : ($d1 ?? $d2),
            ]];
        });

        $dealers = Dealer::query()
            ->with(['dealerStalls' => fn ($q) => $q->where('deleted', false)->with('stall:sid,block')])
            ->whereIn('did', $this->onlyActive
                ? $allDids->filter(fn ($did) => $this->summaries[$did]['bill_count'] > 0)->values()
                : $allDids)
            ->orderBy('name')
            ->get();

        return $dealers;
    }

    public function headings(): array
    {
        return ['Nama', 'NIK', 'Kondisi', 'Lokasi', 'Jml Tagihan', 'Total Tagihan (Rp)', 'Terbayar (Rp)', 'Tunggakan (Rp)', 'Terakhir Bayar'];
    }

    public function map($row): array
    {
        $s = $this->summaries[$row->did] ?? ['bill_count' => 0, 'total_billed' => 0, 'total_paid' => 0, 'outstanding' => 0, 'last_payment' => null];
        $condLabel = ['regular' => 'Regular', 'new' => 'Baru', 'external' => 'Eksternal'];
        $location = $row->dealerStalls->map(fn ($ds) => $ds->stall?->block)->filter()->implode(', ') ?: ($row->dealer_condition === 'external' ? 'Eksternal' : '-');

        return [
            $row->name,
            $row->nik,
            $condLabel[$row->dealer_condition] ?? $row->dealer_condition,
            $location,
            $s['bill_count'],
            (int) $s['total_billed'],
            (int) $s['total_paid'],
            (int) $s['outstanding'],
            $s['last_payment'] ? Carbon::parse($s['last_payment'])->format('d/m/Y') : '-',
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
