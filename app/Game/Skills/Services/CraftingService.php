<?php

namespace App\Game\Skills\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\Traits\SkillCheck;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;

class CraftingService {

    use ResponseBuilder, SkillCheck, UpdateCharacterGold;

    /**
     * @var RandomEnchantmentService $randomEnchantmentService
     */
    private RandomEnchantmentService $randomEnchantmentService;

    /**
     * @var SkillService $skillService
     */
    private SkillService $skillService;

    /**
     * @param RandomEnchantmentService $randomEnchantmentService
     * @param SkillService $skillService
     */
    public function __construct(RandomEnchantmentService $randomEnchantmentService, SkillService $skillService) {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->skillService             = $skillService;
    }

    /**
     * Fetch all craftable items for a character.
     *
     * The params variable is the request params.
     *
     * @param Character $character
     * @param array $params
     * @param bool $merchantMessage
     * @return Collection
     * @throws Exception
     */
    public function fetchCraftableItems(Character $character, array $params, bool $merchantMessage = true): Collection {

        $craftingType = $params['crafting_type'];

        if ($craftingType == 'hammer' || $craftingType == 'bow' || $craftingType == 'stave') {
            $craftingType = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $craftingType);

        return $this->getItems($character, $skill, $params['crafting_type'], $merchantMessage);
    }

    /**
     * Attempts to craft the item.
     *
     * The params are the request params.
     *
     * Gold is only taken from a player if they can pick up the item they crafted or
     * if they fail to craft them item.
     *
     * @param Character $character
     * @param array $params params
     * @return bool
     * @throws Exception
     */
    public function craft(Character $character, array $params): bool {
        $item  = Item::find($params['item_to_craft']);

        $skill = $this->fetchCraftingSkill($character, $params['type']);

        if (is_null($item)) {
            event(new GameServerMessageEvent($character->user, 'Invalid Item'));

            return false;
        }

        $cost = $this->getItemCost($character, $item);

        if ($cost > $character->gold) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold'));

            return false;
        }

        return $this->attemptToCraftItem($character, $skill, $item);
    }

    protected function getItemCost(Character $character, Item $item): int {
        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.30);
        }

        return $cost;
    }

    /**
     * Attempt to craft and pick up the item.
     *
     * @param Character $character
     * @param Skill $skill
     * @param Item $item
     * @return bool
     * @throws Exception
     */
    protected function attemptToCraftItem(Character $character, Skill $skill, Item $item): bool {
        if ($skill->level < $item->skill_level_required) {
            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));

            return false;
        }

        if ($skill->level > $item->skill_level_trivial) {
            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));

            $this->pickUpItem($character, $item, $skill, true);

            return true;
        }

        $characterRoll = $this->characterRoll($skill);
        $dcCheck       = $this->getDCCheck($skill, 0);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            return true;
        }

        event(new ServerMessageEvent($character->user, 'failed_to_craft'));

        $cost = $this->getItemCost($character, $item);

        $this->updateCharacterGold($character, $cost);

        return false;
    }

    /**
     * Fetch the crafting skill.
     *
     * @param Character $character
     * @param string $craftingType
     * @return Skill
     */
    protected function fetchCraftingSkill(Character $character, string $craftingType): Skill {

        if ($craftingType === 'hammer' || $craftingType === 'bow' || $craftingType === 'stave') {
            $craftingType = 'weapon';
        }

        $gameSkill = GameSkill::where('name', ucfirst($craftingType) . ' Crafting')->first();

        return Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
    }

    /**
     * Return a list of items the player can craft for the type.
     *
     * @param Character $character
     * @param Skill $skill
     * @param string $craftingType
     * @param bool $merchantMessage
     * @return Collection
     * @throws Exception
     */
    protected function getItems(Character $character, Skill $skill, string $craftingType, bool $merchantMessage = true): Collection {
        $twoHandedWeapons = ['bow', 'hammer', 'stave'];
        $craftingTypes    = ['armour', 'ring', 'spell'];

        $items = Item::where('can_craft', true)
                    ->where('skill_level_required', '<=', $skill->level)
                    ->whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->orderBy('skill_level_required', 'asc');

        if (in_array($craftingType, $twoHandedWeapons)) {
            $items->where('default_position', strtolower($craftingType));
        } else if (in_array($craftingType, $craftingTypes)) {
            $items->where('crafting_type', strtolower($craftingType));
        } else {
            $items->where('type', strtolower($craftingType));
        }

        $items = $items->select('name', 'cost', 'type', 'id')->get();

        if ($character->classType()->isMerchant()) {
            $items = $items->transform(function($item) {
                $cost = $item->cost;

                $cost = floor($cost - $cost * 0.30);

                $item->cost = $cost;

                return $item;
            });

            if ($merchantMessage) {
                event(new GameServerMessageEvent($character->user, 'As a Merchant you get 30% discount on crafting items. The items in the list have been adjusted.'));
            }
        }

        return $items;
    }

    /**
     * Handle picking up the item.
     *
     * @param Character $character
     * @param Item $item
     * @param Skill $skill
     * @param bool $tooEasy
     * @return void
     * @throws Exception
     */
    public function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false) {
        if ($this->attemptToPickUpItem($character, $item)) {

            if (!$tooEasy) {
                $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);
            }

            if ($item->type === 'trinket') {
                $this->updateTrinketCost($character, $item);
            }

            if ($item->type !== 'trinket') {
                $this->updateCharacterGold($character, $item->cost);
            }
        }
    }

    /**
     * Attempt to pick up the item.
     *
     * @param Character $character
     * @param Item $item
     * @return bool
     */
    private function attemptToPickUpItem(Character $character, Item $item): bool {
        if (!$character->isInventoryFull()) {

            $slot = $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'crafted', $item->name, $slot->id));

            return true;
        }

        event(new ServerMessageEvent($character->user, 'inventory_full'));

        return false;
    }
}
