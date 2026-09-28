<?php

namespace App\Admin\GemAbilities\Services;

use App\Admin\GemAbilities\Requests\GemAbilityIndexRequest;
use App\Admin\GemAbilities\Requests\StoreGemAbilityRequest;
use App\Flare\Models\GameGemAbility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GemAbilityService
{
    /**
     * Paginate the Gem Abilities list for the validated Admin index request.
     *
     * @param GemAbilityIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(GemAbilityIndexRequest $request): LengthAwarePaginator
    {
        $filters = $request->validated('filters') ?? [];

        $query = GameGemAbility::query()
            ->when(! empty($request->validated('search_text')), fn (Builder $searchQuery): Builder => $this->applySearch($searchQuery, $request->validated('search_text')))
            ->when(isset($filters['ability_type']), fn (Builder $filterQuery): Builder => $filterQuery->where('ability_type', $filters['ability_type']))
            ->when(isset($filters['enabled']), fn (Builder $filterQuery): Builder => $filterQuery->where('enabled', $filters['enabled']))
            ->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Create a new Gem Ability definition from the validated form data.
     *
     * @param StoreGemAbilityRequest $request
     * @return GameGemAbility
     */
    public function create(StoreGemAbilityRequest $request): GameGemAbility
    {
        return GameGemAbility::create($this->definitionAttributes($request));
    }

    /**
     * Update an existing Gem Ability definition from the validated form data.
     *
     * @param GameGemAbility $gameGemAbility
     * @param StoreGemAbilityRequest $request
     * @return GameGemAbility
     */
    public function update(GameGemAbility $gameGemAbility, StoreGemAbilityRequest $request): GameGemAbility
    {
        $gameGemAbility->update($this->definitionAttributes($request));

        return $gameGemAbility->refresh();
    }

    /**
     * Limit the query to Gem Abilities whose name or description matches the search text.
     *
     * @param Builder $query
     * @param string $searchText
     * @return Builder
     */
    private function applySearch(Builder $query, string $searchText): Builder
    {
        return $query->where(function (Builder $searchQuery) use ($searchText): void {
            $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                ->orWhere('description', 'LIKE', '%'.$searchText.'%');
        });
    }

    /**
     * Build the persisted definition attributes, clearing active-only fields a passive ability does not use.
     *
     * @param StoreGemAbilityRequest $request
     * @return array
     */
    private function definitionAttributes(StoreGemAbilityRequest $request): array
    {
        return [
            'proc_chance' => null,
            'scaling_source' => null,
            ...$request->validated(),
        ];
    }
}
