<?php

namespace App\Admin\Classes\Controllers\Api;

use App\Admin\Classes\Requests\ClassIndexRequest;
use App\Admin\Classes\Requests\StoreClassRequest;
use App\Admin\Classes\Requests\UpdateClassRequest;
use App\Admin\Classes\Services\ClassService;
use App\Admin\Classes\Transformers\ClassFormOptionsTransformer;
use App\Admin\Classes\Transformers\ClassFormTransformer;
use App\Admin\Classes\Transformers\ClassListTransformer;
use App\Flare\Models\GameClass;
use App\Flare\Pagination\Pagination;
use App\Game\ClassRanks\Transformers\ClassDetailTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassesController extends Controller
{
    public function __construct(
        private readonly ClassService $classService,
        private readonly Pagination $pagination,
        private readonly ClassListTransformer $classListTransformer,
        private readonly ClassDetailTransformer $classDetailTransformer,
        private readonly ClassFormTransformer $classFormTransformer,
        private readonly ClassFormOptionsTransformer $classFormOptionsTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Classes list.
     */
    public function index(ClassIndexRequest $request): JsonResponse
    {
        $paginator = $this->classService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->classListTransformer)
        );
    }

    /**
     * Return the Admin Class form options.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->classService->formOptions();

        return response()->json($this->classFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Class.
     */
    public function show(GameClass $gameClass): JsonResponse
    {
        return response()->json($this->classDetailTransformer->transform($gameClass), 200);
    }

    /**
     * Return the current field values for the given Class, for populating the edit form.
     */
    public function edit(GameClass $gameClass): JsonResponse
    {
        return response()->json($this->classFormTransformer->transform($gameClass), 200);
    }

    /**
     * Create a new Class from the validated request.
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $gameClass = $this->classService->create($request);

        return response()->json($this->classFormTransformer->transform($gameClass), 201);
    }

    /**
     * Update an existing Class from the validated request.
     */
    public function update(UpdateClassRequest $request, GameClass $gameClass): JsonResponse
    {
        $gameClass = $this->classService->update($gameClass, $request);

        return response()->json($this->classFormTransformer->transform($gameClass), 200);
    }
}
