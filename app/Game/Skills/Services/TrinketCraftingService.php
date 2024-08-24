<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class TrinketCraftingService
{
    use UpdateCharacterCurrency;

    private CraftingService $craftingService;

    private SkillCheckService $skillCheckService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    public function __construct(
        CraftingService $craftingService,
        SkillCheckService $skillCheckService,
        ItemListCostTransformerService $itemListCostTransformerService
    ) {
        $this->craftingService = $craftingService;
        $this->skillCheckService = $skillCheckService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
    }

    /**
     * Fetch trinkets the player can craft.
     *
     * @throws Exception
     */
    public function fetchItemsToCraft(Character $character, bool $showMerchantMessage = true): array
    {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        $items = Item::where('type', 'trinket')
            ->where('skill_level_required', '<=', $trinkentrySkill->level)
            ->orderBy('skill_level_required', 'asc')
            ->select('name', 'id', 'gold_dust_cost', 'copper_coin_cost')
            ->get();

        return $this->itemListCostTransformerService->reduceCostForTrinketryItems($character, $items, $showMerchantMessage)->toArray();
    }

    public function fetchSkillXP(Character $character): array
    {
        $skill = $this->fetchCharacterSkill($character);

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    /**
     * Attempt to craft the item.
     *
     * - Removes currency
     * - Crafts, attempts to, item
     * - Attempts to give item to player
     *
     * @throws Exception
     */
    public function craft(Character $character, Item $item): array
    {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        if (! $this->canAfford($character, $item)) {
            event(new ServerMessageEvent($character->user, 'You do not have enough of the required currencies to craft this.'));

            return $this->fetchItemsToCraft($character);
        }

        if ($trinkentrySkill->level < $item->skill_level_required) {
            ServerMessageHandler::handlemessage($character->user, 'to_hard_to_craft');

            return $this->fetchItemsToCraft($character);
        }

        if ($trinkentrySkill->level > $item->skill_level_trivial) {
            ServerMessageHandler::handlemessage($character->user, 'to_easy_to_craft');

            $this->updateTrinketCost($character, $item);

            $this->craftingService->pickUpItem($character, $item, $trinkentrySkill, true);

            return $this->fetchItemsToCraft($character);
        }

        $this->updateTrinketCost($character, $item);

        if (! $this->canCraft($trinkentrySkill)) {
            event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

            return $this->fetchItemsToCraft($character);
        }

        $this->craftingService->pickUpItem($character, $item, $trinkentrySkill);

        return $this->fetchItemsToCraft($character->refresh(), false);
    }

    /**
     * Fetch the crafting skill for the player.
     */
    protected function fetchCharacterSkill(Character $character): Skill
    {
        $gameSkill = GameSkill::where('name', 'Trinketry')->first();

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Can the player afford to make this item?
     *
     * @throws Exception
     */
    protected function canAfford(Character $character, Item $item): bool
    {

        $copperCoinCost = $item->copper_coin_cost;
        $goldDustCostCost = $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $copperCoinCost = floor($copperCoinCost - $copperCoinCost * 0.10);
            $goldDustCostCost = floor($goldDustCostCost - $goldDustCostCost * 0.10);
        }

        if ($character->gold_dust < $goldDustCostCost) {
            return false;
        }

        if ($character->copper_coins < $copperCoinCost) {
            return false;
        }

        return true;
    }

    /**
     * Can the character craft this item?
     */
    protected function canCraft(Skill $trinketSkill): bool
    {
        return $this->skillCheckService->characterRoll($trinketSkill) > $this->skillCheckService->getDCCheck($trinketSkill);
    }
}
