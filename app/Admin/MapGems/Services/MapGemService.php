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
use Illuminate\Pagination\LengthAwarePaginator;

class MapGemService
{
    public function __construct(
        private readonly AdminGemRollService $adminGemRollService,
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
    ) {}

    /**
     * Paginate the Map Gems list for the validated Admin index request.
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
     */
    public function create(StoreMapGemRequest $request): GameMapGemParamter
    {
        return GameMapGemParamter::create($request->validated());
    }

    /**
     * Update an existing Map Gem profile from the validated form data.
     */
    public function update(GameMapGemParamter $gameMapGemParamter, UpdateMapGemRequest $request): GameMapGemParamter
    {
        $gameMapGemParamter->update($request->validated());

        return $gameMapGemParamter->refresh();
    }

    /**
     * Roll a new Gem for the given Map Gem profile using the existing Gem roll service.
     */
    public function roll(GameMapGemParamter $gameMapGemParamter, User $admin): Gem
    {
        $gem = $this->adminGemRollService->rollMapGem($gameMapGemParamter, $admin);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return $gem;
    }

    /**
     * Roll a new Gem for every Map Gem profile, regardless of any previously rolled Gem.
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
     * Paginate the Gem roll history for the given Map Gem profile, active roll first.
     */
    public function paginateRolls(GameMapGemParamter $gameMapGemParamter, int $perPage, int $page): LengthAwarePaginator
    {
        return $gameMapGemParamter->gemRolls()
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$gameMapGemParamter->rolled_gem_id])
            ->orderByDesc('roll_number')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Activate an existing Map Gem roll without regenerating its Gem World.
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
