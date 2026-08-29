<?php

namespace App\Admin\Items\Controllers\Api;

use App\Admin\Items\Requests\ItemImportRequest;
use App\Admin\Items\Services\ItemExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemImportController extends Controller
{
    /**
     * @param  ItemExcelService  $itemExcelService  Item Excel export/import service.
     */
    public function __construct(
        private readonly ItemExcelService $itemExcelService,
    ) {}

    /**
     * Import validated catalog Items and return the success response.
     *
     * @param  ItemImportRequest  $request  Validated Item import request.
     * @return JsonResponse Import success JSON response.
     *
     * @codeCoverageIgnore
     */
    public function __invoke(ItemImportRequest $request): JsonResponse
    {
        $this->itemExcelService->import($request->file('items_import'));

        return response()->json([
            'message' => 'Items imported successfully.',
        ], 200);
    }
}
