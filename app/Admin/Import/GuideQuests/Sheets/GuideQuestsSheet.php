<?php

namespace App\Admin\Import\GuideQuests\Sheets;

use App\Flare\Models\Faction;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Item;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuideQuestsSheet implements ToCollection
{
    private const ALLOWED_BATCH_CRAFTING_TYPES = [
        'craft',
        'craft_and_enchant',
        'alchemy',
        'trinketry',
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $guideQuest = array_combine($rows[0]->toArray(), $row->toArray());

                $guideQuestData = $this->returnCleanAffix($guideQuest);

                if (is_null($guideQuestData)) {
                    continue;
                } else {
                    $foundGuideQuest = GuideQuest::where('name', $guideQuestData['name'])->first();

                    if (! is_null($foundGuideQuest)) {
                        $foundGuideQuest->update($guideQuestData);
                    } else {
                        GuideQuest::create($guideQuestData);
                    }
                }
            }
        }
    }

    protected function returnCleanAffix(array $data)
    {

        $gameMap = GameMap::where('name', $data['required_game_map_id'])->first();
        $beOnMap = GameMap::where('name', $data['be_on_game_map'])->first();
        $skill = GameSkill::where('name', $data['required_skill'])->first();
        $secondarySkill = GameSkill::where('name', $data['required_secondary_skill'])->first();
        $passiveSkill = PassiveSkill::where('name', $data['required_passive_skill'])->first();
        $faction = Faction::whereHas('gameMap', function ($query) use ($data) {
            $query->where('name', $data['required_faction_id']);
        })->first();
        $requiredItem = Item::where('name', $data['required_quest_item_id'])->where('type', 'quest')->first();
        $secondaryItem = Item::where('name', $data['secondary_quest_item_id'])->where('type', 'quest')->first();
        $quest = Quest::where('name', $data['required_quest_id'])->first();
        $kingdomBuilding = GameBuilding::where('name', $data['required_kingdom_building_id'])->first();
        $parentGuideQuest = GuideQuest::where('name', $data['parent_id'])->first();

        if (
            blank($data['required_batch_crafting_type'] ?? null) ||
            ! in_array($data['required_batch_crafting_type'], self::ALLOWED_BATCH_CRAFTING_TYPES, true) ||
            blank($data['required_batch_crafting_hours'] ?? null) ||
            (int) $data['required_batch_crafting_hours'] < 1
        ) {
            $data['required_batch_crafting_type'] = null;
            $data['required_batch_crafting_hours'] = null;
        }

        $data['required_batch_crafted_items'] = $this->requiredBatchCraftedItems($data);

        if (is_null($skill)) {
            $data['required_skill_level'] = null;
            $data['required_skill'] = null;
        } else {
            $data['required_skill'] = $skill->id;
        }

        if (is_null($secondarySkill)) {
            $data['required_secondary_skill_level'] = null;
            $data['required_secondary_skill'] = null;
        } else {
            $data['required_secondary_skill'] = $secondarySkill->id;
        }

        if (is_null($passiveSkill)) {
            $data['required_passive_level'] = null;
            $data['required_passive_skill'] = null;
        } else {
            $data['required_passive_skill'] = $passiveSkill->id;
        }

        if (is_null($faction)) {
            $data['required_faction_id'] = null;
            $data['required_faction_level'] = null;
        } else {
            $data['required_faction_id'] = $faction->id;
        }

        if (is_null($requiredItem)) {
            $data['required_quest_item_id'] = null;
        } else {
            $data['required_quest_item_id'] = $requiredItem->id;
        }

        if (is_null($secondaryItem)) {
            $data['secondary_quest_item_id'] = null;
        } else {
            $data['secondary_quest_item_id'] = $secondaryItem->id;
        }

        if (is_null($quest)) {
            $data['required_quest_id'] = null;
        } else {
            $data['required_quest_id'] = $quest->id;
        }

        if (is_null($gameMap)) {
            $data['required_game_map_id'] = null;
        } else {
            $data['required_game_map_id'] = $gameMap->id;
        }

        if (is_null($kingdomBuilding)) {
            $data['required_kingdom_building_id'] = null;
            $data['required_kingdom_building_level'] = null;
        } else {
            $data['required_kingdom_building_id'] = $kingdomBuilding->id;
        }

        if (is_null($beOnMap)) {
            $data['be_on_game_map'] = null;
        } else {
            $data['be_on_game_map'] = $beOnMap->id;
        }

        if (is_null($parentGuideQuest)) {
            $data['parent_id'] = null;
        } else {
            $data['parent_id'] = $parentGuideQuest->id;
        }

        $data['required_event_goal_crafting_participation'] = blank($data['required_event_goal_crafting_participation'] ?? null)
            ? null
            : (int) $data['required_event_goal_crafting_participation'];
        $data['required_event_goal_enchanting_participation'] = blank($data['required_event_goal_enchanting_participation'] ?? null)
            ? null
            : (int) $data['required_event_goal_enchanting_participation'];

        return $data;
    }

    private function requiredBatchCraftedItems(array $data): ?array
    {
        $requiredBatchCraftedItems = [];

        for ($row = 1; $row <= 2; $row++) {
            $source = $data['required_item_' . $row . '_source'] ?? null;
            $itemName = $data['required_item_' . $row . '_name'] ?? ($data['required_batch_crafted_item_' . $row . '_name'] ?? null);
            $itemType = $data['required_item_' . $row . '_type'] ?? ($data['required_batch_crafted_item_' . $row . '_type'] ?? null);
            $amount = $data['required_item_' . $row . '_amount'] ?? ($data['required_batch_crafted_item_' . $row . '_amount'] ?? null);

            if (blank($itemName) && blank($itemType) && blank($amount)) {
                continue;
            }

            if (blank($itemName) || blank($itemType) || blank($amount) || (int) $amount < 1) {
                continue;
            }

            $source = blank($source) ? 'inventory' : $source;

            if (! in_array($source, ['inventory', 'alchemy_bag'], true)) {
                continue;
            }

            $item = $source === 'alchemy_bag'
                ? $this->findAlchemyRequirementItem($itemName, $itemType)
                : $this->findInventoryRequirementItem($itemName, $itemType);

            if (is_null($item)) {
                continue;
            }

            $requiredBatchCraftedItems[] = [
                'source' => $source,
                'item_id' => $item->id,
                'amount' => (int) $amount,
                'must_be_enchanted' => $source === 'alchemy_bag'
                    ? false
                    : filter_var($data['required_item_' . $row . '_must_be_enchanted'] ?? ($data['required_batch_crafted_item_' . $row . '_must_be_enchanted'] ?? false), FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if (empty($requiredBatchCraftedItems)) {
            return null;
        }

        return $requiredBatchCraftedItems;
    }

    private function findInventoryRequirementItem(string $itemName, string $itemType): ?Item
    {
        if (! in_array($itemType, $this->validBatchCraftedItemTypes(), true) && ! in_array($itemType, $this->validBatchCraftedItemTypeNames(), true)) {
            return null;
        }

        return Item::where('name', $itemName)
            ->where('can_craft', true)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', '!=', 'alchemy')
            ->where(function ($query) use ($itemType) {
                $query->where('type', $itemType);

                foreach ($this->validBatchCraftedItemTypes() as $validType) {
                    if ($this->batchCraftedItemTypeName($validType) === $itemType) {
                        $query->orWhere('type', $validType);
                    }
                }
            })
            ->first();
    }

    private function findAlchemyRequirementItem(string $itemName, string $itemType): ?Item
    {
        return Item::where('name', $itemName)
            ->where('type', 'alchemy')
            ->where(function ($query) use ($itemType) {
                $query->where('alchemy_type', $itemType);

                foreach ($this->alchemyItemTypeNames() as $alchemyType => $alchemyTypeName) {
                    if ($alchemyTypeName === $itemType) {
                        $query->orWhere('alchemy_type', $alchemyType);
                    }
                }
            })
            ->first();
    }

    private function validBatchCraftedItemTypes(): array
    {
        return array_merge(
            ItemType::validWeapons(),
            ArmourType::allTypes(),
            [
                ItemType::RING->value,
                ItemType::SPELL_DAMAGE->value,
                ItemType::SPELL_HEALING->value,
            ],
        );
    }

    private function validBatchCraftedItemTypeNames(): array
    {
        return array_map(fn (string $type) => $this->batchCraftedItemTypeName($type), $this->validBatchCraftedItemTypes());
    }

    private function batchCraftedItemTypeName(string $type): string
    {
        return match ($type) {
            ItemType::DAGGER->value => 'Daggers',
            ItemType::SPELL_DAMAGE->value => 'Spell Damage',
            ItemType::SPELL_HEALING->value => 'Spell Healing',
            default => ItemType::getProperNameForType($type),
        };
    }

    private function alchemyItemTypeNames(): array
    {
        return [
            AlchemyItemType::INCREASE_STATS->value => 'Increases Stats',
            AlchemyItemType::INCREASE_SKILL_TYPE->value => 'Increases Training Skills',
            AlchemyItemType::INCREASE_DAMAGE->value => 'Increases Damage',
            AlchemyItemType::INCREASE_ARMOUR->value => 'Increases Armour',
            AlchemyItemType::INCREASE_HEALING->value => 'Increases Healing',
            AlchemyItemType::INCREASE_ALCHEMY_SKILL->value => 'Increases Alchemy Skill',
            AlchemyItemType::DAMAGES_KINGDOMS->value => 'Damages Kingdoms',
            AlchemyItemType::HOLY_OILS->value => 'Holy Oils',
        ];
    }
}
