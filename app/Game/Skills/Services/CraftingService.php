<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CraftingService {

    use ResponseBuilder, UpdateCharacterCurrency;

    /**
     * @var RandomEnchantmentService $randomEnchantmentService
     */
    private RandomEnchantmentService $randomEnchantmentService;

    /**
     * @var SkillService $skillService
     */
    private SkillService $skillService;

    /**
     * @var ItemListCostTransformerService $itemListCostTransformerService
     */
    private ItemListCostTransformerService $itemListCostTransformerService;

    /**
     * @var SkillCheckService $skillCheckService
     */
    private SkillCheckService $skillCheckService;

    /**
     * @var UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty
     */
    private UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty;

    /**
     * @var HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal
     */
    private HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    /**
     * @var bool $craftForNpc
     */
    private bool $craftForNpc = false;

    /**
     * @var bool $craftForEvent
     */
    private bool $craftForEvent = false;

    /**
     * @param RandomEnchantmentService $randomEnchantmentService
     * @param SkillService $skillService
     * @param ItemListCostTransformerService $itemListCostTransformerService
     * @param SkillCheckService $skillCheckService
     * @param UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty
     * @param HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal
     */
    public function __construct(
        RandomEnchantmentService $randomEnchantmentService,
        SkillService $skillService,
        ItemListCostTransformerService $itemListCostTransformerService,
        SkillCheckService $skillCheckService,
        UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty,
        HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
    ) {
        $this->randomEnchantmentService              = $randomEnchantmentService;
        $this->skillService                          = $skillService;
        $this->itemListCostTransformerService        = $itemListCostTransformerService;
        $this->skillCheckService                     = $skillCheckService;
        $this->updateCraftingTasksForFactionLoyalty  = $updateCraftingTasksForFactionLoyalty;
        $this->handleUpdatingCraftingGlobalEventGoal = $handleUpdatingCraftingGlobalEventGoal;
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
        $defaultToWeapon = [
            'hammer',
            'bow',
            'stave',
            'gun',
            'fan',
            'mace',
            'scratch-awl',
        ];

        if (in_array($craftingType, $defaultToWeapon)) {
            $craftingType = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $craftingType);

        return $this->getItems($character, $skill, $params['crafting_type'], $merchantMessage);
    }

    /**
     * Get Crafting XP
     *
     * @param Character $character
     * @param string $type
     * @return array
     */
    public function getCraftingXP(Character $character, string $type): array {
        if ($type == 'hammer' ||
            $type == 'bow' ||
            $type == 'stave' ||
            $type === 'gun' ||
            $type === 'fan' ||
            $type === 'mace' ||
            $type === 'scratch-awl'
        ) {
            $type = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $type);

        return [
            'current_xp'    => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name'    => $skill->name,
            'level'         => $skill->level
        ];
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

        $this->craftForNpc = $params['craft_for_npc'];

        $this->craftForEvent = $params['craft_for_event'];

        $item  = Item::find($params['item_to_craft']);

        $skill = $this->fetchCraftingSkill($character, $params['type']);

        if (is_null($item)) {
            event(new ServerMessageEvent($character->user, 'Invalid Item'));

            return false;
        }

        $this->handleCraftingTimeOut($character, $item);

        $cost = $this->getItemCost($character, $item);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, 'not_enough_gold');

            return false;
        }

        return $this->attemptToCraftItem($character, $skill, $item);
    }

    /**
     * Handle crafting timeout.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     * @throws Exception
     */
    protected function handleCraftingTimeOut(Character $character, Item $item): void {
        $craftingTimeOut = null;

        if (
            $character->classType()->isBlacksmith() &&
            (WeaponTypes::isWeaponType($item->type) ||
                ArmourTypes::isArmourType($item->type))
        ) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Blacksmith, your crafting timeout is reduced by 25% for weapons (including rings) and armour.');

            $craftingTimeOut = ceil(10 - 10 * 0.25);
        }

        if ($character->classType()->isBlacksmith() && SpellTypes::isSpellType($item->type)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Blacksmith, your crafting timeout is increased by 25% for spell crafting.');

            $craftingTimeOut = ceil(10 + 10 * 0.25);
        }

        if ($character->classType()->isArcaneAlchemist() && SpellTypes::isSpellType($item->type)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Arcane Alchemist, your crafting timeout is reduced by 15% for spell crafting.');

            $craftingTimeOut = ceil(10 - 10 * 0.15);
        }

        event(new CraftedItemTimeOutEvent($character, null, $craftingTimeOut));
    }

    protected function getItemCost(Character $character, Item $item): int {
        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.30);
        }

        if ($character->classType()->isBlacksmith() && (WeaponTypes::isWeaponType($item->type) || ArmourTypes::isArmourType($item->type)
        )) {
            $cost = floor($cost - $cost * 0.25);
        }

        if ($character->classType()->isArcaneAlchemist() && (SpellTypes::isSpellType($item->type))) {
            $cost = floor($cost - $cost * 0.15);
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
            ServerMessageHandler::handleMessage($character->user, 'to_hard_to_craft');

            return false;
        }

        if ($skill->level > $item->skill_level_trivial) {
            ServerMessageHandler::handleMessage($character->user, 'to_easy_to_craft');

            $this->pickUpItem($character, $item, $skill, true);

            return true;
        }

        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck       = $this->skillCheckService->getDCCheck($skill, 0);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, 'failed_to_craft');

        $this->updateCharacterGold($character, $item);

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

        if ($craftingType === 'hammer' ||
            $craftingType === 'bow' ||
            $craftingType === 'stave' ||
            $craftingType === 'gun' ||
            $craftingType === 'fan' ||
            $craftingType === 'mace' ||
            $craftingType === 'scratch-awl'
        ) {
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
    protected function getItems(Character $character, Skill $skill, string $craftingType, bool $merchantMessage = true): SupportCollection {
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

        return $this->itemListCostTransformerService->reduceCostOfCraftingItems($character, $items, $craftingType, $merchantMessage);
    }

    /**
     * Handle picking up the item.
     *
     * @param Character $character
     * @param Item $item
     * @param Skill $skill
     * @param bool $tooEasy
     * @param bool $updateGoldCost
     * @return void
     * @throws Exception
     */
    public function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false, bool $updateGoldCost = true): void {

        if ($this->craftForNpc) {
            $result = $this->handleCraftingForNpc($character, $item, $skill, $tooEasy, $updateGoldCost);

            if ($result) {
                return;
            }
        }

        if ($this->craftForEvent) {
            $result = $this->handleCraftingForEvent($character, $item, $skill, $tooEasy, $updateGoldCost);

            if ($result) {
                return;
            }
        }

        if ($this->attemptToPickUpItem($character, $item)) {

            if (!$tooEasy) {
                $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);
            }

            if ($updateGoldCost) {
                $this->updateCharacterGold($character, $item);
            }
        }
    }

    /**
     * Handle crafting for npc faction loyalty.
     *
     * @param Character $character
     * @param Item $item
     * @param Skill $skill
     * @param bool $tooEasy
     * @param bool $updateGoldCost
     * @return bool
     * @throws Exception
     */
    private function handleCraftingForNpc(Character $character, Item $item, Skill $skill, bool $tooEasy, bool $updateGoldCost): bool {
        $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character, $item);

        if ($this->updateCraftingTasksForFactionLoyalty->handedOverItem()) {
            if (!$tooEasy) {
                $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);
            }

            if ($updateGoldCost) {
                $this->updateCharacterGold($character, $item);
            }

            return true;
        }

        return false;
    }

    /**
     * Handle craft for global events.
     *
     * @param Character $character
     * @param Item $item
     * @param Skill $skill
     * @param bool $tooEasy
     * @param bool $updateGoldCost
     * @return bool
     * @throws Exception
     */
    private function handleCraftingForEvent(Character $character, Item $item, Skill $skill, bool $tooEasy, bool $updateGoldCost): bool {
        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        if ($this->handleUpdatingCraftingGlobalEventGoal->handedOverItem()) {
            if (!$tooEasy) {
                $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);
            }

            if ($updateGoldCost) {
                $this->updateCharacterGold($character, $item);
            }

            return true;
        }

        return false;
    }

    /**
     * Attempt to pick up the item.
     *
     * @param Character $character
     * @param Item $item
     * @return bool
     */
    private function attemptToPickUpItem(Character $character, Item $item): bool {

        if ($this->craftForNpc) {

        }

        if (!$character->isInventoryFull()) {

            $slot = $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            ServerMessageHandler::handleMessage($character->user, 'crafted', $item->name, $slot->id);

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, 'inventory_full');

        return false;
    }
}
