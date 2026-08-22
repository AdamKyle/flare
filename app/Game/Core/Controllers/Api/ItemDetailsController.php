<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Item;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemDetailsController extends Controller
{
    public function __construct(private readonly ItemTransformer $itemTransformer) {}

    /**
     * Return the transformed catalog details for the given Item.
     *
     * @param  Item  $item  The Item to transform.
     * @return JsonResponse The transformed Item details.
     */
    public function show(Item $item): JsonResponse
    {
        return response()->json($this->itemTransformer->transform($item));
    }
}
