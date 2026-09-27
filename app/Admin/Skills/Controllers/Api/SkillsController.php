<?php

namespace App\Admin\Skills\Controllers\Api;

use App\Admin\Skills\Requests\SkillIndexRequest;
use App\Admin\Skills\Requests\StoreSkillRequest;
use App\Admin\Skills\Requests\UpdateSkillRequest;
use App\Admin\Skills\Services\SkillAdminService;
use App\Admin\Skills\Transformers\SkillDetailTransformer;
use App\Admin\Skills\Transformers\SkillFormOptionsTransformer;
use App\Admin\Skills\Transformers\SkillFormTransformer;
use App\Admin\Skills\Transformers\SkillListTransformer;
use App\Flare\Models\GameSkill;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SkillsController extends Controller
{
    /**
     * @param SkillAdminService $skillAdminService
     * @param Pagination $pagination
     * @param SkillListTransformer $skillListTransformer
     * @param SkillDetailTransformer $skillDetailTransformer
     * @param SkillFormTransformer $skillFormTransformer
     * @param SkillFormOptionsTransformer $skillFormOptionsTransformer
     */
    public function __construct(
        private readonly SkillAdminService $skillAdminService,
        private readonly Pagination $pagination,
        private readonly SkillListTransformer $skillListTransformer,
        private readonly SkillDetailTransformer $skillDetailTransformer,
        private readonly SkillFormTransformer $skillFormTransformer,
        private readonly SkillFormOptionsTransformer $skillFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Skills list.
     *
     * @param SkillIndexRequest $request
     * @return JsonResponse
     */
    public function index(SkillIndexRequest $request): JsonResponse
    {
        $paginator = $this->skillAdminService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->skillListTransformer)
        );
    }

    /**
     * Return the Admin Skill form options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        return response()->json($this->skillFormOptionsTransformer->transform($this->skillAdminService->formOptions()));
    }

    /**
     * Return the Admin detail representation for the given Skill.
     *
     * @param GameSkill $gameSkill
     * @return JsonResponse
     */
    public function show(GameSkill $gameSkill): JsonResponse
    {
        return response()->json($this->skillDetailTransformer->transform($gameSkill));
    }

    /**
     * Return the current field values for the given Skill, for populating the edit form.
     *
     * @param GameSkill $gameSkill
     * @return JsonResponse
     */
    public function edit(GameSkill $gameSkill): JsonResponse
    {
        return response()->json($this->skillFormTransformer->transform($gameSkill));
    }

    /**
     * Create a new Skill from the validated request.
     *
     * @param StoreSkillRequest $request
     * @return JsonResponse
     */
    public function store(StoreSkillRequest $request): JsonResponse
    {
        $gameSkill = $this->skillAdminService->create($request);

        return response()->json($this->skillFormTransformer->transform($gameSkill), 201);
    }

    /**
     * Update an existing Skill from the validated request.
     *
     * @param UpdateSkillRequest $request
     * @param GameSkill $gameSkill
     * @return JsonResponse
     */
    public function update(UpdateSkillRequest $request, GameSkill $gameSkill): JsonResponse
    {
        $gameSkill = $this->skillAdminService->update($gameSkill, $request);

        return response()->json($this->skillFormTransformer->transform($gameSkill));
    }
}
