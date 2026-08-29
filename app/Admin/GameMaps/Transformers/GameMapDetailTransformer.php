<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use Illuminate\Support\Facades\Storage;

class GameMapDetailTransformer
{
    /**
     * Transform the supplied internal Game Map detail data into its Admin detail representation.
     *
     * @param  array{game_map: GameMap, required_item: Item|null, required_quest: Quest|null, required_location: Location|null}  $detailData  Internal Game Map detail data.
     * @return array{id: int, name: string, map_url: string, tiles: array<int, array<int, string>>, description: string|null, kingdom_color: string, default: bool, can_traverse: bool, event_restriction: int|null, xp_bonus: float|null, skill_training_bonus: float|null, drop_chance_bonus: float|null, enemy_stat_bonus: float|null, character_attack_reduction: float|null, required_location: array{id: int, name: string}|null, required_quest_item: array{id: int, name: string, quest: array{id: int, name: string}|null}|null} Admin Game Map detail representation.
     */
    public function transform(array $detailData): array
    {
        /** @var GameMap $gameMap */
        $gameMap = $detailData['game_map'];

        /** @var Item|null $requiredItem */
        $requiredItem = $detailData['required_item'];

        /** @var Quest|null $requiredQuest */
        $requiredQuest = $detailData['required_quest'];

        /** @var Location|null $requiredLocation */
        $requiredLocation = $detailData['required_location'];

        return [
            'id' => $gameMap->id,
            'name' => $gameMap->name,
            'map_url' => Storage::disk('maps')->url($gameMap->path),
            'tiles' => $gameMap->tile_map ?? [],
            'description' => $gameMap->description,
            'kingdom_color' => $gameMap->kingdom_color,
            'default' => $gameMap->default,
            'can_traverse' => $gameMap->can_traverse,
            'event_restriction' => $gameMap->only_during_event_type,
            'xp_bonus' => $gameMap->xp_bonus,
            'skill_training_bonus' => $gameMap->skill_training_bonus,
            'drop_chance_bonus' => $gameMap->drop_chance_bonus,
            'enemy_stat_bonus' => $gameMap->enemy_stat_bonus,
            'character_attack_reduction' => $gameMap->character_attack_reduction,
            'required_location' => $this->transformRequiredLocation($requiredLocation),
            'required_quest_item' => $this->transformRequiredQuestItem($requiredItem, $requiredQuest),
        ];
    }

    /**
     * Transform the required Location into display data.
     *
     * @param  Location|null  $requiredLocation  Game Map's required access Location, when one is set.
     * @return array{id: int, name: string}|null Compact Location identity.
     */
    private function transformRequiredLocation(?Location $requiredLocation): ?array
    {
        if (is_null($requiredLocation)) {
            return null;
        }

        return [
            'id' => $requiredLocation->id,
            'name' => $requiredLocation->name,
        ];
    }

    /**
     * Transform the Game Map's required quest Item and its acquisition Quest, when present.
     *
     * @param  Item|null  $requiredItem  Required Game Map access Item.
     * @param  Quest|null  $requiredQuest  Quest that rewards the required Item.
     * @return array{id: int, name: string, quest: array{id: int, name: string}|null}|null Required Item representation.
     */
    private function transformRequiredQuestItem(?Item $requiredItem, ?Quest $requiredQuest): ?array
    {
        if (is_null($requiredItem)) {
            return null;
        }

        return [
            'id' => $requiredItem->id,
            'name' => $requiredItem->name,
            'quest' => $this->transformRequiredQuest($requiredQuest),
        ];
    }

    /**
     * Transform the Quest that grants the required quest Item, when one exists.
     *
     * @param  Quest|null  $requiredQuest  Quest that rewards the required Item.
     * @return array{id: int, name: string}|null Required Quest representation.
     */
    private function transformRequiredQuest(?Quest $requiredQuest): ?array
    {
        if (is_null($requiredQuest)) {
            return null;
        }

        return [
            'id' => $requiredQuest->id,
            'name' => $requiredQuest->name,
        ];
    }
}
