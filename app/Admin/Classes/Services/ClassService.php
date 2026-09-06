<?php

namespace App\Admin\Classes\Services;

use App\Admin\Classes\Requests\ClassIndexRequest;
use App\Admin\Classes\Requests\StoreClassRequest;
use App\Admin\Classes\Requests\UpdateClassRequest;
use App\Flare\Models\GameClass;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClassService
{
    /**
     * Paginate the Classes list for the validated Admin index request.
     *
     * @param  ClassIndexRequest  $request  Validated Class list request.
     * @return LengthAwarePaginator Paginated Class records.
     */
    public function paginate(ClassIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = GameClass::query();

        if (! empty($searchText)) {
            $query->where(function ($searchQuery) use ($searchText) {
                $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchText.'%');
            });
        }

        $query->orderBy($sortKey, $sortDirection)
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin Class form option data.
     *
     * @return array{classes: Collection<int, GameClass>} Internal Class form option data.
     */
    public function formOptions(): array
    {
        return [
            'classes' => GameClass::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new Class from the validated form data.
     *
     * @param  StoreClassRequest  $request  Validated Class creation request.
     * @return GameClass Created Class.
     */
    public function create(StoreClassRequest $request): GameClass
    {
        return GameClass::create($request->validated());
    }

    /**
     * Update an existing Class from the validated form data.
     *
     * @param  GameClass  $gameClass  Class to update.
     * @param  UpdateClassRequest  $request  Validated Class update request.
     * @return GameClass Updated Class.
     */
    public function update(GameClass $gameClass, UpdateClassRequest $request): GameClass
    {
        $gameClass->update($request->validated());

        return $gameClass->refresh();
    }
}
