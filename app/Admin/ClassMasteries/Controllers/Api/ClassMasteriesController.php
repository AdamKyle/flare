<?php

namespace App\Admin\ClassMasteries\Controllers\Api;

use App\Admin\ClassMasteries\Requests\ClassMasteryIndexRequest;
use App\Admin\ClassMasteries\Requests\StoreClassMasteryRequest;
use App\Admin\ClassMasteries\Requests\UpdateClassMasteryRequest;
use App\Admin\ClassMasteries\Services\ClassMasteryService;
use App\Admin\ClassMasteries\Transformers\ClassMasteryFormOptionsTransformer;
use App\Admin\ClassMasteries\Transformers\ClassMasteryFormTransformer;
use App\Admin\ClassMasteries\Transformers\ClassMasteryListTransformer;
use App\Flare\Models\GameClassSpecial;
use App\Flare\Pagination\Pagination;
use App\Game\ClassRanks\Transformers\ClassMasteryDetailTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassMasteriesController extends Controller
{
    public function __construct(
        private readonly ClassMasteryService $classMasteryService,
        private readonly Pagination $pagination,
        private readonly ClassMasteryListTransformer $classMasteryListTransformer,
        private readonly ClassMasteryDetailTransformer $classMasteryDetailTransformer,
        private readonly ClassMasteryFormTransformer $classMasteryFormTransformer,
        private readonly ClassMasteryFormOptionsTransformer $classMasteryFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable, Class-filtered Class Masteries list.
     */
    public function index(ClassMasteryIndexRequest $request): JsonResponse
    {
        $paginator = $this->classMasteryService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->classMasteryListTransformer)
        );
    }

    /**
     * Return the Admin Class Mastery form options.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->classMasteryService->formOptions();

        return response()->json($this->classMasteryFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Class Mastery.
     */
    public function show(GameClassSpecial $gameClassSpecial): JsonResponse
    {
        return response()->json($this->classMasteryDetailTransformer->transform($gameClassSpecial), 200);
    }

    /**
     * Return the current field values for the given Class Mastery, for populating the edit form.
     */
    public function edit(GameClassSpecial $gameClassSpecial): JsonResponse
    {
        return response()->json($this->classMasteryFormTransformer->transform($gameClassSpecial), 200);
    }

    /**
     * Create a new Class Mastery from the validated request.
     */
    public function store(StoreClassMasteryRequest $request): JsonResponse
    {
        $gameClassSpecial = $this->classMasteryService->create($request);

        return response()->json($this->classMasteryFormTransformer->transform($gameClassSpecial), 201);
    }

    /**
     * Update an existing Class Mastery from the validated request.
     */
    public function update(UpdateClassMasteryRequest $request, GameClassSpecial $gameClassSpecial): JsonResponse
    {
        $gameClassSpecial = $this->classMasteryService->update($gameClassSpecial, $request);

        return response()->json($this->classMasteryFormTransformer->transform($gameClassSpecial), 200);
    }
}
