<?php

namespace App\Admin\Items\Controllers\Api;

use App\Admin\Items\Requests\ItemIndexRequest;
use App\Admin\Items\Requests\StoreItemRequest;
use App\Admin\Items\Requests\UpdateItemRequest;
use App\Admin\Items\Services\ItemService;
use App\Admin\Items\Transformers\ItemDetailTransformer;
use App\Admin\Items\Transformers\ItemFormOptionsTransformer;
use App\Admin\Items\Transformers\ItemFormTransformer;
use App\Admin\Items\Transformers\ItemListTransformer;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemsController extends Controller
{
    public function __construct(
        private readonly ItemService $itemService,
        private readonly Pagination $pagination,
        private readonly ItemListTransformer $itemListTransformer,
        private readonly ItemDetailTransformer $itemDetailTransformer,
        private readonly ItemFormTransformer $itemFormTransformer,
        private readonly ItemFormOptionsTransformer $itemFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable, profile-filtered Items list.
     */
    public function index(ItemIndexRequest $request): JsonResponse
    {
        $paginator = $this->itemService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->itemListTransformer)
        );
    }

    /**
     * Return the Admin Item form options.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->itemService->formOptions();

        return response()->json($this->itemFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Item.
     */
    public function show(Item $item): JsonResponse
    {
        return response()->json($this->itemDetailTransformer->transform($item), 200);
    }

    /**
     * Return the current field values for the given Item, for populating the edit form.
     */
    public function edit(Item $item): JsonResponse
    {
        return response()->json($this->itemFormTransformer->transform($item), 200);
    }

    /**
     * Create a new catalog Item from the validated request.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = $this->itemService->create($request);

        return response()->json($this->itemFormTransformer->transform($item), 201);
    }

    /**
     * Update an existing catalog Item from the validated request.
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $item = $this->itemService->update($item, $request);

        return response()->json($this->itemFormTransformer->transform($item), 200);
    }

    /**
     * Return the read-only deletion-impact usage report for the given Item.
     */
    public function usage(Item $item): JsonResponse
    {
        return response()->json($this->itemService->usage($item), 200);
    }

    /**
     * Delete the given catalog Item when it has no current dependencies.
     */
    public function destroy(Item $item): JsonResponse
    {
        $result = $this->itemService->delete($item);

        if (! $result['deletable']) {
            return response()->json([
                'message' => 'This Item cannot be deleted because it is still in use.',
                'blockers' => $result['blockers'],
            ], 409);
        }

        return response()->json(['message' => 'Item deleted successfully.'], 200);
    }
}
