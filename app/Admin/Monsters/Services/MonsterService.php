<?php

namespace App\Admin\Monsters\Services;

use App\Admin\Monsters\Requests\MonsterIndexRequest;
use App\Admin\Monsters\Requests\StoreMonsterRequest;
use App\Admin\Monsters\Requests\UpdateMonsterRequest;
use App\Admin\Monsters\Values\MonsterListCategory;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Game\Core\Items\Values\ItemCatalogType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonsterService
{
    /**
     * Paginate the Monster list for the validated Admin index request.
     *
     * @param  MonsterIndexRequest  $request  Validated Monster list request.
     * @return LengthAwarePaginator Paginated Monster records.
     */
    public function paginate(MonsterIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $filters = $request->validated('filters') ?? [];

        $query = Monster::query()->with('gameMap');

        if (! empty($searchText)) {
            $query->where('name', 'LIKE', '%'.$searchText.'%');
        }

        if (! empty($filters['game_map_id'] ?? null)) {
            $query->where('game_map_id', $filters['game_map_id']);
        }

        $this->applyCategoryFilter($query, $filters['category'] ?? null, $filters['location_type'] ?? null);

        $query->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Apply the Admin Monster list category filter, and its optional Location
     * Type refinement, to the given query.
     *
     * @param  Builder<Monster>  $query  Monster query to constrain.
     * @param  string|null  $category  Validated MonsterListCategory value, if any.
     * @param  int|null  $locationType  Validated LocationType value, if any.
     */
    private function applyCategoryFilter(Builder $query, ?string $category, ?int $locationType): void
    {
        if (is_null($category) || $category === MonsterListCategory::ALL->value) {
            return;
        }

        match (MonsterListCategory::from($category)) {
            MonsterListCategory::REGULAR => $query->where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->whereNull('only_for_location_type'),
            MonsterListCategory::RAID_MONSTER => $query->where('is_celestial_entity', false)
                ->where('is_raid_monster', true)
                ->where('is_raid_boss', false)
                ->whereNull('only_for_location_type'),
            MonsterListCategory::RAID_BOSS => $query->where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', true)
                ->whereNull('only_for_location_type'),
            MonsterListCategory::CELESTIAL => $query->where('is_celestial_entity', true)
                ->whereNull('only_for_location_type'),
            MonsterListCategory::SPECIAL_LOCATION => $query->where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->whereNotNull('only_for_location_type')
                ->when(
                    ! is_null($locationType),
                    fn (Builder $subQuery) => $subQuery->where('only_for_location_type', $locationType)
                ),
            MonsterListCategory::WEEKLY_FIGHT => $query->where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->when(
                    ! is_null($locationType),
                    fn (Builder $subQuery) => $subQuery->where('only_for_location_type', $locationType),
                    fn (Builder $subQuery) => $subQuery->whereIn('only_for_location_type', MonsterListCategory::weeklyFightLocationTypes())
                ),
        };
    }

    /**
     * Build the internal Admin Monster form option data.
     *
     * @return array{game_maps: Collection<int, GameMap>, quest_items: Collection<int, Item>} Internal Monster form option data.
     */
    public function formOptions(): array
    {
        return [
            'game_maps' => GameMap::orderBy('name')->orderBy('id')->get(),
            'quest_items' => Item::where('type', ItemCatalogType::QUEST->value)->orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new Monster from the validated form data.
     *
     * @param  StoreMonsterRequest  $request  Validated Monster creation request.
     * @return Monster Created Monster.
     */
    public function create(StoreMonsterRequest $request): Monster
    {
        return Monster::create($this->normalize($request->validated()));
    }

    /**
     * Update an existing Monster from the validated form data.
     *
     * @param  Monster  $monster  Monster to update.
     * @param  UpdateMonsterRequest  $request  Validated Monster update request.
     * @return Monster Updated Monster.
     */
    public function update(Monster $monster, UpdateMonsterRequest $request): Monster
    {
        $monster->update($this->normalize($request->validated()));

        return $monster->refresh();
    }

    /**
     * Apply the current 2.0 cross-field normalization rules to validated Monster data.
     *
     * Shared by the Store/Update form path and the Monster import Sheet so both mutation
     * paths apply the exact same cross-field business rules.
     *
     * @param  array<string, mixed>  $data  Validated Monster form data.
     * @return array<string, mixed> Normalized Monster attributes.
     */
    public function normalize(array $data): array
    {
        if (is_null($data['quest_item_id'] ?? null)) {
            $data['quest_item_drop_chance'] = 0;
        }

        return $data;
    }
}
