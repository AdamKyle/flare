<?php

namespace App\Admin\ClassMasteries\Services;

use App\Admin\ClassMasteries\Requests\ClassMasteryIndexRequest;
use App\Admin\ClassMasteries\Requests\StoreClassMasteryRequest;
use App\Admin\ClassMasteries\Requests\UpdateClassMasteryRequest;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameClassSpecial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClassMasteryService
{
    /**
     * Paginate the Class Masteries list for the validated Admin index request.
     *
     * @param  ClassMasteryIndexRequest  $request  Validated Class Mastery list request.
     * @return LengthAwarePaginator Paginated Class Mastery records.
     */
    public function paginate(ClassMasteryIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $filters = $request->validated('filters') ?? [];
        $gameClassId = $filters['game_class_id'] ?? null;
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = GameClassSpecial::with('gameClass');

        if (! empty($searchText)) {
            $query->where(function ($searchQuery) use ($searchText) {
                $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchText.'%');
            });
        }

        if (! is_null($gameClassId)) {
            $query->where('game_class_id', $gameClassId);
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
     * Build the internal Admin Class Mastery form option data.
     *
     * @return array{classes: Collection<int, GameClass>} Internal Class Mastery form option data.
     */
    public function formOptions(): array
    {
        return [
            'classes' => GameClass::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new Class Mastery from the validated form data.
     *
     * @param  StoreClassMasteryRequest  $request  Validated Class Mastery creation request.
     * @return GameClassSpecial Created Class Mastery.
     */
    public function create(StoreClassMasteryRequest $request): GameClassSpecial
    {
        return GameClassSpecial::create($request->validated());
    }

    /**
     * Update an existing Class Mastery from the validated form data.
     *
     * @param  GameClassSpecial  $gameClassSpecial  Class Mastery to update.
     * @param  UpdateClassMasteryRequest  $request  Validated Class Mastery update request.
     * @return GameClassSpecial Updated Class Mastery.
     */
    public function update(GameClassSpecial $gameClassSpecial, UpdateClassMasteryRequest $request): GameClassSpecial
    {
        $gameClassSpecial->update($request->validated());

        return $gameClassSpecial->refresh();
    }
}
