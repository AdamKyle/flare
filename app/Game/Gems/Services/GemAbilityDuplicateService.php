<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Game\Core\Items\Values\ItemUniqueness;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\GemAbilityType;

class GemAbilityDuplicateService
{
    /**
     * Return the first deterministic Active Gem Ability modifier for every ability id.
     *
     * @param iterable $items
     * @return array
     */
    public function firstActiveModifierIds(iterable $items): array
    {
        return array_values(array_map(
            fn (array $occurrences): int => $occurrences[0]['modifier']->id,
            $this->groupAbilityOccurrences($items, GemAbilityType::ACTIVE),
        ));
    }

    /**
     * Return duplicate Active Gem Abilities and the Items containing them.
     *
     * @param iterable $items
     * @return array
     */
    public function duplicateActiveAbilities(iterable $items): array
    {
        $groups = array_filter(
            $this->groupAbilityOccurrences($items, GemAbilityType::ACTIVE),
            fn (array $occurrences): bool => count($occurrences) > 1,
        );

        return array_values(array_map(
            fn (array $occurrences): array => $this->transformDuplicateGroup($occurrences),
            $groups,
        ));
    }

    /**
     * Return character-wide duplicate Active abilities that contain the viewed owned Item.
     *
     * @param Character $character
     * @param Item $item
     * @return array
     */
    public function duplicateActiveAbilitiesForOwnedItem(Character $character, Item $item): array
    {
        $duplicates = $this->duplicateActiveAbilities($this->buildOwnedItemContext($character));

        return array_values(array_filter(
            $duplicates,
            fn (array $duplicate): bool => collect($duplicate['items'])->contains('item_id', $item->id),
        ));
    }

    /**
     * Return character-wide duplicate Active abilities that contain an Item from the selected Set.
     *
     * @param Character $character
     * @param InventorySet $inventorySet
     * @return array
     */
    public function duplicateActiveAbilitiesForSet(Character $character, InventorySet $inventorySet): array
    {
        $duplicates = $this->duplicateActiveAbilities($this->buildOwnedItemContext($character));

        return array_values(array_filter(
            $duplicates,
            fn (array $duplicate): bool => collect($duplicate['items'])->contains('set_id', $inventorySet->id),
        ));
    }

    /**
     * Build the eager-loaded Item and ownership-location context for a Character.
     *
     * @param Character $character
     * @return array
     */
    private function buildOwnedItemContext(Character $character): array
    {
        $itemRelationships = [
            'itemPrefix',
            'itemSuffix',
            'appliedHolyStacks',
            'sockets.gem.characterModifiers.gameGemAbility',
        ];

        $character->load([
            'inventory.slots.item' => fn ($query) => $query->with($itemRelationships),
            'inventorySets.slots.item' => fn ($query) => $query->with($itemRelationships),
        ]);

        $ownedItems = [];

        foreach ($character->inventory->slots as $slot) {
            if (is_null($slot->item)) {
                continue;
            }

            $ownedItems[] = [
                'item' => $slot->item,
                'source' => $slot->equipped ? 'equipped' : 'inventory',
                'set_id' => null,
                'set_name' => null,
            ];
        }

        foreach ($character->inventorySets as $inventorySet) {
            foreach ($inventorySet->slots as $slot) {
                if (is_null($slot->item)) {
                    continue;
                }

                $ownedItems[] = [
                    'item' => $slot->item,
                    'source' => $inventorySet->is_equipped ? 'equipped' : 'set',
                    'set_id' => $inventorySet->id,
                    'set_name' => $inventorySet->name,
                ];
            }
        }

        return $ownedItems;
    }

    /**
     * Group socketed Character Gem Ability occurrences by canonical ability id.
     *
     * @param iterable $items
     * @param GemAbilityType $abilityType
     * @return array
     */
    private function groupAbilityOccurrences(iterable $items, GemAbilityType $abilityType): array
    {
        $groups = [];
        $sortedItems = collect($items)
            ->filter()
            ->map(fn (Item|array $item): array => $item instanceof Item ? [
                'item' => $item,
                'source' => 'inventory',
                'set_id' => null,
                'set_name' => null,
            ] : $item)
            ->sortBy(fn (array $itemContext): int => $itemContext['item']->id);

        foreach ($sortedItems as $itemContext) {
            $item = $itemContext['item'];
            $item->loadMissing([
                'itemPrefix',
                'itemSuffix',
                'appliedHolyStacks',
                'sockets.gem.characterModifiers.gameGemAbility',
            ]);

            foreach ($item->sockets->sortBy('id') as $socket) {
                $gem = $socket->gem;

                if (is_null($gem) || $gem->domain !== Gem::DOMAIN_CHARACTER) {
                    continue;
                }

                foreach ($gem->characterModifiers->sortBy(['roll_position', 'id']) as $modifier) {
                    if (! $this->matchesAbilityType($modifier, $abilityType)) {
                        continue;
                    }

                    $groups[$modifier->game_gem_ability_id][] = [
                        'modifier' => $modifier,
                        'item_context' => $itemContext,
                    ];
                }
            }
        }

        return $groups;
    }

    /**
     * Determine whether a modifier references the requested Gem Ability type.
     *
     * @param CharacterGemModifier $modifier
     * @param GemAbilityType $abilityType
     * @return bool
     */
    private function matchesAbilityType(CharacterGemModifier $modifier, GemAbilityType $abilityType): bool
    {
        return $modifier->modifier_type === CharacterGemModifierType::GEM_ABILITY
            && ! is_null($modifier->gameGemAbility)
            && $modifier->gameGemAbility->ability_type === $abilityType;
    }

    /**
     * Transform one duplicate group into API-safe warning metadata.
     *
     * @param array $occurrences
     * @return array
     */
    private function transformDuplicateGroup(array $occurrences): array
    {
        $ability = $occurrences[0]['modifier']->gameGemAbility;
        $items = collect($occurrences)
            ->pluck('item_context')
            ->unique(fn (array $itemContext): int => $itemContext['item']->id)
            ->values()
            ->map(fn (array $itemContext): array => $this->transformItem($itemContext))
            ->all();

        return [
            'ability' => [
                'id' => $ability->id,
                'name' => $ability->name,
            ],
            'count' => count($occurrences),
            'items' => $items,
        ];
    }

    /**
     * Transform an affected Item with the canonical fields used by item-color presentation.
     *
     * @param array $itemContext
     * @return array
     */
    private function transformItem(array $itemContext): array
    {
        $item = $itemContext['item'];

        return [
            'item_id' => $item->id,
            'name' => $item->affix_name,
            'affix_count' => $item->affix_count,
            'is_unique' => ItemUniqueness::fromItem($item)->isUnique(),
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'holy_level' => $item->holy_level,
            'type' => $item->type,
            'usable' => $item->usable,
            'damages_kingdoms' => $item->damages_kingdoms,
            'source' => $itemContext['source'],
            'set_id' => $itemContext['set_id'],
            'set_name' => $itemContext['set_name'],
        ];
    }
}
