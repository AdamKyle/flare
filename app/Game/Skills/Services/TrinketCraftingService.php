<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Character\CharacterInventory\Exceptions\BatchCraftingDestinationFullException;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\DB;

class TrinketCraftingService
{
    private CraftingService $craftingService;

    private SkillCheckService $skillCheckService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    private SkillService $skillService;

    public function __construct(
        CraftingService $craftingService,
        SkillCheckService $skillCheckService,
        ItemListCostTransformerService $itemListCostTransformerService,
        SkillService $skillService,
    ) {
        $this->craftingService = $craftingService;
        $this->skillCheckService = $skillCheckService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
        $this->skillService = $skillService;
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
            ->select('name', 'id', 'gold_dust_cost', 'copper_coin_cost', 'skill_level_required')
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
            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            return $this->fetchItemsToCraft($character);
        }

        if ($trinkentrySkill->level > $item->skill_level_trivial) {
            ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $this->deductCraftingCost($character, $item);

            $this->craftingService->pickUpItem($character, $item, $trinkentrySkill, true);

            return $this->fetchItemsToCraft($character);
        }

        $this->deductCraftingCost($character, $item);

        if (! $this->canCraft($trinkentrySkill)) {
            event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

            return $this->fetchItemsToCraft($character);
        }

        $this->craftingService->pickUpItem($character, $item, $trinkentrySkill);

        return $this->fetchItemsToCraft($character->refresh(), false);
    }

    /**
     * Craft a trinket directly for Batch Crafting, with no InventorySlot involved.
     *
     * Preserves the same affordability, skill requirement, success/failure roll, and
     * currency spending rules as craft(), but never picks the item up into inventory.
     *
     * @throws Exception
     */
    public function craftForBatch(
        Character $character,
        Item $item,
        bool $suppressSuccessServerMessage = false,
        ?callable $destinationCreator = null,
    ): array
    {
        return DB::transaction(function () use ($character, $item, $suppressSuccessServerMessage, $destinationCreator): array {
            $trinkentrySkill = $this->fetchCharacterSkill($character);

            if (! $this->canAfford($character, $item)) {
                return [
                    'success' => false,
                    'item' => null,
                    'reason' => 'not_enough_currency',
                    'destination' => null,
                    'cost' => $this->craftingCost($character->refresh(), $item),
                ];
            }

            if ($trinkentrySkill->level < $item->skill_level_required) {
                ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

                return ['success' => false, 'item' => null, 'reason' => 'skill_too_low', 'destination' => null];
            }

            if ($trinkentrySkill->level > $item->skill_level_trivial) {
                ServerMessageHandler::handlemessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

                $this->deductCraftingCost($character, $item);

                if (! $suppressSuccessServerMessage) {
                    ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
                }

                $destination = is_null($destinationCreator) ? null : $destinationCreator($item);

                if (! is_null($destinationCreator) && ! is_array($destination)) {
                    throw new BatchCraftingDestinationFullException('The retained Batch Crafting destination could not accept the crafted trinket.');
                }

                return ['success' => true, 'item' => $item, 'reason' => null, 'destination' => $destination];
            }

            $this->deductCraftingCost($character, $item);

            if (! $this->canCraft($trinkentrySkill)) {
                event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

                return ['success' => false, 'item' => null, 'reason' => 'failed_roll', 'destination' => null];
            }

            if (! $suppressSuccessServerMessage) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
            }

            $destination = is_null($destinationCreator) ? null : $destinationCreator($item);

            if (! is_null($destinationCreator) && ! is_array($destination)) {
                throw new BatchCraftingDestinationFullException('The retained Batch Crafting destination could not accept the crafted trinket.');
            }

            $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $trinkentrySkill);

            return ['success' => true, 'item' => $item, 'reason' => null, 'destination' => $destination];
        });
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
    public function craftingCost(Character $character, Item $item): array
    {
        $copperCoinCost = (int) $item->copper_coin_cost;
        $goldDustCost = (int) $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $copperCoinCost = floor($copperCoinCost - $copperCoinCost * 0.10);
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
        }

        return [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'gold_dust' => [
                'required' => (int) $goldDustCost,
                'available' => (int) $character->gold_dust,
                'missing' => max(0, (int) $goldDustCost - (int) $character->gold_dust),
            ],
            'copper_coins' => [
                'required' => (int) $copperCoinCost,
                'available' => (int) $character->copper_coins,
                'missing' => max(0, (int) $copperCoinCost - (int) $character->copper_coins),
            ],
        ];
    }

    protected function canAfford(Character $character, Item $item): bool
    {
        $cost = $this->craftingCost($character, $item);

        return $cost['gold_dust']['missing'] === 0
            && $cost['copper_coins']['missing'] === 0;
    }

    private function deductCraftingCost(Character $character, Item $item): void
    {
        $cost = $this->craftingCost($character, $item);

        $character->update([
            'gold_dust' => $character->gold_dust - $cost['gold_dust']['required'],
            'copper_coins' => $character->copper_coins - $cost['copper_coins']['required'],
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));
    }

    /**
     * Can the character craft this item?
     */
    protected function canCraft(Skill $trinketSkill): bool
    {
        return $this->skillCheckService->characterRoll($trinketSkill) > $this->skillCheckService->getDCCheck($trinketSkill);
    }
}
