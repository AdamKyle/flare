<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Events\Concerns\ShouldShowEnchantingEventButton;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class EnchantingAffixService
{
    use ShouldShowEnchantingEventButton;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     * @param GlobalEventGoalEligibilityService $globalEventGoalEligibilityService
     */
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder,
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
    ) {}

    /**
     * Fetches the affixes for a character.
     *
     * Only returns that which the player has the skill level and intelligence for.
     *
     * @param Character $character
     * @param bool $ignoreTrinkets
     * @param bool $showMerchantMessage
     * @return array
     */
    public function fetchAffixes(Character $character, bool $ignoreTrinkets = false, bool $showMerchantMessage = true): array
    {
        $characterInfo = $this->characterStatBuilder->setCharacter($character);
        $enchantingSkill = $this->getEnchantingSkill($character);

        $inventory = $this->getEligibleInventorySlots($character);

        if ($ignoreTrinkets) {
            $inventory = $inventory->reject(fn (InventorySlot $slot) => in_array($slot->item->type, ['trinket', 'artifact'], true));
        }

        [$noAffix, $withAffix] = $inventory->partition(fn (InventorySlot $slot) => $slot->item->affix_count === 0);

        $newInventory = $noAffix->merge($withAffix);

        return [
            'affixes' => $this->getAvailableAffixes($characterInfo, $enchantingSkill, $showMerchantMessage),
            'character_inventory' => $newInventory,
            'show_enchanting_for_event' => $this->shouldShowEnchantingEventButton($character),
            'items_for_event' => $this->fetchEventItemsForEnchanting($character),
        ];
    }

    /**
     * Resolve the character's non-quest/alchemy/gem, unequipped Inventory slots eligible for enchanting, honoring the existing disenchant slot-ignore cache.
     *
     * @param Character $character
     * @return Collection
     */
    private function getEligibleInventorySlots(Character $character): Collection
    {
        $slotsToIgnore = Cache::get('character-slots-to-disenchant-'.$character->id, []);

        return $character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem'])
            ->whereNotIn('id', $slotsToIgnore)
            ->where('equipped', false)
            ->values();
    }

    /**
     * Build the lean Global Event Crafting Inventory item payload for enchanting selection.
     *
     * @param Character $character
     * @return array
     */
    private function fetchEventItemsForEnchanting(Character $character): array
    {
        return $this->fetchEventItemSlotsForEnchanting($character)
            ->map(fn ($slot) => [
                'slot_id' => $slot->id,
                'item_name' => $slot->item->affix_name,
                'affix_count' => $slot->item->affix_count,
            ])
            ->toArray();
    }

    /**
     * Resolve the character's eligible Global Event Crafting Inventory slots for enchanting.
     *
     * @param Character $character
     * @return Collection
     */
    private function fetchEventItemSlotsForEnchanting(Character $character): Collection
    {
        $globalEventGoal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($globalEventGoal)) {
            return new Collection;
        }

        $eventInventory = GlobalEventCraftingInventory::where('character_id', $character->id)
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first();

        if (is_null($eventInventory)) {
            return new Collection;
        }

        return $eventInventory->craftingSlots()
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereNotIn('type', ['quest', 'alchemy', 'gem', 'trinket', 'artifact']);
            })
            ->get();
    }

    /**
     * Resolve the currently available non-random affixes for the character's Enchanting skill level.
     *
     * @param CharacterStatBuilder $builder
     * @param Skill $enchantingSkill
     * @param bool $showMerchantMessage
     * @return Collection
     */
    private function getAvailableAffixes(CharacterStatBuilder $builder, Skill $enchantingSkill, bool $showMerchantMessage = true): Collection
    {
        $affixes = ItemAffix::where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('randomly_generated', false)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        $character = $builder->character();

        if ($character->classType()->isMerchant() && $showMerchantMessage) {
            event(new ServerMessageEvent($character->user, 'As a Merchant you get 15% discount on enchanting items. This discount is applied to the total cost of the enchantments, not the individual enchantments.'));
        }

        return $affixes;
    }

    /**
     * Resolve the character's Enchanting skill, assuming it exists.
     *
     * @param Character $character
     * @return Skill
     */
    private function getEnchantingSkill(Character $character): Skill
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();

        return Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();
    }
}
