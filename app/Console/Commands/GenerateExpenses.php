<?php

namespace App\Console\Commands;

use App\Services\ExpenseGenerationService;
use Illuminate\Console\Command;

class GenerateExpenses extends Command
{
    protected $signature = 'expenses:generate';

    protected $description = 'Generate occurrence pengeluaran rutin yang jatuh tempo (lazy roll-forward, idempoten).';

    public function handle(ExpenseGenerationService $service): int
    {
        $created = $service->ensureAllActive();

        $this->info("Selesai. {$created} pengeluaran rutin baru dibuat.");

        return self::SUCCESS;
    }
}
