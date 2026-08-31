<?php

namespace App\Admin\Monsters\Controllers\Api;

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
    /**
     * @param  MonsterService  $monsterService  Admin Monster mutation service.
     * @param  MonsterReadService  $monsterReadService  Shared factual Monster read service.
     * @param  Pagination  $pagination  Paginator response transformer.
     * @param  MonsterListTransformer  $monsterListTransformer  List-record transformer.
     * @param  MonsterFormTransformer  $monsterFormTransformer  Form-value transformer.
     * @param  MonsterFormOptionsTransformer  $monsterFormOptionsTransformer  Form-options transformer.
     */
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
     *
     * @param  MonsterIndexRequest  $request  Validated Monster list request.
     * @return JsonResponse Paginated Monster list JSON response.
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
     *
     * @return JsonResponse Monster form-options JSON response.
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
     *
     * @param  Monster  $monster  Monster to transform.
     * @return JsonResponse Monster detail JSON response.
     */
    public function show(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterReadService->detail($monster), 200);
    }

    /**
     * Return the current field values for the given Monster, for populating the edit form.
     *
     * @param  Monster  $monster  Monster to populate.
     * @return JsonResponse Monster form-value JSON response.
     */
    public function edit(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterFormTransformer->transform($monster), 200);
    }

    /**
     * Create a new Monster from the validated request.
     *
     * @param  StoreMonsterRequest  $request  Validated Monster creation request.
     * @return JsonResponse Created Monster JSON response.
     */
    public function store(StoreMonsterRequest $request): JsonResponse
    {
        $monster = $this->monsterService->create($request);

        return response()->json($this->monsterFormTransformer->transform($monster), 201);
    }

    /**
     * Update an existing Monster from the validated request.
     *
     * @param  UpdateMonsterRequest  $request  Validated Monster update request.
     * @param  Monster  $monster  Monster to update.
     * @return JsonResponse Updated Monster JSON response.
     */
    public function update(UpdateMonsterRequest $request, Monster $monster): JsonResponse
    {
        $monster = $this->monsterService->update($monster, $request);

        return response()->json($this->monsterFormTransformer->transform($monster), 200);
    }
}
