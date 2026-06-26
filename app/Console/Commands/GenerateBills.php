<?php

namespace App\Console\Commands;

use App\Services\BillGenerationService;
use Illuminate\Console\Command;

class GenerateBills extends Command
{
    protected $signature = 'bills:generate';

    protected $description = 'Generate tagihan periode berjalan untuk semua rental aktif (lazy roll-forward, idempoten).';

    public function handle(BillGenerationService $service): int
    {
        $created = $service->ensureAllActive();

        $this->info("Selesai. {$created} tagihan baru dibuat.");

        return self::SUCCESS;
    }
}
