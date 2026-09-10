<?php

namespace App\Admin\Monsters\Controllers\Api;

use App\Admin\Monsters\Requests\MonsterGemEffectContextIndexRequest;
use App\Admin\Monsters\Requests\MonsterIndexRequest;
use App\Admin\Monsters\Requests\StoreMonsterRequest;
use App\Admin\Monsters\Requests\UpdateMonsterRequest;
use App\Admin\Monsters\Services\MonsterService;
use App\Admin\Monsters\Transformers\MonsterFormOptionsTransformer;
use App\Admin\Monsters\Transformers\MonsterFormTransformer;
use App\Admin\Monsters\Transformers\MonsterListTransformer;
use App\Flare\Models\Monster;
use App\Flare\Pagination\Pagination;
use App\Game\Monsters\Services\MonsterReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MonstersController extends Controller
{
    public function __construct(
        private readonly MonsterService $monsterService,
        private readonly MonsterReadService $monsterReadService,
        private readonly Pagination $pagination,
        private readonly MonsterListTransformer $monsterListTransformer,
        private readonly MonsterFormTransformer $monsterFormTransformer,
        private readonly MonsterFormOptionsTransformer $monsterFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Monster list.
     */
    public function index(MonsterIndexRequest $request): JsonResponse
    {
        $paginator = $this->monsterService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->monsterListTransformer)
        );
    }

    /**
     * Return the Admin Monster form options.
     */
    public function options(): JsonResponse
    {
        return response()->json(
            $this->monsterFormOptionsTransformer->transform($this->monsterService->formOptions()),
            200
        );
    }

    /**
     * Return the full factual detail representation for the given Monster.
     */
    public function show(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterReadService->detail($monster), 200);
    }

    /**
     * Return the append-paginated Gem effect context list for the given Monster.
     */
    public function gemEffectContexts(MonsterGemEffectContextIndexRequest $request, Monster $monster): JsonResponse
    {
        return response()->json(
            $this->monsterReadService->gemEffectContexts(
                $monster,
                $request->integer('per_page'),
                $request->integer('page'),
            ),
            200
        );
    }

    /**
     * Return the current field values for the given Monster, for populating the edit form.
     */
    public function edit(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterFormTransformer->transform($monster), 200);
    }

    /**
     * Create a new Monster from the validated request.
     */
    public function store(StoreMonsterRequest $request): JsonResponse
    {
        $monster = $this->monsterService->create($request);

        return response()->json($this->monsterFormTransformer->transform($monster), 201);
    }

    /**
     * Update an existing Monster from the validated request.
     */
    public function update(UpdateMonsterRequest $request, Monster $monster): JsonResponse
    {
        $monster = $this->monsterService->update($monster, $request);

        return response()->json($this->monsterFormTransformer->transform($monster), 200);
    }
}
