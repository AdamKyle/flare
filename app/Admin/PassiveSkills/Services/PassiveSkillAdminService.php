<?php

namespace App\Admin\PassiveSkills\Services;

use App\Admin\PassiveSkills\Requests\PassiveSkillIndexRequest;
use App\Admin\PassiveSkills\Requests\StorePassiveSkillRequest;
use App\Admin\PassiveSkills\Requests\UpdatePassiveSkillRequest;
use App\Flare\Models\PassiveSkill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PassiveSkillAdminService
{
    /**
     * Paginate the Passive Skills list for the validated Admin index request.
     *
     * @param PassiveSkillIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(PassiveSkillIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return PassiveSkill::query()
            ->with('parent')
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
     * Build the internal Admin Passive Skill form option data.
     *
     * @return array
     */
    public function formOptions(): array
    {
        return [
            'passive_skills' => PassiveSkill::orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Return every Passive Skill in deterministic tree order.
     *
     * @return Collection
     */
    public function tree(): Collection
    {
        return PassiveSkill::query()->orderBy('id')->get();
    }

    /**
     * Return the Passive Skills that directly belong to the given Passive Skill, in unlock order.
     *
     * @param PassiveSkill $passiveSkill
     * @return Collection
     */
    public function childSkills(PassiveSkill $passiveSkill): Collection
    {
        return PassiveSkill::where('parent_skill_id', $passiveSkill->id)
            ->orderBy('unlocks_at_level')
            ->orderBy('name')
            ->get(['id', 'name', 'effect_type', 'max_level', 'unlocks_at_level']);
    }

    /**
     * Create a new Passive Skill from the validated form data.
     *
     * @param StorePassiveSkillRequest $request
     * @return PassiveSkill
     */
    public function create(StorePassiveSkillRequest $request): PassiveSkill
    {
        return PassiveSkill::create($request->validated())->refresh();
    }

    /**
     * Update an existing Passive Skill from the validated form data.
     *
     * @param PassiveSkill $passiveSkill
     * @param UpdatePassiveSkillRequest $request
     * @return PassiveSkill
     */
    public function update(PassiveSkill $passiveSkill, UpdatePassiveSkillRequest $request): PassiveSkill
    {
        $passiveSkill->update($request->validated());

        return $passiveSkill->refresh();
    }
}
