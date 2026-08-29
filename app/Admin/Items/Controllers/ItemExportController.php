<?php

namespace App\Admin\Items\Controllers;

use App\Admin\Items\Requests\ItemExportRequest;
use App\Admin\Items\Services\ItemExcelService;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItemExportController extends Controller
{
    /**
     * @param  ItemExcelService  $itemExcelService  Item Excel export/import service.
     */
    public function __construct(
        private readonly ItemExcelService $itemExcelService,
    ) {}

    /**
     * Download the catalog Items workbook for the requested Item family profile.
     *
     * @param  ItemExportRequest  $request  Validated Item export request.
     * @return BinaryFileResponse Item workbook download response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(ItemExportRequest $request): BinaryFileResponse
    {
        return $this->itemExcelService->export($request->profile());
    }
}
