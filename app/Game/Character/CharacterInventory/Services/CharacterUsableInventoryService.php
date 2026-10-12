<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use League\Fractal\Manager;

class CharacterUsableInventoryService
{
    /**
     * @param UsableItemTransformer $usableItemTransformer
     * @param Pagination $pagination
     * @param Manager $manager
     */
    public function __construct(
        private readonly UsableItemTransformer $usableItemTransformer,
        private readonly Pagination $pagination,
        private readonly Manager $manager,
    ) {}

    /**
     * Return transformed usable Alchemy Bag Items.
     *
     * @param Character $character
     * @param string $searchText
     * @param array $filters
     * @return array
     */
    public function usableItems(Character $character, string $searchText = '', array $filters = []): array
    {
        return $this->usableItemSlots($character, $searchText, $filters)
            ->map(function (AlchemyBagSlot $slot): array {
                return array_merge($this->usableItemTransformer->transform($slot), [
                    'id' => $slot->id,
                    'amount' => $slot->amount,
                ]);
            })
            ->all();
    }

    /**
     * Return paginated usable Alchemy Bag Items.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filters
     * @return array
     */
    public function fetchUsableItems(
        Character $character,
        int $perPage = 10,
        int $page = 1,
        string $searchText = '',
        array $filters = [],
    ): array {
        return $this->pagination->buildPaginatedDate(
            $this->usableItemSlots($character, $searchText, $filters),
            $this->usableItemTransformer,
            $perPage,
            $page,
        );
    }

    /**
     * Return filtered Alchemy Bag slots.
     *
     * @param Character $character
     * @param string $searchText
     * @param array $filters
     * @return Collection
     */
    private function usableItemSlots(Character $character, string $searchText, array $filters): Collection
    {
        if (is_null($character->alchemyBag)) {
            return new Collection;
        }

        $slots = AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)
            ->where('character_id', $character->id)
            ->with('item')
            ->get();

        if ($searchText !== '') {
            $search = Str::lower($searchText);
            $slots = $slots->filter(fn (AlchemyBagSlot $slot): bool => Str::contains(Str::lower($slot->item->name), $search));
        }

        if (! empty($filters)) {
            $slots = $slots->filter(fn (AlchemyBagSlot $slot): bool => $this->matchesFilters($slot->item, $filters));
        }

        return new Collection($slots->values());
    }

    /**
     * Determine whether a usable Item matches a selected filter.
     *
     * @param Item $item
     * @param array $filters
     * @return bool
     */
    private function matchesFilters(Item $item, array $filters): bool
    {
        $matchesBaseModifiers = $item->base_damage_mod > 0
            || $item->base_ac_mod > 0
            || $item->base_healing_mod > 0
            || $item->fight_time_out_mod_bonus > 0
            || $item->move_time_out_mod_bonus > 0;

        return (isset($filters['increase-stats']) && $item->increase_stat_by > 0)
            || (isset($filters['effects-skills']) && ($item->increase_skill_bonus_by > 0 || $item->increase_skill_training_bonus_by > 0))
            || (isset($filters['effects-base-modifiers']) && $matchesBaseModifiers)
            || (isset($filters['damages-kingdoms']) && $item->damages_kingdoms)
            || (isset($filters['holy-oils']) && ! is_null($item->holy_level))
            || (isset($filters['scrolls']) && ! is_null($item->gem_scroll_type))
            || (isset($filters['xp-scrolls']) && $item->gem_scroll_type === GemScrollType::XP)
            || (isset($filters['currency-scrolls']) && $item->gem_scroll_type === GemScrollType::CURRENCY)
            || (isset($filters['item-scrolls']) && $item->gem_scroll_type === GemScrollType::ITEM);
    }
}
