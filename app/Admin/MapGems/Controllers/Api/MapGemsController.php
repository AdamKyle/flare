<?php

namespace App\Admin\MapGems\Controllers\Api;

use App\Admin\MapGems\Requests\MapGemIndexRequest;
use App\Admin\MapGems\Requests\StoreMapGemRequest;
use App\Admin\MapGems\Requests\UpdateMapGemRequest;
use App\Admin\MapGems\Services\MapGemService;
use App\Admin\MapGems\Transformers\MapGemDetailTransformer;
use App\Admin\MapGems\Transformers\MapGemFormOptionsTransformer;
use App\Admin\MapGems\Transformers\MapGemFormTransformer;
use App\Admin\MapGems\Transformers\MapGemListTransformer;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapGemsController extends Controller
{
    /**
     * @param  MapGemService  $mapGemService  Admin Map Gem application service.
     * @param  Pagination  $pagination  Paginator response transformer.
     * @param  MapGemListTransformer  $mapGemListTransformer  List-record transformer.
     * @param  MapGemDetailTransformer  $mapGemDetailTransformer  Detail transformer.
     * @param  MapGemFormTransformer  $mapGemFormTransformer  Form-value transformer.
     * @param  MapGemFormOptionsTransformer  $mapGemFormOptionsTransformer  Form-options transformer.
     */
    public function __construct(
        private readonly MapGemService $mapGemService,
        private readonly Pagination $pagination,
        private readonly MapGemListTransformer $mapGemListTransformer,
        private readonly MapGemDetailTransformer $mapGemDetailTransformer,
        private readonly MapGemFormTransformer $mapGemFormTransformer,
        private readonly MapGemFormOptionsTransformer $mapGemFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, Map-filtered Map Gems list.
     *
     * @param  MapGemIndexRequest  $request  Validated Map Gem list request.
     * @return JsonResponse Paginated Map Gem list JSON response.
     */
    public function index(MapGemIndexRequest $request): JsonResponse
    {
        $paginator = $this->mapGemService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->mapGemListTransformer)
        );
    }

    /**
     * Return the Admin Map Gem form options.
     *
     * @return JsonResponse Map Gem form-options JSON response.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->mapGemService->formOptions();

        return response()->json($this->mapGemFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Map Gem profile.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to transform.
     * @return JsonResponse Map Gem detail JSON response.
     */
    public function show(GameMapGemParamter $gameMapGemParamter): JsonResponse
    {
        return response()->json($this->mapGemDetailTransformer->transform($gameMapGemParamter), 200);
    }

    /**
     * Return the current field values for the given Map Gem profile, for populating the edit form.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to populate.
     * @return JsonResponse Map Gem form-value JSON response.
     */
    public function edit(GameMapGemParamter $gameMapGemParamter): JsonResponse
    {
        return response()->json($this->mapGemFormTransformer->transform($gameMapGemParamter), 200);
    }

    /**
     * Create a new Map Gem profile from the validated request.
     *
     * @param  StoreMapGemRequest  $request  Validated Map Gem creation request.
     * @return JsonResponse Created Map Gem profile JSON response.
     */
    public function store(StoreMapGemRequest $request): JsonResponse
    {
        $gameMapGemParamter = $this->mapGemService->create($request);

        return response()->json($this->mapGemFormTransformer->transform($gameMapGemParamter), 201);
    }

    /**
     * Update an existing Map Gem profile from the validated request.
     *
     * @param  UpdateMapGemRequest  $request  Validated Map Gem update request.
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to update.
     * @return JsonResponse Updated Map Gem profile JSON response.
     */
    public function update(UpdateMapGemRequest $request, GameMapGemParamter $gameMapGemParamter): JsonResponse
    {
        $gameMapGemParamter = $this->mapGemService->update($gameMapGemParamter, $request);

        return response()->json($this->mapGemFormTransformer->transform($gameMapGemParamter), 200);
    }

    /**
     * Roll a new Gem for the given Map Gem profile and return the updated detail state.
     *
     * @param  Request  $request  Current authenticated Admin request.
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to roll.
     * @return JsonResponse Updated Map Gem detail JSON response.
     */
    public function roll(Request $request, GameMapGemParamter $gameMapGemParamter): JsonResponse
    {
        $this->mapGemService->roll($gameMapGemParamter, $request->user());

        return response()->json($this->mapGemDetailTransformer->transform($gameMapGemParamter->refresh()), 200);
    }

    /**
     * Roll a Gem for every Map Gem profile that does not currently have a rolled Gem.
     *
     * @param  Request  $request  Current authenticated Admin request.
     * @return JsonResponse Bulk roll result JSON response.
     */
    public function rollAll(Request $request): JsonResponse
    {
        $result = $this->mapGemService->rollAll($request->user());

        return response()->json($result, 200);
    }

    /**
     * Activate an existing historical Gem roll as the given Map Gem profile's active roll.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile whose active roll is changing.
     * @param  Gem  $gem  Gem roll to activate.
     * @return JsonResponse Updated Map Gem detail JSON response, or a validation error response.
     */
    public function activateRoll(GameMapGemParamter $gameMapGemParamter, Gem $gem): JsonResponse
    {
        $activated = $this->mapGemService->activateRoll($gameMapGemParamter, $gem);

        if (! $activated) {
            return response()->json(['message' => 'This Gem roll does not belong to this Map Gem profile.'], 422);
        }

        return response()->json($this->mapGemDetailTransformer->transform($gameMapGemParamter->refresh()), 200);
    }
}
