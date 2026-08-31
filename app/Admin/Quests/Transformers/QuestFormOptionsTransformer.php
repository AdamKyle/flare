<?php

namespace App\Admin\Quests\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Core\Values\FeatureType;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Collection;

class QuestFormOptionsTransformer
{
    /**
     * Transform the supplied internal Quest form option data into its Admin API representation.
     *
     * @param  array{npcs: Collection<int, Npc>, quest_items: Collection<int, Item>, quests: Collection<int, Quest>, game_maps: Collection<int, GameMap>, raids: Collection<int, Raid>, passive_skills: Collection<int, PassiveSkill>}  $formOptions  Internal Quest form option data.
     * @return array<string, mixed> Admin Quest form-options representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'npcs' => $formOptions['npcs']->map(fn (Npc $npc): array => [
                'value' => $npc->id,
                'label' => $npc->real_name,
            ])->values()->all(),
            'quest_items' => $formOptions['quest_items']->map(fn (Item $item): array => [
                'value' => $item->id,
                'label' => $item->name,
            ])->values()->all(),
            'quests' => $formOptions['quests']->map(fn (Quest $quest): array => [
                'value' => $quest->id,
                'label' => $quest->name,
            ])->values()->all(),
            'game_maps' => $formOptions['game_maps']->map(fn (GameMap $gameMap): array => [
                'value' => $gameMap->id,
                'label' => $gameMap->name,
            ])->values()->all(),
            'raids' => $formOptions['raids']->map(fn (Raid $raid): array => [
                'value' => $raid->id,
                'label' => $raid->name,
            ])->values()->all(),
            'passive_skills' => $formOptions['passive_skills']->map(fn (PassiveSkill $passiveSkill): array => [
                'value' => $passiveSkill->id,
                'label' => $passiveSkill->name,
            ])->values()->all(),
            'skill_types' => array_map(fn (SkillTypeValue $type): int => $type->value, SkillTypeValue::cases()),
            'feature_types' => array_map(fn (FeatureType $type): int => $type->value, FeatureType::cases()),
            'event_types' => array_keys(EventType::getOptionsForSelect()),
        ];
    }
}
