<?php

namespace App\Admin\PassiveSkills\Controllers\Api;

use App\Admin\PassiveSkills\Requests\PassiveSkillIndexRequest;
use App\Admin\PassiveSkills\Requests\StorePassiveSkillRequest;
use App\Admin\PassiveSkills\Requests\UpdatePassiveSkillRequest;
use App\Admin\PassiveSkills\Services\PassiveSkillAdminService;
use App\Admin\PassiveSkills\Transformers\PassiveSkillDetailTransformer;
use App\Admin\PassiveSkills\Transformers\PassiveSkillFormOptionsTransformer;
use App\Admin\PassiveSkills\Transformers\PassiveSkillFormTransformer;
use App\Admin\PassiveSkills\Transformers\PassiveSkillListTransformer;
use App\Admin\PassiveSkills\Transformers\PassiveSkillTreeTransformer;
use App\Flare\Models\PassiveSkill;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PassiveSkillsController extends Controller
{
    /**
     * @param PassiveSkillAdminService $passiveSkillAdminService
     * @param Pagination $pagination
     * @param PassiveSkillListTransformer $passiveSkillListTransformer
     * @param PassiveSkillDetailTransformer $passiveSkillDetailTransformer
     * @param PassiveSkillFormTransformer $passiveSkillFormTransformer
     * @param PassiveSkillFormOptionsTransformer $passiveSkillFormOptionsTransformer
     * @param PassiveSkillTreeTransformer $passiveSkillTreeTransformer
     */
    public function __construct(
        private readonly PassiveSkillAdminService $passiveSkillAdminService,
        private readonly Pagination $pagination,
        private readonly PassiveSkillListTransformer $passiveSkillListTransformer,
        private readonly PassiveSkillDetailTransformer $passiveSkillDetailTransformer,
        private readonly PassiveSkillFormTransformer $passiveSkillFormTransformer,
        private readonly PassiveSkillFormOptionsTransformer $passiveSkillFormOptionsTransformer,
        private readonly PassiveSkillTreeTransformer $passiveSkillTreeTransformer,
    ) {}

    /**
     * Return every Passive Skill for the Admin tree view.
     *
     * @return JsonResponse
     */
    public function tree(): JsonResponse
    {
        return response()->json(
            $this->passiveSkillAdminService->tree()
                ->map(fn (PassiveSkill $passiveSkill): array => $this->passiveSkillTreeTransformer->transform($passiveSkill))
                ->values()
                ->all()
        );
    }

    /**
     * Return the paginated, searchable, sortable Passive Skills list.
     *
     * @param PassiveSkillIndexRequest $request
     * @return JsonResponse
     */
    public function index(PassiveSkillIndexRequest $request): JsonResponse
    {
        $paginator = $this->passiveSkillAdminService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->passiveSkillListTransformer)
        );
    }

    /**
     * Return the Admin Passive Skill form options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        return response()->json($this->passiveSkillFormOptionsTransformer->transform($this->passiveSkillAdminService->formOptions()));
    }

    /**
     * Return the Admin detail representation for the given Passive Skill.
     *
     * @param PassiveSkill $passiveSkill
     * @return JsonResponse
     */
    public function show(PassiveSkill $passiveSkill): JsonResponse
    {
        return response()->json($this->passiveSkillDetailTransformer->transform(
            $passiveSkill,
            $this->passiveSkillAdminService->childSkills($passiveSkill),
        ));
    }

    /**
     * Return the current field values for the given Passive Skill, for populating the edit form.
     *
     * @param PassiveSkill $passiveSkill
     * @return JsonResponse
     */
    public function edit(PassiveSkill $passiveSkill): JsonResponse
    {
        return response()->json($this->passiveSkillFormTransformer->transform($passiveSkill));
    }

    /**
     * Create a new Passive Skill from the validated request.
     *
     * @param StorePassiveSkillRequest $request
     * @return JsonResponse
     */
    public function store(StorePassiveSkillRequest $request): JsonResponse
    {
        $passiveSkill = $this->passiveSkillAdminService->create($request);

        return response()->json($this->passiveSkillFormTransformer->transform($passiveSkill), 201);
    }

    /**
     * Update an existing Passive Skill from the validated request.
     *
     * @param UpdatePassiveSkillRequest $request
     * @param PassiveSkill $passiveSkill
     * @return JsonResponse
     */
    public function update(UpdatePassiveSkillRequest $request, PassiveSkill $passiveSkill): JsonResponse
    {
        $passiveSkill = $this->passiveSkillAdminService->update($passiveSkill, $request);

        return response()->json($this->passiveSkillFormTransformer->transform($passiveSkill));
    }
}
