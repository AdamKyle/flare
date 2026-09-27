<?php

namespace App\Admin\Kingdoms\Controllers\Api;

use App\Admin\Kingdoms\Requests\BuildingIndexRequest;
use App\Admin\Kingdoms\Requests\StoreBuildingRequest;
use App\Admin\Kingdoms\Requests\UpdateBuildingRequest;
use App\Admin\Kingdoms\Services\BuildingAdminService;
use App\Admin\Kingdoms\Transformers\BuildingDetailTransformer;
use App\Admin\Kingdoms\Transformers\BuildingFormOptionsTransformer;
use App\Admin\Kingdoms\Transformers\BuildingFormTransformer;
use App\Admin\Kingdoms\Transformers\BuildingListTransformer;
use App\Flare\Models\GameBuilding;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BuildingsController extends Controller
{
    /**
     * @param BuildingAdminService $buildingAdminService
     * @param Pagination $pagination
     * @param BuildingListTransformer $buildingListTransformer
     * @param BuildingDetailTransformer $buildingDetailTransformer
     * @param BuildingFormTransformer $buildingFormTransformer
     * @param BuildingFormOptionsTransformer $buildingFormOptionsTransformer
     */
    public function __construct(
        private readonly BuildingAdminService $buildingAdminService,
        private readonly Pagination $pagination,
        private readonly BuildingListTransformer $buildingListTransformer,
        private readonly BuildingDetailTransformer $buildingDetailTransformer,
        private readonly BuildingFormTransformer $buildingFormTransformer,
        private readonly BuildingFormOptionsTransformer $buildingFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Kingdom Buildings list.
     *
     * @param BuildingIndexRequest $request
     * @return JsonResponse
     */
    public function index(BuildingIndexRequest $request): JsonResponse
    {
        $paginator = $this->buildingAdminService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->buildingListTransformer)
        );
    }

    /**
     * Return the Admin Kingdom Building form options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        return response()->json($this->buildingFormOptionsTransformer->transform($this->buildingAdminService->formOptions()));
    }

    /**
     * Return the Admin detail representation for the given Kingdom Building.
     *
     * @param GameBuilding $gameBuilding
     * @return JsonResponse
     */
    public function show(GameBuilding $gameBuilding): JsonResponse
    {
        return response()->json($this->buildingDetailTransformer->transform(
            $gameBuilding,
            $this->buildingAdminService->recruitableUnits($gameBuilding),
        ));
    }

    /**
     * Return the current field values for the given Kingdom Building, for populating the edit form.
     *
     * @param GameBuilding $gameBuilding
     * @return JsonResponse
     */
    public function edit(GameBuilding $gameBuilding): JsonResponse
    {
        return response()->json($this->buildingFormTransformer->transform(
            $gameBuilding,
            $this->buildingAdminService->recruitableUnits($gameBuilding),
        ));
    }

    /**
     * Create a new Kingdom Building from the validated request.
     *
     * @param StoreBuildingRequest $request
     * @return JsonResponse
     */
    public function store(StoreBuildingRequest $request): JsonResponse
    {
        $gameBuilding = $this->buildingAdminService->create($request);

        return response()->json($this->buildingFormTransformer->transform(
            $gameBuilding,
            $this->buildingAdminService->recruitableUnits($gameBuilding),
        ), 201);
    }

    /**
     * Update an existing Kingdom Building from the validated request.
     *
     * @param UpdateBuildingRequest $request
     * @param GameBuilding $gameBuilding
     * @return JsonResponse
     */
    public function update(UpdateBuildingRequest $request, GameBuilding $gameBuilding): JsonResponse
    {
        $gameBuilding = $this->buildingAdminService->update($gameBuilding, $request);

        return response()->json($this->buildingFormTransformer->transform(
            $gameBuilding,
            $this->buildingAdminService->recruitableUnits($gameBuilding),
        ));
    }
}
