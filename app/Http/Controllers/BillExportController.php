<?php

namespace App\Http\Controllers;

use App\Exports\BillsExport;
use App\Models\Dealer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BillExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filename = 'tagihan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new BillsExport(
            search: $request->get('search', ''),
            statusFilter: $request->get('status', ''),
            frequencyFilter: $request->get('frequency', ''),
            // Param `dealer` berupa sqid (lihat HasObfuscatedId) — decode ke did asli.
            dealerId: Dealer::decodeKey($request->get('dealer')),
            from: $request->get('from', ''),
            to: $request->get('to', ''),
        ), $filename);
    }
}
