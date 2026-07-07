<?php

namespace App\Http\Controllers;

use App\Exports\DealerImportTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DealerImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return Excel::download(new DealerImportTemplateExport, 'template-import-pedagang.xlsx');
    }
}
