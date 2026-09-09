<?php

namespace App\Admin\Items\Services;

use App\Admin\Items\Exports\ItemsExport;
use App\Admin\Items\Imports\ItemsImport;
use App\Admin\Items\Values\ItemExportProfile;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItemExcelService
{
    /**
     * Download the catalog Items workbook for the given Item export family.
     */
    public function export(ItemExportProfile $profile): BinaryFileResponse
    {
        return Excel::download(new ItemsExport($profile->familyValues()), 'items.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import catalog Items from the validated workbook upload.
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new ItemsImport, $file);
    }
}
