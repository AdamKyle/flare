<?php

namespace App\Admin\GameMaps\Transformers;

use App\Admin\GameMaps\Values\AdminGameMapType;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use League\Fractal\TransformerAbstract;

class GameMapListTransformer extends TransformerAbstract
{
    /**
     * Transform a Game Map into its Admin list-row representation.
     *
     * @param GameMap $gameMap
     * @return array
     */
    public function transform(GameMap $gameMap): array
    {
        $type = AdminGameMapType::fromGameMap($gameMap);

        return [
            'id' => $gameMap->id,
            'name' => $gameMap->name,
            'map_type' => $type->value,
            'map_type_label' => $type->label(),
            'plane' => $gameMap->effectiveGameMap()->name,
            'source' => $this->resolveSource($gameMap, $type),
        ];
    }

    /**
     * Resolve the factual source context for a generated Gem World row.
     *
     * @param GameMap $gameMap
     * @param AdminGameMapType $type
     * @return string|null
     */
    private function resolveSource(GameMap $gameMap, AdminGameMapType $type): ?string
    {
        return match ($type) {
            AdminGameMapType::BASE => null,
            AdminGameMapType::MAP_GEM_WORLD => $this->mapGemSource($gameMap->generatedMapGemParamter),
            AdminGameMapType::LOCATION_GEM_WORLD => $this->locationGemSource($gameMap->generatedLocationGemParamter),
        };
    }

    /**
     * Resolve the source label for a generated World Gem Game Map.
     *
     * @param GameMapGemParamter|null $gemParamter
     * @return string|null
     */
    private function mapGemSource(?GameMapGemParamter $gemParamter): ?string
    {
        if (is_null($gemParamter) || is_null($gemParamter->gameMap)) {
            return null;
        }

        return 'Map: '.$gemParamter->gameMap->name.' — '.$gemParamter->name;
    }

    /**
     * Resolve the source label for a generated Location Gem Game Map.
     *
     * @param GameLocationGemParamter|null $gemParamter
     * @return string|null
     */
    private function locationGemSource(?GameLocationGemParamter $gemParamter): ?string
    {
        if (is_null($gemParamter) || is_null($gemParamter->location)) {
            return null;
        }

        return 'Location: '.$gemParamter->location->name.' — '.$gemParamter->name;
    }
}
