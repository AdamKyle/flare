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
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\GuideQuests\Values\GuideQuestRequiredItemSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuideQuestsSheet implements ToCollection
{
    /**
     * Import guide quest rows from the uploaded spreadsheet and persist them.
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headings = $rows->first()->toArray();

        foreach ($rows->skip(1) as $row) {
            $guideQuestData = $this->cleanGuideQuestRow(array_combine($headings, $row->toArray()));

            GuideQuest::updateOrCreate(['name' => $guideQuestData['name']], $guideQuestData);
        }
    }

    /**
     * Build a clean guide quest payload from a raw spreadsheet row, resolving related requirements.
     *
     * @param array $data
     * @return array
     */
    private function cleanGuideQuestRow(array $data): array
    {
        $data['intro_text'] = $this->decodeStructuredContent($data['intro_text'] ?? null, 'intro_text');
        $data['desktop_instructions'] = $this->decodeStructuredContent($data['desktop_instructions'] ?? null, 'desktop_instructions');
        $data['mobile_instructions'] = $this->decodeStructuredContent($data['mobile_instructions'] ?? null, 'mobile_instructions');

        $data = $this->normalizeBatchCraftingRequirement($data);
        $data['required_batch_crafted_items'] = $this->requiredBatchCraftedItems($data);

        $data = $this->applyResolvedRecord($data, 'required_skill', GameSkill::where('name', $data['required_skill'])->first(), ['required_skill_level']);
        $data = $this->applyResolvedRecord($data, 'required_secondary_skill', GameSkill::where('name', $data['required_secondary_skill'])->first(), ['required_secondary_skill_level']);
        $data = $this->applyResolvedRecord($data, 'required_passive_skill', PassiveSkill::where('name', $data['required_passive_skill'])->first(), ['required_passive_level']);
        $data = $this->applyResolvedRecord($data, 'required_faction_id', $this->findFactionByMapName($data['required_faction_id']), ['required_faction_level']);
        $data = $this->applyResolvedRecord($data, 'required_quest_item_id', Item::where('name', $data['required_quest_item_id'])->where('type', 'quest')->first());
        $data = $this->applyResolvedRecord($data, 'secondary_quest_item_id', Item::where('name', $data['secondary_quest_item_id'])->where('type', 'quest')->first());
        $data = $this->applyResolvedRecord($data, 'required_quest_id', Quest::where('name', $data['required_quest_id'])->first());
        $data = $this->applyResolvedRecord($data, 'required_game_map_id', GameMap::where('name', $data['required_game_map_id'])->first());
        $data = $this->applyResolvedRecord($data, 'required_kingdom_building_id', GameBuilding::where('name', $data['required_kingdom_building_id'])->first(), ['required_kingdom_building_level']);
        $data = $this->applyResolvedRecord($data, 'be_on_game_map', GameMap::where('name', $data['be_on_game_map'])->first());
        $data = $this->applyResolvedRecord($data, 'parent_id', GuideQuest::where('name', $data['parent_id'])->first());

        $data['required_event_goal_crafting_participation'] = $this->nullableInteger($data['required_event_goal_crafting_participation'] ?? null);
        $data['required_event_goal_enchanting_participation'] = $this->nullableInteger($data['required_event_goal_enchanting_participation'] ?? null);

        return $data;
    }

    /**
     * Replace a named relationship cell with the resolved record id, clearing it and its companion cells when nothing matches.
     *
     * @param array $data
     * @param string $key
     * @param Model|null $record
     * @param array $companionKeys
     * @return array
     */
    private function applyResolvedRecord(array $data, string $key, ?Model $record, array $companionKeys = []): array
    {
        if (is_null($record)) {
            return [...$data, ...array_fill_keys([$key, ...$companionKeys], null)];
        }

        $data[$key] = $record->id;

        return $data;
    }

    /**
     * Find the Faction belonging to the Game Map with the given name.
     *
     * @param string|null $gameMapName
     * @return Faction|null
     */
    private function findFactionByMapName(?string $gameMapName): ?Faction
    {
        return Faction::whereHas('gameMap', fn (Builder $query): Builder => $query->where('name', $gameMapName))->first();
    }

    /**
     * Clear the Batch Crafting requirement unless it names a Guide Quest batch type and at least one hour.
     *
     * @param array $data
     * @return array
     */
    private function normalizeBatchCraftingRequirement(array $data): array
    {
        $batchCraftingType = BatchCraftingType::tryFrom($data['required_batch_crafting_type'] ?? '');
        $batchCraftingHours = $this->nullableInteger($data['required_batch_crafting_hours'] ?? null);

        $isValidRequirement = in_array($batchCraftingType, $this->guideQuestBatchCraftingTypes(), true)
            && ! is_null($batchCraftingHours)
            && $batchCraftingHours >= 1;

        if ($isValidRequirement) {
            return $data;
        }

        $data['required_batch_crafting_type'] = null;
        $data['required_batch_crafting_hours'] = null;

        return $data;
    }

    /**
     * Return the Batch Crafting types a Guide Quest may require experience in.
     *
     * @return array
     */
    private function guideQuestBatchCraftingTypes(): array
    {
        return [
            BatchCraftingType::CRAFT,
            BatchCraftingType::CRAFT_AND_ENCHANT,
            BatchCraftingType::ALCHEMY,
            BatchCraftingType::TRINKETRY,
        ];
    }

    /**
     * Normalize a numeric spreadsheet cell to a whole number, returning null when the cell is blank or not numeric.
     *
     * @param mixed $value
     * @return int|null
     */
    private function nullableInteger(mixed $value): ?int
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        return intval($value);
    }

    /**
     * Decode and validate one structured Guide Quest content cell.
     *
     * @param mixed $content
     * @param string $field
     * @return array|null
     */
    private function decodeStructuredContent(mixed $content, string $field): ?array
    {
        if (blank($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Guide Quest '.$field.' must contain valid structured JSON.');
        }

        $hasInvalidBlock = array_any(
            $decoded,
            fn (mixed $block): bool => ! is_array($block) || ! isset($block['id'], $block['content']),
        );

        if ($hasInvalidBlock) {
            throw new InvalidArgumentException('Guide Quest '.$field.' contains an invalid content block.');
        }

        return $decoded;
    }

    /**
     * Resolve the required batch crafted items configured for the guide quest row.
     *
     * @param array $data
     * @return array|null
     */
    private function requiredBatchCraftedItems(array $data): ?array
    {
        $requiredItems = array_values(array_filter([
            $this->requiredItemForRow($data, 1),
            $this->requiredItemForRow($data, 2),
        ]));

        if (empty($requiredItems)) {
            return null;
        }

        return $requiredItems;
    }

    /**
     * Resolve one numbered required-item column group into its persisted requirement, or null when it is incomplete or invalid.
     *
     * @param array $data
     * @param int $row
     * @return array|null
     */
    private function requiredItemForRow(array $data, int $row): ?array
    {
        $itemName = $data['required_item_'.$row.'_name'] ?? ($data['required_batch_crafted_item_'.$row.'_name'] ?? null);
        $itemType = $data['required_item_'.$row.'_type'] ?? ($data['required_batch_crafted_item_'.$row.'_type'] ?? null);
        $amount = $this->nullableInteger($data['required_item_'.$row.'_amount'] ?? ($data['required_batch_crafted_item_'.$row.'_amount'] ?? null));

        if (blank($itemName) || blank($itemType) || is_null($amount) || $amount < 1) {
            return null;
        }

        $sourceValue = $data['required_item_'.$row.'_source'] ?? null;
        $source = blank($sourceValue) ? GuideQuestRequiredItemSource::INVENTORY : GuideQuestRequiredItemSource::tryFrom($sourceValue);

        if (is_null($source)) {
            return null;
        }

        $item = $source === GuideQuestRequiredItemSource::ALCHEMY_BAG
            ? $this->findAlchemyRequirementItem($itemName, $itemType)
            : $this->findInventoryRequirementItem($itemName, $itemType);

        if (is_null($item)) {
            return null;
        }

        $mustBeEnchanted = $data['required_item_'.$row.'_must_be_enchanted'] ?? ($data['required_batch_crafted_item_'.$row.'_must_be_enchanted'] ?? false);

        return [
            'source' => $source->value,
            'item_id' => $item->id,
            'amount' => $amount,
            'must_be_enchanted' => $source === GuideQuestRequiredItemSource::INVENTORY && filter_var($mustBeEnchanted, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Resolve the craftable inventory item matching the required item name and type.
     *
     * @param string $itemName
     * @param string $itemType
     * @return Item|null
     */
    private function findInventoryRequirementItem(string $itemName, string $itemType): ?Item
    {
        if (! in_array($itemType, $this->validBatchCraftedItemTypes(), true) && ! in_array($itemType, $this->validBatchCraftedItemTypeNames(), true)) {
            return null;
        }

        $matchingTypes = [
            $itemType,
            ...array_filter(
                $this->validBatchCraftedItemTypes(),
                fn (string $validType): bool => $this->batchCraftedItemTypeName($validType) === $itemType,
            ),
        ];

        return Item::where('name', $itemName)
            ->where('can_craft', true)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', '!=', 'alchemy')
            ->whereIn('type', $matchingTypes)
            ->first();
    }

    /**
     * Resolve the alchemy item matching the required item name and alchemy type.
     *
     * @param string $itemName
     * @param string $itemType
     * @return Item|null
     */
    private function findAlchemyRequirementItem(string $itemName, string $itemType): ?Item
    {
        $matchingAlchemyTypes = [
            $itemType,
            ...array_keys(array_filter(
                $this->alchemyItemTypeNames(),
                fn (string $alchemyTypeName): bool => $alchemyTypeName === $itemType,
            )),
        ];

        return Item::where('name', $itemName)
            ->where('type', 'alchemy')
            ->whereIn('alchemy_type', $matchingAlchemyTypes)
            ->first();
    }

    /**
     * Return the item types that are valid for a required batch crafted item.
     *
     * @return array
     */
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

    /**
     * Return the human readable names for the valid batch crafted item types.
     *
     * @return array
     */
    private function validBatchCraftedItemTypeNames(): array
    {
        return array_map(fn (string $type): string => $this->batchCraftedItemTypeName($type), $this->validBatchCraftedItemTypes());
    }

    /**
     * Resolve the human readable name for a single batch crafted item type.
     *
     * @param string $type
     * @return string
     */
    private function batchCraftedItemTypeName(string $type): string
    {
        return match ($type) {
            ItemType::DAGGER->value => 'Daggers',
            ItemType::SPELL_DAMAGE->value => 'Spell Damage',
            ItemType::SPELL_HEALING->value => 'Spell Healing',
            default => ItemType::getProperNameForType($type),
        };
    }

    /**
     * Return the human readable names for the alchemy item types.
     *
     * @return array
     */
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
