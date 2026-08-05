<?php

namespace App\Game\Npcs\Actions\QueenOfHearts\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\RandomAffixTier;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RandomEnchantmentService
{
    private RandomAffixGenerator $randomAffixGenerator;

    const ITEM_COST = 2_000_000_000;

    public function __construct(
        RandomAffixGenerator $randomAffixGenerator,
        private readonly ChanceCalculator $chanceCalculator,
    ) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Generate for type.
     *
     * @throws Exception
     */
    public function generateForType(Character $character): Item
    {
        return $this->generateRandomAffixForRandom($character, RandomAffixTier::LEGENDARY->value);
    }

    /**
     * Get cost of unique.
     */
    public function getCost(): int
    {
        return RandomAffixTier::LEGENDARY->value;
    }

    /**
     * Authoritative query for the character's unique-source inventory slots.
     *
     * Preserves the exact current eligibility rule: not equipped, and
     * (mythic, unique, or cosmic) with at least one randomly generated affix.
     */
    public function buildUniqueInventoryQuery(Character $character): Builder
    {
        return $this->baseInventoryQuery($character)->whereHas('item', $this->uniqueItemEligibility());
    }

    /**
     * Fetch uniques from characters inventory.
     */
    public function fetchUniquesFromCharactersInventory(Character $character): Collection
    {
        return $this->buildUniqueInventoryQuery($character)->get();
    }

    /**
     * Fetch Api data.
     */
    public function fetchDataForApi(Character $character): array
    {
        $uniqueSlots = $this->fetchUniquesFromCharactersInventory($character);
        $nonUniqueSlots = $this->fetchNonUniqueItems($character);

        return [
            'unique_slots' => $uniqueSlots,
            'non_unique_slots' => $nonUniqueSlots,
        ];
    }

    /**
     * Authoritative query for the character's non-unique destination-eligible inventory slots.
     *
     * Preserves the exact current eligibility rule: not equipped, not a quest/alchemy/trinket/artifact
     * item, not mythic or cosmic, and without any randomly generated affix (not unique).
     */
    public function buildNonUniqueInventoryQuery(Character $character): Builder
    {
        return $this->baseInventoryQuery($character)->whereHas('item', $this->nonUniqueItemEligibility());
    }

    /**
     * Fetch non unique items.
     */
    public function fetchNonUniqueItems(Character $character): Collection
    {
        return $this->buildNonUniqueInventoryQuery($character)->get();
    }

    /**
     * Authoritative single query for every slot eligible as a Queen movement destination:
     * the union of the unique-source and non-unique-destination eligibility rules.
     */
    public function buildDestinationInventoryQuery(Character $character): Builder
    {
        $uniqueEligibility = $this->uniqueItemEligibility();
        $nonUniqueEligibility = $this->nonUniqueItemEligibility();

        return $this->baseInventoryQuery($character)
            ->where(function ($eligibility) use ($uniqueEligibility, $nonUniqueEligibility) {
                $eligibility->whereHas('item', $uniqueEligibility)
                    ->orWhereHas('item', $nonUniqueEligibility);
            });
    }

    /**
     * Base inventory-slot query scoped to the character's own unequipped inventory.
     */
    private function baseInventoryQuery(Character $character): Builder
    {
        return InventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks', 'item.itemSkillProgressions'])
            ->where('inventory_id', $character->inventory->id)
            ->where('equipped', false);
    }

    /**
     * Item-level eligibility for a Queen unique-source item: mythic, unique, or cosmic,
     * with at least one randomly generated affix.
     */
    private function uniqueItemEligibility(): callable
    {
        return function ($itemQuery) {
            $itemQuery->where(function ($eligibility) {
                $eligibility->where('is_mythic', true)
                    ->orWhere('is_cosmic', true)
                    ->orWhereHas('itemPrefix', $this->randomlyGeneratedAffixQuery())
                    ->orWhereHas('itemSuffix', $this->randomlyGeneratedAffixQuery());
            })->where(function ($hasRandomlyGeneratedAffix) {
                $hasRandomlyGeneratedAffix->whereHas('itemPrefix', $this->randomlyGeneratedAffixQuery())
                    ->orWhereHas('itemSuffix', $this->randomlyGeneratedAffixQuery());
            });
        };
    }

    /**
     * Item-level eligibility for a Queen non-unique destination item: not a
     * quest/alchemy/trinket/artifact item, not mythic or cosmic, and not unique.
     */
    private function nonUniqueItemEligibility(): callable
    {
        return function ($itemQuery) {
            $itemQuery->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                ->where('is_mythic', false)
                ->where('is_cosmic', false)
                ->where(function ($notUnique) {
                    $notUnique->whereDoesntHave('itemPrefix', $this->randomlyGeneratedAffixQuery())
                        ->whereDoesntHave('itemSuffix', $this->randomlyGeneratedAffixQuery());
                });
        };
    }

    /**
     * Reusable affix-eligibility closure: the affix exists and is randomly generated.
     */
    private function randomlyGeneratedAffixQuery(): callable
    {
        return fn ($affixQuery) => $affixQuery->where('randomly_generated', true);
    }

    /**
     * Check if player is in hell.
     */
    public function isPlayerInHell(Character $character): bool
    {
        return $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::QUEEN_OF_HEARTS->value;
        })->isNotEmpty() && $character->map->gameMap->mapType()->isHell();
    }

    protected function shouldAddSuffixToItem(): bool
    {
        return $this->chanceCalculator->passesPercentage(50.0);
    }

    /**
     * Generate completely random affix.
     *
     * @throws Exception
     */
    protected function generateRandomAffixForRandom(Character $character, int $amount): Item
    {
        $item = Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->where('cost', '<=', self::ITEM_COST)
            ->inRandomOrder()
            ->first();

        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount($amount);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if ($this->shouldAddSuffixToItem()) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id,
            ]);
        }

        $duplicateItem->update([
            'market_sellable' => true,
        ]);

        return $duplicateItem;
    }
}
