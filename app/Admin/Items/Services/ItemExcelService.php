<?php

namespace App\Admin\Items\Services;

use App\Admin\Items\Exports\ItemsExport;
use App\Admin\Items\Imports\ItemsImport;
use App\Admin\Items\Values\ItemProfile;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItemExcelService
{
    /**
     * Download the catalog Items workbook for the given Item family profile.
     *
     * @param  ItemProfile  $profile  Item family profile to export.
     * @return BinaryFileResponse Catalog Items workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function export(ItemProfile $profile): BinaryFileResponse
    {
        return Excel::download(new ItemsExport($this->familyValues($profile)), 'items.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import catalog Items from the validated workbook upload.
     *
     * @param  UploadedFile  $file  Uploaded Items workbook.
     * @return void Persists the imported catalog Items.
     *
     * @codeCoverageIgnore
     */
    public function import(UploadedFile $file): void
    {
        Excel::import(new ItemsImport, $file);
    }

    /**
     * Resolve the `type`/`specialty_type` values that identify the given profile's Item family.
     *
     * @param  ItemProfile  $profile  Item family profile.
     * @return array<int, string> Family type/specialty values, or an empty array for the full catalog.
     */
    private function familyValues(ItemProfile $profile): array
    {
        if ($profile->requiresSpecialtyType()) {
            return array_map(fn (ItemSpecialtyType $type): string => $type->value, ItemSpecialtyType::cases());
        }

        return $profile->types() ?? [];
    }
}
