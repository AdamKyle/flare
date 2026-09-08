<?php

namespace App\Admin\Items\Controllers\Api;

use App\Admin\Items\Requests\ItemImportRequest;
use App\Admin\Items\Services\ItemExcelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemImportController extends Controller
{
    public function __construct(
        private readonly ItemExcelService $itemExcelService,
    ) {}

    /**
     * Import validated catalog Items and return the success response.
     */
    public function __invoke(ItemImportRequest $request): JsonResponse
    {
        $this->itemExcelService->import($request->file('items_import'));

        return response()->json([
            'message' => 'Items imported successfully.',
        ], 200);
    }
}
