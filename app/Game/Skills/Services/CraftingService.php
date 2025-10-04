<?php

namespace App\Game\Skills\Services;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CraftingService
{
    use ResponseBuilder, UpdateCharacterCurrency;

    private RandomEnchantmentService $randomEnchantmentService;

    private SkillService $skillService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    private SkillCheckService $skillCheckService;

    private UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty;

    private HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    private bool $craftForNpc = false;

    private bool $craftForEvent = false;

    public function __construct(
        RandomEnchantmentService $randomEnchantmentService,
        SkillService $skillService,
        ItemListCostTransformerService $itemListCostTransformerService,
        SkillCheckService $skillCheckService,
        UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty,
        HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
        private readonly FactionLoyaltyService $factionLoyaltyService,
    ) {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->skillService = $skillService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
        $this->skillCheckService = $skillCheckService;
        $this->updateCraftingTasksForFactionLoyalty = $updateCraftingTasksForFactionLoyalty;
        $this->handleUpdatingCraftingGlobalEventGoal = $handleUpdatingCraftingGlobalEventGoal;
    }

    /**
     * Fetch all craftable items for a character.
     *
     * The params variable is the request params.
     *
     * @throws Exception
     */
    public function fetchCraftableItems(Character $character, array $params, bool $merchantMessage = true): Collection
    {

        $craftingType = $params['crafting_type'];
        $defaultToWeapon = ItemType::validWeapons();

        if (
            (is_array($craftingType) && empty(array_diff($craftingType, $defaultToWeapon))) ||
            (! is_array($craftingType) && in_array($craftingType, $defaultToWeapon))
        ) {
            $craftingType = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $craftingType);

        return $this->getItems($character, $skill, $params['crafting_type'], $merchantMessage);
    }

    /**
     * Get Crafting XP
     */
    public function getCraftingXP(Character $character, string|array $type): array
    {

        $defaultToWeapon = ItemType::validWeapons();

        if (
            (is_array($type) && empty(array_diff($type, $defaultToWeapon))) ||
            (! is_array($type) && in_array($type, $defaultToWeapon))
        ) {
            $type = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $type);

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    public function getInventoryCount(Character $character): array
    {
        return [
            'current_count' => $character->getInventoryCount(),
            'max_inventory' => $character->inventory_max,
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
     * @param  array  $params  params
     *
     * @throws Exception
     */
    public function craft(Character $character, array $params): bool
    {

        $this->craftForNpc = $params['craft_for_npc'];

        $this->craftForEvent = $params['craft_for_event'];

        $item = Item::find($params['item_to_craft']);

        $skill = $this->fetchCraftingSkill($character, $params['type']);

        if (is_null($item)) {
            event(new ServerMessageEvent($character->user, 'Invalid Item'));

            return false;
        }

        $this->handleCraftingTimeOut($character, $item);

        $cost = $this->getItemCost($character, $item);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_GOLD);

            return false;
        }

        return $this->attemptToCraftItem($character, $skill, $item);
    }

    /**
     * Handle crafting timeout.
     *
     * @throws Exception
     */
    protected function handleCraftingTimeOut(Character $character, Item $item): void
    {
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

    protected function getItemCost(Character $character, Item $item): int
    {
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
     * @throws Exception
     */
    protected function attemptToCraftItem(Character $character, Skill $skill, Item $item): bool
    {
        if ($skill->level < $item->skill_level_required) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            return false;
        }

        if ($skill->level > $item->skill_level_trivial) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $this->pickUpItem($character, $item, $skill, true);

            return true;
        }

        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck = $this->skillCheckService->getDCCheck($skill, 0);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::FAILED_TO_CRAFT);

        $this->updateCharacterGold($character, $item);

        return false;
    }

    /**
     * Fetch the crafting skill.
     */
    protected function fetchCraftingSkill(Character $character, string $craftingType): Skill
    {

        if (
            in_array($craftingType, ItemType::validWeapons())
        ) {
            $craftingType = 'weapon';
        }

        $gameSkill = GameSkill::where('name', ucfirst($craftingType).' Crafting')->first();

        return Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
    }

    /**
     * Return a list of items the player can craft for the type.
     *
     * @return Collection
     *
     * @throws Exception
     */
    protected function getItems(Character $character, Skill $skill, string|array $craftingType, bool $merchantMessage = true): SupportCollection
    {
        $twoHandedWeapons = [ItemType::BOW->value, ItemType::HAMMER->value, ItemType::STAVE->value];
        $craftingTypes = ['armour', 'ring', 'spell'];

        $items = Item::where('can_craft', true)
            ->where('skill_level_required', '<=', $skill->level)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->doesntHave('appliedHolyStacks')
            ->doesnthave('sockets')
            ->orderBy('skill_level_required', 'asc');

        $craftingTypeArray = is_array($craftingType) ? $craftingType : [$craftingType];

        if (! empty(array_intersect($craftingTypeArray, $twoHandedWeapons))) {
            $items->whereIn('default_position', array_map('strtolower', $craftingTypeArray));
        } elseif (! empty(array_intersect($craftingTypeArray, $craftingTypes))) {
            $items->whereIn('crafting_type', array_map('strtolower', $craftingTypeArray));
        } else {
            $items->whereIn('type', array_map('strtolower', $craftingTypeArray));
        }

        $items = $items->select('name', 'cost', 'type', 'id')->get();

        return $this->itemListCostTransformerService->reduceCostOfCraftingItems($character, $items, $merchantMessage);
    }

    /**
     * Handle picking up the item.
     *
     * @throws Exception
     */
    public function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false, bool $updateGoldCost = true): void
    {

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

            if (! $tooEasy) {
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
     * @throws Exception
     */
    private function handleCraftingForNpc(Character $character, Item $item, Skill $skill, bool $tooEasy, bool $updateGoldCost): bool
    {
        $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character, $item);

        if ($this->updateCraftingTasksForFactionLoyalty->handedOverItem()) {
            if (! $tooEasy) {
                $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);
            }

            if ($updateGoldCost) {
                $this->updateCharacterGold($character, $item);
            }

            event(new FactionLoyaltyUpdate($character->user, $this->factionLoyaltyService->getLoyaltyInfoForPlane($character)));

            return true;
        }

        return false;
    }

    /**
     * Handle craft for global events.
     *
     * @throws Exception
     */
    private function handleCraftingForEvent(Character $character, Item $item, Skill $skill, bool $tooEasy, bool $updateGoldCost): bool
    {
        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        if ($this->handleUpdatingCraftingGlobalEventGoal->handedOverItem()) {
            if (! $tooEasy) {
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
     */
    private function attemptToPickUpItem(Character $character, Item $item): bool
    {

        if ($this->craftForNpc) {
        }

        if (! $character->isInventoryFull()) {

            $slot = $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new UpdateCharacterInventoryCountEvent($character));

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name, $slot->id);

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::INVENTORY_IS_FULL);

        return false;
    }
}
