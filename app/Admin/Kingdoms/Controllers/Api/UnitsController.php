<?php

namespace App\Admin\Kingdoms\Controllers\Api;

use App\Admin\Kingdoms\Requests\StoreUnitRequest;
use App\Admin\Kingdoms\Requests\UnitIndexRequest;
use App\Admin\Kingdoms\Requests\UpdateUnitRequest;
use App\Admin\Kingdoms\Services\UnitAdminService;
use App\Admin\Kingdoms\Transformers\UnitDetailTransformer;
use App\Admin\Kingdoms\Transformers\UnitFormTransformer;
use App\Admin\Kingdoms\Transformers\UnitListTransformer;
use App\Flare\Models\GameUnit;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UnitsController extends Controller
{
    /**
     * @param UnitAdminService $unitAdminService
     * @param Pagination $pagination
     * @param UnitListTransformer $unitListTransformer
     * @param UnitDetailTransformer $unitDetailTransformer
     * @param UnitFormTransformer $unitFormTransformer
     */
    public function __construct(
        private readonly UnitAdminService $unitAdminService,
        private readonly Pagination $pagination,
        private readonly UnitListTransformer $unitListTransformer,
        private readonly UnitDetailTransformer $unitDetailTransformer,
        private readonly UnitFormTransformer $unitFormTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Kingdom Units list.
     *
     * @param UnitIndexRequest $request
     * @return JsonResponse
     */
    public function index(UnitIndexRequest $request): JsonResponse
    {
        $paginator = $this->unitAdminService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->unitListTransformer)
        );
    }

    /**
     * Return the Admin detail representation for the given Kingdom Unit.
     *
     * @param GameUnit $gameUnit
     * @return JsonResponse
     */
    public function show(GameUnit $gameUnit): JsonResponse
    {
        return response()->json($this->unitDetailTransformer->transform(
            $gameUnit,
            $this->unitAdminService->recruitingBuildings($gameUnit),
        ));
    }

    /**
     * Return the current field values for the given Kingdom Unit, for populating the edit form.
     *
     * @param GameUnit $gameUnit
     * @return JsonResponse
     */
    public function edit(GameUnit $gameUnit): JsonResponse
    {
        return response()->json($this->unitFormTransformer->transform($gameUnit));
    }

    /**
     * Create a new Kingdom Unit from the validated request.
     *
     * @param StoreUnitRequest $request
     * @return JsonResponse
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $gameUnit = $this->unitAdminService->create($request);

        return response()->json($this->unitFormTransformer->transform($gameUnit), 201);
    }

    /**
     * Update an existing Kingdom Unit from the validated request.
     *
     * @param UpdateUnitRequest $request
     * @param GameUnit $gameUnit
     * @return JsonResponse
     */
    public function update(UpdateUnitRequest $request, GameUnit $gameUnit): JsonResponse
    {
        $gameUnit = $this->unitAdminService->update($gameUnit, $request);

        return response()->json($this->unitFormTransformer->transform($gameUnit));
    }
}
