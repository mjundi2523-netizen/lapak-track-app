<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\DealerBill;
use App\Models\DealerStall;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillGenerationService
{
    protected BillIdGenerator $billIdGenerator;

    public function __construct(BillIdGenerator $billIdGenerator)
    {
        $this->billIdGenerator = $billIdGenerator;
    }

    /**
     * Generate bills for a dealer-stall assignment.
     * Creates:
     *  - One MTR (main stall rent) bill for the base rental amount
     *  - ATR (add-on transaction) bills grouped by frequency
     */
    public function generateBillsForDealerStall(DealerStall $ds): void
    {
        $ds->load(['stall.paymentTerm', 'stall.addOns']);

        $stall = $ds->stall;
        $paymentTerm = $stall->paymentTerm;
        $userId = Auth::id() ?? 1;

        if (! $paymentTerm) {
            return;
        }

        $now = Carbon::now();
        $periodStart = $ds->rent_start_date ? Carbon::parse($ds->rent_start_date) : $now;
        $periodEnd = $ds->rent_end_date ? Carbon::parse($ds->rent_end_date) : $periodStart->copy()->addYear();

        DB::transaction(function () use ($ds, $stall, $paymentTerm, $userId, $now, $periodStart, $periodEnd) {
            // Generate MTR (main stall rent) bill
            $billId = $this->billIdGenerator->generate('dealer_bills', 'MTR', $now);

            DealerBill::create([
                'bill_id' => $billId,
                'dsid' => $ds->dsid,
                'total_amount' => $paymentTerm->price,
                'due_date' => $periodEnd,
                'billing_status' => 'unpaid',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'created_by' => $userId,
            ]);

            // Generate ATR (add-on) bills grouped by frequency
            $addOns = $stall->addOns;

            if ($addOns->isEmpty()) {
                return;
            }

            $groupedByFrequency = $addOns->groupBy('frequency');

            foreach ($groupedByFrequency as $frequency => $group) {
                $totalForFrequency = $group->sum('price');

                $dueDate = $this->calculateDueDate($periodStart, $frequency);

                $billId = $this->billIdGenerator->generate('dealer_bills', 'ATR', $now);

                DealerBill::create([
                    'bill_id' => $billId,
                    'dsid' => $ds->dsid,
                    'total_amount' => $totalForFrequency,
                    'due_date' => $dueDate,
                    'billing_status' => 'unpaid',
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'created_by' => $userId,
                ]);
            }
        });
    }

    /**
     * Calculate due date based on frequency from the period start.
     */
    protected function calculateDueDate(Carbon $startDate, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $startDate->copy()->addDay(),
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'annual' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }
}
