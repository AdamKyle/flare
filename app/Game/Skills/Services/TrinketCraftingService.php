<?php

namespace App\Game\Skills\Services;


use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Events\ServerMessageEvent as FlareServerMessage;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\Traits\SkillCheck;

class TrinketCraftingService {

    use SkillCheck;

    /**
     * @var CraftingService $craftingService
     */
    private $craftingService;

    /**
     * @param CraftingService $craftingService
     */
    public function __construct(CraftingService $craftingService) {
        $this->craftingService = $craftingService;
    }

    /**
     * Fetch trinkets the player can craft.
     *
     * @param Character $character
     * @return array
     */
    public function fetchItemsToCraft(Character $character): array {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        return Item::where('type', 'trinket')
                    ->where('skill_level_required', '<=', $trinkentrySkill->level)
                    ->select('name', 'id', 'gold_dust_cost', 'copper_coin_cost')
                    ->get()
                    ->toArray();
    }

    /**
     * Attempt to craft the item.
     *
     * - Removes currency
     * - Crafts, attempts to, item
     * - Attempts to give item to player
     *
     * @param Character $character
     * @param Item $item
     * @return array
     */
    public function craft(Character $character, Item $item): array {
        $trinkentrySkill = $this->fetchCharacterSkill($character);

        if (!$this->canAfford($character, $item)) {
            event(new ServerMessageEvent($character->user, 'You do not have enough of the required currencies to craft this.'));

            return $this->fetchItemsToCraft($character);
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        if ($trinkentrySkill->level < $item->$trinkentrySkill) {
            event(new FlareServerMessage($character->user, 'to_hard_to_craft'));

            return $this->fetchItemsToCraft($character);
        }

        if ($trinkentrySkill->level >= $item->skill_level_trivial) {
            event(new FlareServerMessage($character->user, 'to_easy_to_craft'));

            $this->craftingService->pickUpItem($character, $item, $trinkentrySkill, true);

            return $this->fetchItemsToCraft($character);
        }

        if (!$this->canCraft($character, $item, $trinkentrySkill)) {
            event(new ServerMessageEvent($character->user, 'You failed to craft the trinket. All your efforts fall apart before your eyes!'));

            return $this->fetchItemsToCraft($character);
        }

        $this->craftingService->pickUpItem($character, $item, $trinkentrySkill);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        return $this->fetchItemsToCraft($character->refresh());
    }

    /**
     * Fetch the crafting skill for the player.
     *
     * @param Character $character
     * @return Skill
     */
    protected function fetchCharacterSkill(Character $character): Skill {
        $gameSkill = GameSkill::where('name', 'Trinketry')->first();

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }

    /**
     * Can the player afford to make this item?
     *
     * @param Character $character
     * @param Item $item
     * @return bool
     */
    protected function canAfford(Character $character, Item $item): bool {

        if ($character->gold_dust < $item->gold_dust_cost) {
            return false;
        }

        if ($character->copper_coins < $item->copper_coin_cost) {
            return false;
        }

        return true;
    }

    /**
     * Can the character craft this item?
     *
     * @param Character $character
     * @param Item $item
     * @param Skill $trinketSkill
     * @return bool
     */
    protected function canCraft(Character $character, Item $item, Skill $trinketSkill): bool {
        return $this->characterRoll($trinketSkill) > $this->getDCCheck($trinketSkill);
    }
}
