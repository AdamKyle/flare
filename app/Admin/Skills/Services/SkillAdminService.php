<?php

namespace App\Admin\Skills\Services;

use App\Admin\Skills\Requests\SkillIndexRequest;
use App\Admin\Skills\Requests\StoreSkillRequest;
use App\Admin\Skills\Requests\UpdateSkillRequest;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SkillAdminService
{
    /**
     * Paginate the Skills list for the validated Admin index request.
     *
     * @param SkillIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(SkillIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return GameSkill::query()
            ->with('gameClass')
            ->when(! empty($searchText), function (Builder $query) use ($searchText): void {
                $query->where(function (Builder $searchQuery) use ($searchText): void {
                    $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                        ->orWhere('description', 'LIKE', '%'.$searchText.'%');
                });
            })
            ->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Build the internal Admin Skill form option data.
     *
     * @return array
     */
    public function formOptions(): array
    {
        return [
            'classes' => GameClass::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new Skill from the validated form data.
     *
     * @param StoreSkillRequest $request
     * @return GameSkill
     */
    public function create(StoreSkillRequest $request): GameSkill
    {
        return GameSkill::create($request->validated())->refresh();
    }

    /**
     * Update an existing Skill from the validated form data.
     *
     * @param GameSkill $gameSkill
     * @param UpdateSkillRequest $request
     * @return GameSkill
     */
    public function update(GameSkill $gameSkill, UpdateSkillRequest $request): GameSkill
    {
        $gameSkill->update($request->validated());

        return $gameSkill->refresh();
    }
}
