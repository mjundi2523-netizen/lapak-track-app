<?php

namespace App\Http\Controllers;

use App\Exports\CollectionExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CollectionExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filename = 'rekap-penerimaan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new CollectionExport(
            from: $request->get('from', now()->startOfMonth()->format('Y-m-d')),
            to: $request->get('to', now()->format('Y-m-d')),
            search: $request->get('search', ''),
        ), $filename);
    }
}
