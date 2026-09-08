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
use App\Game\Npcs\Values\NpcType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NpcService
{
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

    /**
     * Paginate the standalone NPCs list for the validated Admin index request.
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
