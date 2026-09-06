<?php

namespace App\Admin\LocationGems\Controllers\Api;

use App\Admin\LocationGems\Requests\LocationGemIndexRequest;
use App\Admin\LocationGems\Requests\StoreLocationGemRequest;
use App\Admin\LocationGems\Requests\UpdateLocationGemRequest;
use App\Admin\LocationGems\Services\LocationGemService;
use App\Admin\LocationGems\Transformers\LocationGemDetailTransformer;
use App\Admin\LocationGems\Transformers\LocationGemFormOptionsTransformer;
use App\Admin\LocationGems\Transformers\LocationGemFormTransformer;
use App\Admin\LocationGems\Transformers\LocationGemListTransformer;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationGemsController extends Controller
{
    /**
     * @param  LocationGemService  $locationGemService  Admin Location Gem application service.
     * @param  Pagination  $pagination  Paginator response transformer.
     * @param  LocationGemListTransformer  $locationGemListTransformer  List-record transformer.
     * @param  LocationGemDetailTransformer  $locationGemDetailTransformer  Detail transformer.
     * @param  LocationGemFormTransformer  $locationGemFormTransformer  Form-value transformer.
     * @param  LocationGemFormOptionsTransformer  $locationGemFormOptionsTransformer  Form-options transformer.
     */
    public function __construct(
        private readonly LocationGemService $locationGemService,
        private readonly Pagination $pagination,
        private readonly LocationGemListTransformer $locationGemListTransformer,
        private readonly LocationGemDetailTransformer $locationGemDetailTransformer,
        private readonly LocationGemFormTransformer $locationGemFormTransformer,
        private readonly LocationGemFormOptionsTransformer $locationGemFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, filtered Location Gems list.
     *
     * @param  LocationGemIndexRequest  $request  Validated Location Gem list request.
     * @return JsonResponse Paginated Location Gem list JSON response.
     */
    public function index(LocationGemIndexRequest $request): JsonResponse
    {
        $paginator = $this->locationGemService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->locationGemListTransformer)
        );
    }

    /**
     * Return the Admin Location Gem form options.
     *
     * @return JsonResponse Location Gem form-options JSON response.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->locationGemService->formOptions();

        return response()->json($this->locationGemFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Location Gem profile.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to transform.
     * @return JsonResponse Location Gem detail JSON response.
     */
    public function show(GameLocationGemParamter $gameLocationGemParamter): JsonResponse
    {
        return response()->json($this->locationGemDetailTransformer->transform($gameLocationGemParamter), 200);
    }

    /**
     * Return the current field values for the given Location Gem profile, for populating the edit form.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to populate.
     * @return JsonResponse Location Gem form-value JSON response.
     */
    public function edit(GameLocationGemParamter $gameLocationGemParamter): JsonResponse
    {
        return response()->json($this->locationGemFormTransformer->transform($gameLocationGemParamter), 200);
    }

    /**
     * Create a new Location Gem profile from the validated request.
     *
     * @param  StoreLocationGemRequest  $request  Validated Location Gem creation request.
     * @return JsonResponse Created Location Gem profile JSON response.
     */
    public function store(StoreLocationGemRequest $request): JsonResponse
    {
        $gameLocationGemParamter = $this->locationGemService->create($request);

        return response()->json($this->locationGemFormTransformer->transform($gameLocationGemParamter), 201);
    }

    /**
     * Update an existing Location Gem profile from the validated request.
     *
     * @param  UpdateLocationGemRequest  $request  Validated Location Gem update request.
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to update.
     * @return JsonResponse Updated Location Gem profile JSON response.
     */
    public function update(UpdateLocationGemRequest $request, GameLocationGemParamter $gameLocationGemParamter): JsonResponse
    {
        $gameLocationGemParamter = $this->locationGemService->update($gameLocationGemParamter, $request);

        return response()->json($this->locationGemFormTransformer->transform($gameLocationGemParamter), 200);
    }

    /**
     * Roll a new Gem for the given Location Gem profile and return the updated detail state.
     *
     * @param  Request  $request  Current authenticated Admin request.
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to roll.
     * @return JsonResponse Updated Location Gem detail JSON response.
     */
    public function roll(Request $request, GameLocationGemParamter $gameLocationGemParamter): JsonResponse
    {
        $this->locationGemService->roll($gameLocationGemParamter, $request->user());

        return response()->json($this->locationGemDetailTransformer->transform($gameLocationGemParamter->refresh()), 200);
    }

    /**
     * Roll a Gem for every Location Gem profile that does not currently have a rolled Gem.
     *
     * @param  Request  $request  Current authenticated Admin request.
     * @return JsonResponse Bulk roll result JSON response.
     */
    public function rollAll(Request $request): JsonResponse
    {
        $result = $this->locationGemService->rollAll($request->user());

        return response()->json($result, 200);
    }

    /**
     * Activate an existing historical Gem roll as the given Location Gem profile's active roll.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile whose active roll is changing.
     * @param  Gem  $gem  Gem roll to activate.
     * @return JsonResponse Updated Location Gem detail JSON response, or a validation error response.
     */
    public function activateRoll(GameLocationGemParamter $gameLocationGemParamter, Gem $gem): JsonResponse
    {
        $activated = $this->locationGemService->activateRoll($gameLocationGemParamter, $gem);

        if (! $activated) {
            return response()->json(['message' => 'This Gem roll does not belong to this Location Gem profile.'], 422);
        }

        return response()->json($this->locationGemDetailTransformer->transform($gameLocationGemParamter->refresh()), 200);
    }
}
