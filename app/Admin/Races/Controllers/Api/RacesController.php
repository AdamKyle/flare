<?php

namespace App\Admin\Races\Controllers\Api;

use App\Admin\Races\Requests\RaceIndexRequest;
use App\Admin\Races\Requests\StoreRaceRequest;
use App\Admin\Races\Requests\UpdateRaceRequest;
use App\Admin\Races\Services\RaceService;
use App\Admin\Races\Transformers\RaceDetailTransformer;
use App\Admin\Races\Transformers\RaceFormTransformer;
use App\Admin\Races\Transformers\RaceListTransformer;
use App\Flare\Models\GameRace;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RacesController extends Controller
{
    /**
     * @param  RaceService  $raceService  Admin Race application service.
     * @param  Pagination  $pagination  Paginator response transformer.
     * @param  RaceListTransformer  $raceListTransformer  List-record transformer.
     * @param  RaceDetailTransformer  $raceDetailTransformer  Detail transformer.
     * @param  RaceFormTransformer  $raceFormTransformer  Form-value transformer.
     */
    public function __construct(
        private readonly RaceService $raceService,
        private readonly Pagination $pagination,
        private readonly RaceListTransformer $raceListTransformer,
        private readonly RaceDetailTransformer $raceDetailTransformer,
        private readonly RaceFormTransformer $raceFormTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Races list.
     *
     * @param  RaceIndexRequest  $request  Validated Race list request.
     * @return JsonResponse Paginated Race list JSON response.
     */
    public function index(RaceIndexRequest $request): JsonResponse
    {
        $paginator = $this->raceService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->raceListTransformer)
        );
    }

    /**
     * Return the Admin detail representation for the given Race.
     *
     * @param  GameRace  $gameRace  Race to transform.
     * @return JsonResponse Race detail JSON response.
     */
    public function show(GameRace $gameRace): JsonResponse
    {
        return response()->json($this->raceDetailTransformer->transform($gameRace), 200);
    }

    /**
     * Return the current field values for the given Race, for populating the edit form.
     *
     * @param  GameRace  $gameRace  Race to populate.
     * @return JsonResponse Race form-value JSON response.
     */
    public function edit(GameRace $gameRace): JsonResponse
    {
        return response()->json($this->raceFormTransformer->transform($gameRace), 200);
    }

    /**
     * Create a new Race from the validated request.
     *
     * @param  StoreRaceRequest  $request  Validated Race creation request.
     * @return JsonResponse Created Race JSON response.
     */
    public function store(StoreRaceRequest $request): JsonResponse
    {
        $gameRace = $this->raceService->create($request->validated(), $request->file('image'));

        return response()->json($this->raceFormTransformer->transform($gameRace), 201);
    }

    /**
     * Update an existing Race from the validated request.
     *
     * @param  UpdateRaceRequest  $request  Validated Race update request.
     * @param  GameRace  $gameRace  Race to update.
     * @return JsonResponse Updated Race JSON response.
     */
    public function update(UpdateRaceRequest $request, GameRace $gameRace): JsonResponse
    {
        $gameRace = $this->raceService->update($gameRace, $request->validated(), $request->file('image'));

        return response()->json($this->raceFormTransformer->transform($gameRace), 200);
    }
}
