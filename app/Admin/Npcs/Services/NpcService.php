<?php

namespace App\Admin\Npcs\Services;

use App\Admin\Npcs\Requests\MoveNpcRequest;
use App\Admin\Npcs\Requests\StoreNpcRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Validation\ValidationException;

class NpcService
{
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

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
