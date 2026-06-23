<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillIdGenerator
{
    /**
     * Generate a unique bill ID in the format: [EntityCode]-[YYMM]-[Sequence]
     * Example: MTR-2606-0001
     *
     * @param string $table The table to check for sequence (dealer_bills or dealer_payment)
     * @param string $entityCode MTR for main stall rent, ATR for add-on transactions
     * @param Carbon $date The date to use for YYMM portion
     * @return string
     */
    public function generate(string $table, string $entityCode, Carbon $date): string
    {
        return DB::transaction(function () use ($table, $entityCode, $date) {
            $prefix = $entityCode . '-' . $date->format('ym') . '-';

            $lastRecord = DB::table($table)
                ->where('bill_id', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('bill_id', 'desc')
                ->first();

            if ($lastRecord) {
                $lastSequence = (int) substr($lastRecord->bill_id, strlen($prefix));
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
