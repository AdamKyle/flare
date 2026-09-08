<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use Illuminate\Support\Facades\Storage;

class GameMapDetailTransformer
{
    /**
     * Transform the supplied internal Game Map detail data into its Player factual detail representation.
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
