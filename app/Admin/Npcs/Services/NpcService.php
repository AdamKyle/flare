<?php

namespace App\Admin\Npcs\Services;

use App\Admin\Npcs\Requests\MoveNpcRequest;
use App\Admin\Npcs\Requests\NpcIndexRequest;
use App\Admin\Npcs\Requests\NpcRelationIndexRequest;
use App\Admin\Npcs\Requests\StoreNpcRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NpcService
{
    /**
     * @param  CoordinatesQuery  $coordinatesQuery  Authoritative admin coordinate grid contract.
     */
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

    /**
     * Paginate the standalone NPCs list for the validated Admin index request.
     *
     * @param  NpcIndexRequest  $request  Validated NPC list request.
     * @return LengthAwarePaginator Paginated NPC records.
     */
    public function paginate(NpcIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');
        $filters = $request->validated('filters') ?? [];

        $query = Npc::query()->with('gameMap');

        if (! empty($searchText)) {
            $query->where('real_name', 'LIKE', '%'.$searchText.'%');
        }

        if (! empty($filters['game_map_id'])) {
            $query->where('game_map_id', $filters['game_map_id']);
        }

        if (array_key_exists('type', $filters) && ! is_null($filters['type'])) {
            $query->where('type', $filters['type']);
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
     * Build the internal Admin detail data for the given NPC.
     *
     * @param  Npc  $npc  NPC to describe.
     * @return array{npc: Npc, quest_count: int, reward_item_count: int} Internal NPC detail data.
     */
    public function detailData(Npc $npc): array
    {
        return [
            'npc' => $npc,
            'quest_count' => $npc->quests()->count(),
            'reward_item_count' => $this->rewardItemIds($npc)->count(),
        ];
    }

    /**
     * Paginate the Quests belonging to the given NPC.
     *
     * @param  Npc  $npc  NPC whose Quests are being listed.
     * @param  NpcRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated Quests for the NPC.
     */
    public function paginateQuests(Npc $npc, NpcRelationIndexRequest $request): LengthAwarePaginator
    {
        return $npc->quests()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Paginate the unique quest-reward Items awarded by the given NPC's Quests.
     *
     * @param  Npc  $npc  NPC whose reward Items are being listed.
     * @param  NpcRelationIndexRequest  $request  Validated relationship list request.
     * @return LengthAwarePaginator Paginated, deduplicated reward Items for the NPC.
     */
    public function paginateRewardItems(Npc $npc, NpcRelationIndexRequest $request): LengthAwarePaginator
    {
        return Item::whereIn('id', $this->rewardItemIds($npc))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Resolve the unique, non-null reward Item identifiers for the given NPC's Quests.
     *
     * @param  Npc  $npc  NPC whose reward Item identifiers are being resolved.
     * @return Collection<int, int> Unique reward Item identifiers.
     */
    private function rewardItemIds(Npc $npc): Collection
    {
        return Quest::where('npc_id', $npc->id)
            ->whereNotNull('reward_item')
            ->pluck('reward_item')
            ->unique()
            ->values();
    }

    /**
     * Build the internal Admin Npc form option data for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the NPC form belongs to.
     * @return array{game_map: GameMap, npc_types: array<int,NpcType>, coordinates: Coordinates} Internal NPC form option data.
     */
    public function formOptions(GameMap $gameMap): array
    {
        return [
            'game_map' => $gameMap,
            'npc_types' => NpcType::cases(),
            'coordinates' => $this->coordinatesQuery->get(),
        ];
    }

    /**
     * Resolve the given Npc, aborting when it does not belong to the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the NPC is expected to belong to.
     * @param  Npc  $npc  NPC to resolve.
     * @return Npc Resolved NPC.
     */
    public function findOnMap(GameMap $gameMap, Npc $npc): Npc
    {
        if ($npc->game_map_id !== $gameMap->id) {
            abort(404);
        }

        return $npc;
    }

    /**
     * Create a new Npc on the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the new NPC belongs to.
     * @param  StoreNpcRequest  $request  Validated NPC creation request.
     * @return Npc Created NPC.
     */
    public function create(GameMap $gameMap, StoreNpcRequest $request): Npc
    {
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x_position'], $validated['y_position']);

        return Npc::create([
            ...$validated,
            'game_map_id' => $gameMap->id,
            'name' => str_replace(' ', '', $validated['real_name']),
        ]);
    }

    /**
     * Update an existing Npc on the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the NPC belongs to.
     * @param  Npc  $npc  NPC to update.
     * @param  StoreNpcRequest  $request  Validated NPC update request.
     * @return Npc Updated NPC.
     */
    public function update(GameMap $gameMap, Npc $npc, StoreNpcRequest $request): Npc
    {
        $npc = $this->findOnMap($gameMap, $npc);
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x_position'], $validated['y_position']);

        $npc->update([
            ...$validated,
            'name' => str_replace(' ', '', $validated['real_name']),
        ]);

        return $npc->refresh();
    }

    /**
     * Move an existing Npc on the given Game Map to a new X/Y coordinate.
     *
     * @param  GameMap  $gameMap  Game Map the NPC belongs to.
     * @param  Npc  $npc  NPC to move.
     * @param  MoveNpcRequest  $request  Validated NPC move request.
     * @return Npc Moved NPC.
     */
    public function move(GameMap $gameMap, Npc $npc, MoveNpcRequest $request): Npc
    {
        $npc = $this->findOnMap($gameMap, $npc);
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x_position'], $validated['y_position']);

        $npc->update([
            'x_position' => $validated['x_position'],
            'y_position' => $validated['y_position'],
        ]);

        return $npc->refresh();
    }

    /**
     * Assert the given X/Y coordinate exists within the game world's coordinate grid.
     *
     * @param  int  $x  X coordinate to validate.
     * @param  int  $y  Y coordinate to validate.
     * @return void Returns normally when both coordinates are valid.
     *
     * @throws ValidationException When the coordinate falls outside the authoritative grid.
     */
    private function assertValidCoordinates(int $x, int $y): void
    {
        $coordinates = $this->coordinatesQuery->get();

        if (! in_array($x, $coordinates->x, true)) {
            throw ValidationException::withMessages([
                'x_position' => 'The selected X coordinate is not part of the map grid.',
            ]);
        }

        if (! in_array($y, $coordinates->y, true)) {
            throw ValidationException::withMessages([
                'y_position' => 'The selected Y coordinate is not part of the map grid.',
            ]);
        }
    }
}
