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
    public function __construct(
        private readonly RaceService $raceService,
        private readonly Pagination $pagination,
        private readonly RaceListTransformer $raceListTransformer,
        private readonly RaceDetailTransformer $raceDetailTransformer,
        private readonly RaceFormTransformer $raceFormTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Races list.
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
     */
    public function show(GameRace $gameRace): JsonResponse
    {
        return response()->json($this->raceDetailTransformer->transform($gameRace), 200);
    }

    /**
     * Return the current field values for the given Race, for populating the edit form.
     */
    public function edit(GameRace $gameRace): JsonResponse
    {
        return response()->json($this->raceFormTransformer->transform($gameRace), 200);
    }

    /**
     * Create a new Race from the validated request.
     */
    public function store(StoreRaceRequest $request): JsonResponse
    {
        $gameRace = $this->raceService->create($request->validated(), $request->file('image'));

        return response()->json($this->raceFormTransformer->transform($gameRace), 201);
    }

    /**
     * Update an existing Race from the validated request.
     */
    public function update(UpdateRaceRequest $request, GameRace $gameRace): JsonResponse
    {
        $gameRace = $this->raceService->update($gameRace, $request->validated(), $request->file('image'));

        return response()->json($this->raceFormTransformer->transform($gameRace), 200);
    }
}
