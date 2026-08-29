<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Npc;
use App\Game\Maps\Values\Coordinates;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class GameMapEditorTransformer
{
    /**
     * @param  GameMapLocationMarkerTransformer  $gameMapLocationMarkerTransformer  Location editor marker transformer.
     * @param  GameMapNpcMarkerTransformer  $gameMapNpcMarkerTransformer  NPC editor marker transformer.
     * @param  GameMapKingdomMarkerTransformer  $gameMapKingdomMarkerTransformer  Kingdom editor marker transformer.
     */
    public function __construct(
        private readonly GameMapLocationMarkerTransformer $gameMapLocationMarkerTransformer,
        private readonly GameMapNpcMarkerTransformer $gameMapNpcMarkerTransformer,
        private readonly GameMapKingdomMarkerTransformer $gameMapKingdomMarkerTransformer,
    ) {}

    /**
     * Transform the supplied internal Game Map editor data into its Admin API representation.
     *
     * @param  array{game_map: GameMap, coordinates: Coordinates, locations: Collection<int, Location>, npcs: Collection<int, Npc>, kingdoms: Collection<int, Kingdom>}  $editorData  Internal Game Map editor data.
     * @return array{game_map: array{id: int, name: string, map_url: string, tiles: array<int, array<int, string>>}, coordinates: array{x: array<int, int>, y: array<int, int>}, locations: array<int, array<string, mixed>>, npcs: array<int, array<string, mixed>>, kingdoms: array<int, array<string, mixed>>} Admin Game Map editor representation.
     */
    public function transform(array $editorData): array
    {
        /** @var GameMap $gameMap */
        $gameMap = $editorData['game_map'];

        /** @var Coordinates $coordinates */
        $coordinates = $editorData['coordinates'];

        /** @var Collection<int, Location> $locations */
        $locations = $editorData['locations'];

        /** @var Collection<int, Npc> $npcs */
        $npcs = $editorData['npcs'];

        /** @var Collection<int, Kingdom> $kingdoms */
        $kingdoms = $editorData['kingdoms'];

        return [
            'game_map' => [
                'id' => $gameMap->id,
                'name' => $gameMap->name,
                'map_url' => Storage::disk('maps')->url($gameMap->path),
                'tiles' => $gameMap->tile_map ?? [],
            ],
            'coordinates' => [
                'x' => $coordinates->x,
                'y' => $coordinates->y,
            ],
            'locations' => $locations->map(
                fn (Location $location): array => $this->gameMapLocationMarkerTransformer->transform($location)
            )->values()->all(),
            'npcs' => $npcs->map(
                fn (Npc $npc): array => $this->gameMapNpcMarkerTransformer->transform($npc)
            )->values()->all(),
            'kingdoms' => $kingdoms->map(
                fn (Kingdom $kingdom): array => $this->gameMapKingdomMarkerTransformer->transform($kingdom)
            )->values()->all(),
        ];
    }
}
