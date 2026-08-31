<?php

namespace App\Admin\GameMaps\Services;

use App\Admin\GameMaps\Jobs\GenerateGameMapTilesJob;
use App\Admin\GameMaps\Jobs\ReplaceGameMapTilesJob;
use App\Admin\GameMaps\Requests\GameMapIndexRequest;
use App\Admin\GameMaps\Requests\GameMapRelationIndexRequest;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Quests\Values\QuestKind;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GameMapService
{
    /**
     * @param  CoordinatesQuery  $coordinatesQuery  Map coordinate query contract.
     */
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

    /**
     * Paginate the Game Maps list for the validated Admin index request.
     *
     * @param  GameMapIndexRequest  $request  Validated Game Map list request.
     * @return LengthAwarePaginator Paginated Game Map records.
     */
    public function paginate(GameMapIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $sortDirection = $request->validated('sort_direction');

        $query = GameMap::query();

        if (! empty($searchText)) {
            $query->where('name', 'LIKE', '%'.$searchText.'%');
        }

        $query->orderBy('name', $sortDirection)
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin detail data for the given Game Map, including its required quest Item and Quest.
     *
     * @param  GameMap  $gameMap  Game Map to describe.
     * @return array{game_map: GameMap, required_item: Item|null, required_quest: Quest|null, required_location: Location|null} Internal Game Map detail data.
     */
    public function detailData(GameMap $gameMap): array
    {
        $requiredItem = $gameMap->requiredItem();

        return [
            'game_map' => $gameMap,
            'required_item' => $requiredItem,
            'required_quest' => $this->findQuestForRequiredItem($requiredItem),
            'required_location' => $gameMap->requiredLocation,
        ];
    }

    /**
     * Build the internal editor data for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map to edit.
     * @return array{game_map: GameMap, coordinates: Coordinates, locations: Collection<int,Location>, npcs: Collection<int,Npc>, kingdoms: Collection<int,Kingdom>} Internal Game Map editor data.
     */
    public function editorData(GameMap $gameMap): array
    {
        return [
            'game_map' => $gameMap,
            'coordinates' => $this->coordinatesQuery->get(),
            'locations' => Location::where('game_map_id', $gameMap->id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
            'npcs' => Npc::where('game_map_id', $gameMap->id)
                ->orderBy('real_name')
                ->orderBy('id')
                ->get(),
            'kingdoms' => Kingdom::where('game_map_id', $gameMap->id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * Build the internal Admin Game Map form option data.
     *
     * @return array{event_types: array<int,int>, locations: Collection<int,Location>} Internal Game Map form options.
     */
    public function formOptions(): array
    {
        return [
            'event_types' => array_keys(EventType::getOptionsForSelect()),
            'locations' => Location::orderBy('name')
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * Paginate the Locations belonging to the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map whose Locations are being listed.
     * @param  GameMapRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated Locations on the Game Map.
     */
    public function paginateRelatedLocations(GameMap $gameMap, GameMapRelationIndexRequest $request): LengthAwarePaginator
    {
        $query = Location::where('game_map_id', $gameMap->id);

        $this->applySearch($query, 'name', $request->validated('search_text'));

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Paginate the NPCs belonging to the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map whose NPCs are being listed.
     * @param  GameMapRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated NPCs on the Game Map.
     */
    public function paginateRelatedNpcs(GameMap $gameMap, GameMapRelationIndexRequest $request): LengthAwarePaginator
    {
        $query = Npc::where('game_map_id', $gameMap->id);

        $this->applySearch($query, 'real_name', $request->validated('search_text'));

        return $query->orderBy('real_name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Paginate the Quests whose Quest-giver NPC belongs to the given Game Map.
     *
     * A Quest is related to a Map when its Quest-giver NPC lives there; the Quest's own
     * `access_to_map_id`/`faction_game_map_id` are unrelated availability requirements, not its
     * primary location, and are deliberately not used here.
     *
     * @param  GameMap  $gameMap  Game Map whose Quests are being listed.
     * @param  GameMapRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated Quests given by an NPC on the Game Map.
     */
    public function paginateRelatedQuests(GameMap $gameMap, GameMapRelationIndexRequest $request): LengthAwarePaginator
    {
        $query = Quest::whereHas('npc', fn ($npcQuery) => $npcQuery->where('game_map_id', $gameMap->id))
            ->with('npc');

        $this->applySearch($query, 'name', $request->validated('search_text'));

        $paginator = $query->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );

        $this->attachResolvedKind($paginator->getCollection());

        return $paginator;
    }

    /**
     * Paginate the Monsters actually available on the given Game Map: those persisted directly
     * on the Map, plus special-location Monsters whose `only_for_location_type` matches a
     * Location type that actually exists on the Map.
     *
     * @param  GameMap  $gameMap  Game Map whose Monsters are being listed.
     * @param  GameMapRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated Monsters available on the Game Map.
     */
    public function paginateRelatedMonsters(GameMap $gameMap, GameMapRelationIndexRequest $request): LengthAwarePaginator
    {
        $presentLocationTypes = $this->presentLocationTypes($gameMap);

        $query = Monster::where(function ($monsterQuery) use ($gameMap, $presentLocationTypes) {
            $monsterQuery->where('game_map_id', $gameMap->id);

            if ($presentLocationTypes->isNotEmpty()) {
                $monsterQuery->orWhereIn('only_for_location_type', $presentLocationTypes);
            }
        });

        $this->applySearch($query, 'name', $request->validated('search_text'));

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Paginate the unique quest Items connected to the given Game Map through its required
     * Item, its Locations' drop/required/reward Items, its Quests' primary/secondary/reward
     * Items, and its available Monsters' quest Item drops.
     *
     * @param  GameMap  $gameMap  Game Map whose quest Items are being listed.
     * @param  GameMapRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated, deduplicated quest Items connected to the Game Map.
     */
    public function paginateRelatedQuestItems(GameMap $gameMap, GameMapRelationIndexRequest $request): LengthAwarePaginator
    {
        $query = Item::whereIn('id', $this->relatedQuestItemIds($gameMap))
            ->where('type', 'quest');

        $this->applySearch($query, 'name', $request->validated('search_text'));

        return $query->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Apply a case-insensitive `LIKE` search filter to a query when search text is present.
     *
     * @param  mixed  $query  Query builder to filter.
     * @param  string  $column  Column to search.
     * @param  string|null  $searchText  Raw search text, when supplied.
     * @return void The query is filtered in place.
     */
    private function applySearch($query, string $column, ?string $searchText): void
    {
        if (! empty($searchText)) {
            $query->where($column, 'LIKE', '%'.$searchText.'%');
        }
    }

    /**
     * Resolve the distinct, non-null Location types actually present on the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map to inspect.
     * @return SupportCollection<int, int> Distinct Location types present on the Game Map.
     */
    private function presentLocationTypes(GameMap $gameMap): SupportCollection
    {
        return Location::where('game_map_id', $gameMap->id)
            ->whereNotNull('type')
            ->distinct()
            ->pluck('type');
    }

    /**
     * Attach a `resolved_kind` property to each Quest in the collection, resolved from its
     * current persisted relationships via the canonical `QuestKind::resolve()`.
     *
     * @param  Collection<int, Quest>  $quests  Quests to annotate in place.
     * @return void Each Quest gains a `resolved_kind` property.
     */
    private function attachResolvedKind(Collection $quests): void
    {
        $childParentIds = Quest::whereIn('parent_quest_id', $quests->pluck('id'))
            ->pluck('parent_quest_id')
            ->unique();

        $quests->each(function (Quest $quest) use ($childParentIds) {
            $quest->resolved_kind = QuestKind::resolve($quest, $childParentIds->contains($quest->id));
        });
    }

    /**
     * Resolve every unique, non-null quest Item id connected to the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map to resolve quest Item ids for.
     * @return SupportCollection<int, int> Unique quest Item ids connected to the Game Map.
     */
    private function relatedQuestItemIds(GameMap $gameMap): SupportCollection
    {
        $locationIds = Location::where('game_map_id', $gameMap->id)->pluck('id');

        $ids = collect([optional($gameMap->requiredItem())->id]);

        $ids = $ids->merge(Item::whereIn('drop_location_id', $locationIds)->pluck('id'));

        $ids = $ids->merge(
            Location::whereIn('id', $locationIds)->whereNotNull('required_quest_item_id')->pluck('required_quest_item_id')
        );

        $ids = $ids->merge(
            Location::whereIn('id', $locationIds)->whereNotNull('quest_reward_item_id')->pluck('quest_reward_item_id')
        );

        $mapQuests = Quest::whereHas('npc', fn ($npcQuery) => $npcQuery->where('game_map_id', $gameMap->id))
            ->get(['item_id', 'secondary_required_item', 'reward_item']);

        $ids = $ids->merge($mapQuests->pluck('item_id'));
        $ids = $ids->merge($mapQuests->pluck('secondary_required_item'));
        $ids = $ids->merge($mapQuests->pluck('reward_item'));

        $presentLocationTypes = $this->presentLocationTypes($gameMap);

        $monsterQuestItemIds = Monster::where(function ($monsterQuery) use ($gameMap, $presentLocationTypes) {
            $monsterQuery->where('game_map_id', $gameMap->id);

            if ($presentLocationTypes->isNotEmpty()) {
                $monsterQuery->orWhereIn('only_for_location_type', $presentLocationTypes);
            }
        })->whereNotNull('quest_item_id')->pluck('quest_item_id');

        $ids = $ids->merge($monsterQuestItemIds);

        return $ids->filter()->unique()->values();
    }

    /**
     * Create a new Game Map from the validated form data and the uploaded map image.
     *
     * @param  array<string, mixed>  $validatedData  Validated Game Map form data.
     * @param  UploadedFile  $map  Uploaded Game Map image.
     * @return GameMap Created Game Map.
     */
    public function create(array $validatedData, UploadedFile $map): GameMap
    {
        $path = Storage::disk('maps')->putFile($validatedData['name'], $map);

        $gameMap = GameMap::create([
            ...Arr::except($validatedData, ['map']),
            'path' => $path,
        ]);

        GenerateGameMapTilesJob::dispatch($gameMap->id);

        return $gameMap;
    }

    /**
     * Update an existing Game Map from the validated form data, replacing its map image when one is supplied.
     *
     * @param  GameMap  $gameMap  Game Map to update.
     * @param  array<string, mixed>  $validatedData  Validated Game Map form data.
     * @param  UploadedFile|null  $map  Replacement Game Map image, when supplied.
     * @return GameMap Updated Game Map.
     */
    public function update(GameMap $gameMap, array $validatedData, ?UploadedFile $map): GameMap
    {
        if (is_null($map)) {
            $gameMap->update(Arr::except($validatedData, ['map']));

            return $gameMap;
        }

        $previousName = $gameMap->name;
        $previousPath = $gameMap->path;
        $replacementPath = Storage::disk('maps')->putFile($validatedData['name'], $map);

        $gameMap->update([
            ...Arr::except($validatedData, ['map']),
            'path' => $replacementPath,
            'tile_map' => null,
        ]);

        ReplaceGameMapTilesJob::dispatch($gameMap->id, $previousName, $previousPath);

        return $gameMap;
    }

    /**
     * Process initial Game Map tile generation outside the HTTP lifecycle.
     *
     * @param  GameMap  $gameMap  Game Map requiring initial tiles.
     * @param  MapTileGenerationService  $mapTileGenerationService  Map tile lifecycle service.
     * @return bool Whether tile generation completed successfully.
     */
    public function processInitialTiles(
        GameMap $gameMap,
        MapTileGenerationService $mapTileGenerationService,
    ): bool {
        try {
            $mapTileGenerationService->tile($gameMap);

            return true;
        } catch (Throwable $generationFailure) {
            Log::error('Initial Game Map tile generation failed.', [
                'game_map_id' => $gameMap->id,
                'exception' => $generationFailure,
            ]);
        }

        try {
            $mapTileGenerationService->remove($gameMap);
        } catch (Throwable $cleanupFailure) {
            Log::error('Initial Game Map partial tile cleanup failed.', [
                'game_map_id' => $gameMap->id,
                'exception' => $cleanupFailure,
            ]);
        }

        return false;
    }

    /**
     * Process and promote replacement Game Map tiles outside the HTTP lifecycle.
     *
     * @param  GameMap  $gameMap  Game Map carrying replacement source-image state.
     * @param  string  $previousName  Previous Game Map name.
     * @param  string  $previousPath  Previous source-image path.
     * @param  MapTileGenerationService  $mapTileGenerationService  Map tile lifecycle service.
     * @return bool Whether the replacement became authoritative.
     */
    public function processReplacementTiles(
        GameMap $gameMap,
        string $previousName,
        string $previousPath,
        MapTileGenerationService $mapTileGenerationService,
    ): bool {
        try {
            $preparedReplacement = $mapTileGenerationService->prepareReplacement($gameMap, $previousName);
        } catch (Throwable $preparationFailure) {
            $this->logReplacementFailure($gameMap, 'preparation', $preparationFailure);

            return false;
        }

        $promotionOccurred = false;

        try {
            $mapTileGenerationService->commitReplacement($preparedReplacement);
            $promotionOccurred = true;
            $gameMap->tile_map = $preparedReplacement->tileMap;

            if (! $gameMap->save()) {
                throw new RuntimeException('Failed to persist the replacement Game Map.');
            }
        } catch (Throwable $replacementFailure) {
            if ($promotionOccurred) {
                try {
                    $mapTileGenerationService->rollbackReplacement($gameMap, null, $preparedReplacement);
                } catch (Throwable $rollbackFailure) {
                    $this->logReplacementFailure($gameMap, 'rollback', $rollbackFailure);
                }
            }

            $gameMap->tile_map = null;
            $this->logReplacementFailure($gameMap, 'commit or persistence', $replacementFailure);

            return false;
        }

        try {
            $mapTileGenerationService->finalizeReplacement($preparedReplacement);
        } catch (Throwable $finalizationFailure) {
            $this->logReplacementFailure($gameMap, 'finalization cleanup', $finalizationFailure);
        }

        if ($previousPath !== $gameMap->path) {
            try {
                $this->deleteFile($previousPath);
            } catch (Throwable $sourceCleanupFailure) {
                $this->logReplacementFailure($gameMap, 'previous source-image cleanup', $sourceCleanupFailure);
            }
        }

        return true;
    }

    /**
     * Log a replacement processing failure with Game Map context.
     *
     * @param  GameMap  $gameMap  Game Map being processed.
     * @param  string  $stage  Replacement lifecycle stage.
     * @param  Throwable  $failure  Operational failure.
     * @return void Writes the failure to the application log.
     */
    private function logReplacementFailure(GameMap $gameMap, string $stage, Throwable $failure): void
    {
        Log::error('Game Map replacement '.$stage.' failed.', [
            'game_map_id' => $gameMap->id,
            'exception' => $failure,
        ]);
    }

    /**
     * Delete a required Game Map source image and surface storage failure.
     *
     * @param  string  $path  Source-image path to delete.
     * @return void The source image is deleted.
     */
    private function deleteFile(string $path): void
    {
        if (! Storage::disk('maps')->delete($path)) {
            throw new RuntimeException('Failed to delete Game Map source image: '.$path.'.');
        }
    }

    /**
     * Find the Quest that rewards the required Game Map access Item.
     *
     * @param  Item|null  $requiredItem  Required Game Map access Item.
     * @return Quest|null Quest that rewards the required Item.
     */
    private function findQuestForRequiredItem(?Item $requiredItem): ?Quest
    {
        if (is_null($requiredItem)) {
            return null;
        }

        return Quest::where('reward_item', $requiredItem->id)->first();
    }
}
