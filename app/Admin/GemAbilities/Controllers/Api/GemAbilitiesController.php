<?php

namespace App\Admin\GemAbilities\Controllers\Api;

use App\Admin\GemAbilities\Requests\GemAbilityIndexRequest;
use App\Admin\GemAbilities\Requests\StoreGemAbilityRequest;
use App\Admin\GemAbilities\Requests\UpdateGemAbilityRequest;
use App\Admin\GemAbilities\Services\GemAbilityService;
use App\Admin\GemAbilities\Transformers\GemAbilityDetailTransformer;
use App\Admin\GemAbilities\Transformers\GemAbilityFormOptionsTransformer;
use App\Admin\GemAbilities\Transformers\GemAbilityFormTransformer;
use App\Admin\GemAbilities\Transformers\GemAbilityListTransformer;
use App\Flare\Models\GameGemAbility;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemAbilitiesController extends Controller
{
    /**
     * @param GemAbilityService $gemAbilityService
     * @param Pagination $pagination
     * @param GemAbilityListTransformer $gemAbilityListTransformer
     * @param GemAbilityDetailTransformer $gemAbilityDetailTransformer
     * @param GemAbilityFormTransformer $gemAbilityFormTransformer
     * @param GemAbilityFormOptionsTransformer $gemAbilityFormOptionsTransformer
     */
    public function __construct(
        private readonly GemAbilityService $gemAbilityService,
        private readonly Pagination $pagination,
        private readonly GemAbilityListTransformer $gemAbilityListTransformer,
        private readonly GemAbilityDetailTransformer $gemAbilityDetailTransformer,
        private readonly GemAbilityFormTransformer $gemAbilityFormTransformer,
        private readonly GemAbilityFormOptionsTransformer $gemAbilityFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable, filterable Gem Abilities list.
     *
     * @param GemAbilityIndexRequest $request
     * @return JsonResponse
     */
    public function index(GemAbilityIndexRequest $request): JsonResponse
    {
        $paginator = $this->gemAbilityService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gemAbilityListTransformer)
        );
    }

    /**
     * Return the Admin Gem Ability form options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        return response()->json($this->gemAbilityFormOptionsTransformer->transform(), 200);
    }

    /**
     * Return the Admin detail representation for the given Gem Ability.
     *
     * @param GameGemAbility $gameGemAbility
     * @return JsonResponse
     */
    public function show(GameGemAbility $gameGemAbility): JsonResponse
    {
        return response()->json($this->gemAbilityDetailTransformer->transform($gameGemAbility), 200);
    }

    /**
     * Return the current field values for the given Gem Ability, for populating the edit form.
     *
     * @param GameGemAbility $gameGemAbility
     * @return JsonResponse
     */
    public function edit(GameGemAbility $gameGemAbility): JsonResponse
    {
        return response()->json($this->gemAbilityFormTransformer->transform($gameGemAbility), 200);
    }

    /**
     * Create a new Gem Ability from the validated request.
     *
     * @param StoreGemAbilityRequest $request
     * @return JsonResponse
     */
    public function store(StoreGemAbilityRequest $request): JsonResponse
    {
        $gameGemAbility = $this->gemAbilityService->create($request);

        return response()->json($this->gemAbilityFormTransformer->transform($gameGemAbility), 201);
    }

    /**
     * Update an existing Gem Ability from the validated request.
     *
     * @param UpdateGemAbilityRequest $request
     * @param GameGemAbility $gameGemAbility
     * @return JsonResponse
     */
    public function update(UpdateGemAbilityRequest $request, GameGemAbility $gameGemAbility): JsonResponse
    {
        $gameGemAbility = $this->gemAbilityService->update($gameGemAbility, $request);

        return response()->json($this->gemAbilityFormTransformer->transform($gameGemAbility), 200);
    }
}
