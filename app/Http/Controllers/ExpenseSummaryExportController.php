<?php

namespace App\Http\Controllers;

use App\Exports\ExpenseSummaryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseSummaryExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filename = 'rekap-pengeluaran-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ExpenseSummaryExport(
            year: (int) $request->get('year', now()->year),
            month: (int) $request->get('month', 0),
            category: (string) $request->get('category', ''),
        ), $filename);
    }
}
