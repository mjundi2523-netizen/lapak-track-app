<?php

namespace App\Http\Controllers;

use App\Exports\DealerSummaryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DealerSummaryExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filename = 'rekap-pedagang-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new DealerSummaryExport(
            from: $request->get('from', now()->startOfMonth()->format('Y-m-d')),
            to: $request->get('to', now()->endOfMonth()->format('Y-m-d')),
            search: $request->get('search', ''),
            onlyActive: (bool) $request->get('only_active', false),
        ), $filename);
    }
}
