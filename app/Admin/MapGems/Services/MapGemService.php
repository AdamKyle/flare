<?php

namespace App\Admin\MapGems\Services;

use App\Admin\MapGems\Requests\MapGemIndexRequest;
use App\Admin\MapGems\Requests\StoreMapGemRequest;
use App\Admin\MapGems\Requests\UpdateMapGemRequest;
use App\Admin\Services\AdminGemRollService;
use App\Admin\Transformers\AdminGemRollTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Gem;
use App\Flare\Models\User;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MapGemService
{
    /**
     * @param  AdminGemRollService  $adminGemRollService  Existing Gem roll service.
     * @param  AdminGemRollTransformer  $adminGemRollTransformer  Shared Admin Gem roll transformer.
     * @param  BuildMonsterCacheService  $buildMonsterCacheService  Monster cache invalidation service.
     */
    public function __construct(
        private readonly AdminGemRollService $adminGemRollService,
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
    ) {}

    /**
     * Paginate the Map Gems list for the validated Admin index request.
     *
     * @param  MapGemIndexRequest  $request  Validated Map Gem list request.
     * @return LengthAwarePaginator Paginated Map Gem records.
     */
    public function paginate(MapGemIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $filters = $request->validated('filters') ?? [];
        $gameMapId = $filters['game_map_id'] ?? null;
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = GameMapGemParamter::with(['gameMap', 'rolledGem']);

        if (! empty($searchText)) {
            $query->where(function ($searchQuery) use ($searchText) {
                $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchText.'%');
            });
        }

        if (! is_null($gameMapId)) {
            $query->where('game_map_id', $gameMapId);
        }

        $query->orderBy($sortKey, $sortDirection)->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin Map Gem form option data.
     *
     * @return array{game_maps: Collection<int, GameMap>, crafting_skills: Collection<int, GameSkill>} Internal Map Gem form option data.
     */
    public function formOptions(): array
    {
        return [
            'game_maps' => GameMap::whereNull('generated_map_type')->orderBy('name')->orderBy('id')->get(),
            'crafting_skills' => GameSkill::where('can_train', false)->orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Create a new Map Gem profile from the validated form data.
     *
     * @param  StoreMapGemRequest  $request  Validated Map Gem creation request.
     * @return GameMapGemParamter Created Map Gem profile.
     */
    public function create(StoreMapGemRequest $request): GameMapGemParamter
    {
        return GameMapGemParamter::create($request->validated());
    }

    /**
     * Update an existing Map Gem profile from the validated form data.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to update.
     * @param  UpdateMapGemRequest  $request  Validated Map Gem update request.
     * @return GameMapGemParamter Updated Map Gem profile.
     */
    public function update(GameMapGemParamter $gameMapGemParamter, UpdateMapGemRequest $request): GameMapGemParamter
    {
        $gameMapGemParamter->update($request->validated());

        return $gameMapGemParamter->refresh();
    }

    /**
     * Roll a new Gem for the given Map Gem profile using the existing Gem roll service.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile to roll.
     * @param  User  $admin  Admin performing the roll.
     * @return Gem Rolled Gem.
     */
    public function roll(GameMapGemParamter $gameMapGemParamter, User $admin): Gem
    {
        $gem = $this->adminGemRollService->rollMapGem($gameMapGemParamter, $admin);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return $gem;
    }

    /**
     * Roll a new Gem for every Map Gem profile, regardless of any previously rolled Gem.
     *
     * @param  User  $admin  Admin performing the bulk roll.
     * @return array{rolled_count: int, rolled: array<int, array<string, mixed>>} Bulk roll result.
     */
    public function rollAll(User $admin): array
    {
        $profiles = GameMapGemParamter::with('gameMap')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $rolled = [];

        foreach ($profiles as $profile) {
            $gem = $this->adminGemRollService->rollMapGem($profile, $admin);

            $rolled[] = $this->bulkRollRow($profile, $gem);
        }

        if (count($rolled) > 0) {
            $this->buildMonsterCacheService->invalidateGemAffectedCaches();
        }

        return [
            'rolled_count' => count($rolled),
            'rolled' => $rolled,
        ];
    }

    /**
     * Make an existing historical Gem roll the profile's currently active roll, without changing
     * the roll count or regenerating the Gem World.
     *
     * @param  GameMapGemParamter  $gameMapGemParamter  Map Gem profile whose active roll is changing.
     * @param  Gem  $gem  Gem roll to activate.
     * @return bool Whether the Gem belongs to this profile and was made/kept active.
     */
    public function activateRoll(GameMapGemParamter $gameMapGemParamter, Gem $gem): bool
    {
        if ($gem->domain !== Gem::DOMAIN_MAP || $gem->game_map_gem_paramters_id !== $gameMapGemParamter->id) {
            return false;
        }

        if ($gameMapGemParamter->rolled_gem_id === $gem->id) {
            return true;
        }

        $gameMapGemParamter->update(['rolled_gem_id' => $gem->id]);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return true;
    }

    /**
     * Build one Bulk Roll result row for a Map Gem profile.
     *
     * @param  GameMapGemParamter  $profile  Map Gem profile the row describes.
     * @param  Gem  $gem  Gem this bulk action rolled for the profile.
     * @return array<string, mixed> Bulk roll result row.
     */
    private function bulkRollRow(GameMapGemParamter $profile, Gem $gem): array
    {
        return [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'source_name' => $profile->gameMap->name,
            'rolled_gem' => $this->adminGemRollTransformer->transform($gem, true),
        ];
    }
}
