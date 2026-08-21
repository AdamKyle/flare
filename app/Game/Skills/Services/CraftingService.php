<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Npcs\Actions\QueenOfHearts\Services\RandomEnchantmentService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use App\Game\Skills\Transformers\CraftableItemTransformer;
use App\Game\Skills\Values\CraftingMessageMode;
use App\Game\Skills\Values\CraftingSkillGroup;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Builder;
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

    private ?int $lastCraftedInventorySlotId = null;

    /**
     * @param  RandomEnchantmentService  $randomEnchantmentService
     * @param  SkillService  $skillService
     * @param  ItemListCostTransformerService  $itemListCostTransformerService
     * @param  SkillCheckService  $skillCheckService
     * @param  UpdateCraftingTasksForFactionLoyalty  $updateCraftingTasksForFactionLoyalty
     * @param  HandleUpdatingCraftingGlobalEventGoal  $handleUpdatingCraftingGlobalEventGoal
     * @param  FactionLoyaltyService  $factionLoyaltyService
     * @param  Pagination  $pagination
     * @param  CraftableItemTransformer  $craftableItemTransformer
     */
    public function __construct(
        RandomEnchantmentService $randomEnchantmentService,
        SkillService $skillService,
        ItemListCostTransformerService $itemListCostTransformerService,
        SkillCheckService $skillCheckService,
        UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty,
        HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly Pagination $pagination,
        private readonly CraftableItemTransformer $craftableItemTransformer,
    ) {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->skillService = $skillService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
        $this->skillCheckService = $skillCheckService;
        $this->updateCraftingTasksForFactionLoyalty = $updateCraftingTasksForFactionLoyalty;
        $this->handleUpdatingCraftingGlobalEventGoal = $handleUpdatingCraftingGlobalEventGoal;
    }

    /**
     * Fetch all craftable items for a character matching the requested crafting type(s).
     *
     * @param  Character  $character  The character requesting craftable items.
     * @param  array  $params  The request params, including the requested crafting type(s).
     * @param  bool  $merchantMessage  Whether to send the Merchant cost-reduction server message.
     * @return Collection The craftable items available to the character.
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
     * Fetch paginated craftable items for a character, with optional search, armour subtype, and item type filtering.
     *
     * @param  Character  $character  The character requesting craftable items.
     * @param  array  $craftingParams  The request params, including the requested crafting type(s).
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $searchText  The optional search text to filter items by name, type, or crafting type.
     * @param  string  $armourSubtype  The optional armour subtype to filter items by.
     * @param  string  $itemType  The optional specific item type to filter items by.
     * @param  bool  $merchantMessage  Whether to send the Merchant cost-reduction server message.
     * @return array The paginated, transformed craftable items.
     */
    public function fetchPaginatedCraftableItems(
        Character $character,
        array $craftingParams,
        int $perPage,
        int $page,
        string $searchText = '',
        string $armourSubtype = '',
        string $itemType = '',
        bool $merchantMessage = true
    ): array {
        $craftingType = $craftingParams['crafting_type'];
        $defaultToWeapon = ItemType::validWeapons();

        if (
            (is_array($craftingType) && empty(array_diff($craftingType, $defaultToWeapon))) ||
            (! is_array($craftingType) && in_array($craftingType, $defaultToWeapon))
        ) {
            $craftingType = 'weapon';
        }

        $skill = $this->fetchCraftingSkill($character, $craftingType);

        $query = $this->buildCraftableItemsQuery($skill, $craftingParams['crafting_type']);

        if ($searchText !== '') {
            $query->where(function ($subQuery) use ($searchText) {
                $subQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('type', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('crafting_type', 'LIKE', '%'.$searchText.'%');
            });
        }

        if ($armourSubtype !== '') {
            $query->where('type', $armourSubtype);
        }

        if ($itemType !== '') {
            $query->where('type', $itemType);
        }

        $paginator = $query->orderBy('skill_level_required')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $this->itemListCostTransformerService->reduceCostOfCraftingItems($character, $paginator->getCollection(), $merchantMessage)
        );

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->craftableItemTransformer);
    }

    /**
     * Return the character's current crafting XP progress for the requested crafting type.
     *
     * @param  Character  $character  The character requesting crafting XP.
     * @param  string|array  $type  The requested crafting type(s).
     * @return array{current_xp: int, next_level_xp: int, skill_name: string, level: int} The crafting XP progress.
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

    /**
     * Return the character's current and maximum Inventory counts.
     *
     * @param  Character  $character  The character being checked.
     * @return array{current_count: int, max_inventory: int} The current and maximum Inventory counts.
     */
    public function getInventoryCount(Character $character): array
    {
        return [
            'current_count' => $character->getInventoryCount(),
            'max_inventory' => $character->inventory_max,
        ];
    }

    /**
     * Return the character's current and maximum Alchemy Bag counts.
     *
     * @param  Character  $character  The character being checked.
     * @return array{current_count: int, max_inventory: int} The current and maximum Alchemy Bag counts.
     */
    public function getAlchemyBagCount(Character $character): array
    {
        return [
            'current_count' => $character->getAlchemyBagCount(),
            'max_inventory' => $character->alchemy_bag_limit,
        ];
    }

    /**
     * Return the character's current and maximum Gem Bag counts.
     *
     * @param  Character  $character  The character being checked.
     * @return array{current_count: int, max_inventory: int} The current and maximum Gem Bag counts.
     */
    public function getGemBagCount(Character $character): array
    {
        return [
            'current_count' => $character->getGemBagCount(),
            'max_inventory' => $character->gem_bag_limit,
        ];
    }

    /**
     * Resolve the highest currently craftable item for each requested item type, in one query.
     *
     * Used by Craft Set recommendation to default every required position to the best
     * craftable equipment item for its discipline without querying once per item type.
     *
     * @param  Skill  $skill  The character's already-resolved Crafting skill for the group.
     * @param  string  $craftingType  The Crafting group used to query craftable items.
     * @param  array<int, string>  $itemTypes  The requested item types to resolve.
     * @return Collection<string, Item> The highest craftable item for each resolved item type, keyed by item type.
     */
    public function fetchBestCraftableItemsByTypeForAutomation(Skill $skill, string $craftingType, array $itemTypes): Collection
    {
        $items = $this->buildCraftableItemsQuery($skill, $craftingType)
            ->whereIn('type', $itemTypes)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->get();

        return $items->groupBy('type')->map(fn (Collection $itemsForType): Item => $itemsForType->first());
    }

    /**
     * Resolve the highest currently craftable weapon for one explicitly requested weapon type.
     *
     * Used by Craft Set hand recommendation to default a selected hand weapon type to the
     * character's best currently craftable candidate for that type.
     *
     * @param  Character  $character  The character requesting the recommendation.
     * @param  string  $weaponType  The requested weapon type.
     * @return Item|null The highest craftable candidate, or null when the character has no Weapon Crafting skill or nothing is craftable.
     */
    public function findBestCraftableWeaponForAutomation(Character $character, string $weaponType): ?Item
    {
        $skill = $this->getCraftingSkillForAutomation($character, $weaponType);

        if (is_null($skill)) {
            return null;
        }

        return $this->baseCraftableItemsQuery($skill)
            ->where(function (Builder $query) use ($weaponType): void {
                $query->where('type', $weaponType)
                    ->orWhere('default_position', $weaponType);
            })
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();
    }

    /**
     * Resolve the highest currently craftable Shield for the character.
     *
     * Used by Craft Set hand recommendation to default a selected Shield hand type to the
     * character's best currently craftable Shield through the Armour Crafting discipline.
     *
     * @param  Character  $character  The character requesting the recommendation.
     * @return Item|null The highest craftable Shield, or null when the character has no Armour Crafting skill or nothing is craftable.
     */
    public function findBestCraftableShieldForAutomation(Character $character): ?Item
    {
        $skill = $this->getCraftingSkillForAutomation($character, 'armour');

        if (is_null($skill)) {
            return null;
        }

        return $this->baseCraftableItemsQuery($skill)
            ->where('crafting_type', 'armour')
            ->where('type', 'shield')
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();
    }

    /**
     * Attempt to craft and pick up the requested item for the character.
     *
     * Gold is only taken from a player if they can pick up the item they crafted or
     * if they fail to craft the item.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  array  $params  The request params, including the item to craft and crafting type.
     * @return bool True when the item was successfully crafted.
     */
    public function craft(Character $character, array $params): bool
    {
        $this->lastCraftedInventorySlotId = null;

        $this->craftForNpc = $params['craft_for_npc'];

        $this->craftForEvent = $params['craft_for_event'];

        $item = Item::find($params['item_to_craft']);

        $skill = $this->fetchCraftingSkill($character, $params['type']);

        if (is_null($item)) {
            event(new ServerMessageEvent($character->user, 'Invalid Item'));

            return false;
        }

        if (! ($params['skip_crafting_timeout'] ?? false)) {
            $this->handleCraftingTimeOut($character, $item);
        }

        $cost = $this->getItemCost($character, $item);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_GOLD);

            return false;
        }

        return $this->attemptToCraftItem($character, $skill, $item);
    }

    /**
     * Return the inventory slot id created by the most recent successful craft, if any.
     *
     * @return int|null The last crafted inventory slot id, or null when nothing was picked up.
     */
    public function getLastCraftedInventorySlotId(): ?int
    {
        return $this->lastCraftedInventorySlotId;
    }

    /**
     * Craft an item directly for Batch Crafting, with no InventorySlot involved.
     *
     * Uses the same skill requirement, gold affordability, and success/failure roll
     * rules as craft(), charging gold and assigning crafting XP exactly like craft()
     * does on a successful roll. Never picks up the item into inventory. When a
     * destination creator is supplied and it cannot accept the crafted item, the
     * attempt is reported back as a normal failure with reason "destination_failed"
     * rather than throwing.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Item  $item  The item to craft.
     * @param  string  $craftingType  The crafting type used to craft the item.
     * @param  CraftingMessageMode  $messageMode  The server-message mode controlling which messages are sent.
     * @param  callable|null  $destinationCreator  The optional callback that places the crafted item and returns its destination.
     * @return array{success: bool, item: Item|null, reason: string|null, xp_gained: int, destination: array|null} The outcome of the craft attempt.
     */
    public function craftForBatch(
        Character $character,
        Item $item,
        string $craftingType,
        CraftingMessageMode $messageMode = CraftingMessageMode::STANDARD,
        ?callable $destinationCreator = null,
    ): array {
        $skill = $this->fetchCraftingSkill($character, $craftingType);

        $cost = $this->getItemCost($character, $item);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_GOLD);

            return ['success' => false, 'item' => null, 'reason' => 'not_enough_gold', 'xp_gained' => 0, 'destination' => null];
        }

        $result = $this->attemptToCraftItemForBatch($character, $skill, $item, $messageMode);

        if (! $result['success'] || is_null($destinationCreator)) {
            return $result + ['destination' => null];
        }

        $destination = $destinationCreator($result['item']);

        if (! is_array($destination)) {
            return ['success' => false, 'item' => null, 'reason' => 'destination_failed', 'xp_gained' => 0, 'destination' => null];
        }

        return $result + ['destination' => $destination];
    }

    /**
     * Attempt to craft an item for Batch Crafting without picking it up into inventory.
     * Mirrors attemptToCraftItem()'s manual Server Messages so batch crafting reads
     * the same as manual crafting in the player's chat/Server Message feed.
     *
     * When $messageMode is BATCH_CRAFTING, the "too easy" and "You crafted a: X!"
     * messages are suppressed because the batch handler sends one canonical outcome
     * message instead. Failure messages are never suppressed.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Skill  $skill  The character's crafting skill for the item's crafting type.
     * @param  Item  $item  The item to craft.
     * @param  CraftingMessageMode  $messageMode  The server-message mode controlling which messages are sent.
     * @return array{success: bool, item: Item|null, reason: string|null, xp_gained: int} The outcome of the craft attempt.
     */
    private function attemptToCraftItemForBatch(Character $character, Skill $skill, Item $item, CraftingMessageMode $messageMode = CraftingMessageMode::STANDARD): array
    {
        $suppressRoutineMessages = $messageMode === CraftingMessageMode::BATCH_CRAFTING;

        if ($skill->level < $item->skill_level_required) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            return ['success' => false, 'item' => null, 'reason' => 'skill_too_low', 'xp_gained' => 0];
        }

        if ($skill->level > $item->skill_level_trivial) {
            if (! $suppressRoutineMessages) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);
            }

            $this->updateCharacterGold($character, $item);

            if (! $suppressRoutineMessages) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
            }

            return ['success' => true, 'item' => $item, 'reason' => null, 'xp_gained' => 0];
        }

        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck = $this->skillCheckService->getDCCheck($skill, 0);

        if ($dcCheck < $characterRoll) {
            $xpGained = $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

            $this->updateCharacterGold($character, $item);

            if (! $suppressRoutineMessages) {
                ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name);
            }

            return ['success' => true, 'item' => $item, 'reason' => null, 'xp_gained' => $xpGained];
        }

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::FAILED_TO_CRAFT);

        $this->updateCharacterGold($character, $item);

        return ['success' => false, 'item' => null, 'reason' => 'failed_roll', 'xp_gained' => 0];
    }

    /**
     * Return the character's crafting skill for the requested crafting type, for automation use.
     *
     * @param  Character  $character  The character requesting the skill.
     * @param  string  $craftingType  The requested crafting type.
     * @return Skill|null The character's crafting skill, or null when the crafting type has no matching skill.
     */
    public function getCraftingSkillForAutomation(Character $character, string $craftingType): ?Skill
    {
        if (in_array($craftingType, ItemType::validWeapons())) {
            $craftingType = 'weapon';
        }

        $gameSkill = GameSkill::where('name', ucfirst($craftingType).' Crafting')->first();

        if (is_null($gameSkill)) {
            return null;
        }

        return Skill::where('game_skill_id', $gameSkill->id)
            ->where('character_id', $character->id)
            ->first();
    }

    /**
     * Resolve the character's Crafting skills for the requested Crafting skill groups in one operation.
     *
     * Used when a caller needs more than one Crafting skill group (for example, walking the
     * Experience cycle across all four disciplines) so the same skills are not re-queried once
     * per group encountered.
     *
     * @param  Character  $character  The character requesting the skills.
     * @param  array<int, CraftingSkillGroup>  $craftingGroups  The requested Crafting skill groups.
     * @return SupportCollection<string, Skill|null> The resolved skills keyed by their requested Crafting skill group value.
     */
    public function resolveCraftingSkillsForGroups(Character $character, array $craftingGroups): SupportCollection
    {
        $gameSkillNames = array_map(fn (CraftingSkillGroup $group): string => $group->skillName(), $craftingGroups);

        $gameSkillsByName = GameSkill::whereIn('name', $gameSkillNames)->get()->keyBy('name');

        $skillsByGameSkillId = Skill::where('character_id', $character->id)
            ->whereIn('game_skill_id', $gameSkillsByName->pluck('id'))
            ->get()
            ->keyBy('game_skill_id');

        return collect($craftingGroups)->mapWithKeys(function (CraftingSkillGroup $group) use ($gameSkillsByName, $skillsByGameSkillId): array {
            $gameSkill = $gameSkillsByName->get($group->skillName());

            return [$group->value => is_null($gameSkill) ? null : $skillsByGameSkillId->get($gameSkill->id)];
        });
    }

    /**
     * Return the Gold cost to craft the item for the character, for automation use.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Item  $item  The item being crafted.
     * @return int The Gold cost to craft the item.
     */
    public function getItemCostForAutomation(Character $character, Item $item): int
    {
        return $this->getItemCost($character, $item);
    }

    /**
     * Fetch every currently craftable, non-trivial candidate item for one Crafting skill group.
     *
     * Executes exactly one query per call so a caller resolving multiple Experience cycle
     * targets within the same discipline can preload candidates once instead of querying
     * per target.
     *
     * @param  Skill  $skill  The character's already-resolved Crafting skill for the group.
     * @param  CraftingSkillGroup  $group  The Crafting skill group being queried.
     * @return Collection The non-trivial craftable candidate items for the group.
     */
    public function fetchMeaningfulExperienceCandidates(Skill $skill, CraftingSkillGroup $group): Collection
    {
        $query = $this->baseCraftableItemsQuery($skill)
            ->where('skill_level_trivial', '>=', $skill->level);

        match ($group) {
            CraftingSkillGroup::WEAPON => $query->where(function (Builder $subQuery): void {
                $subQuery->whereIn('type', ItemType::validWeapons())
                    ->orWhereIn('default_position', ItemType::validWeapons());
            }),
            CraftingSkillGroup::ARMOUR => $query->where('crafting_type', 'armour'),
            CraftingSkillGroup::RING => $query->where('crafting_type', 'ring'),
            CraftingSkillGroup::SPELL => $query->where('crafting_type', 'spell'),
        };

        return $query->orderByDesc('skill_level_required')->orderBy('id')->get();
    }

    /**
     * Resolve the cheapest currently craftable item for the target, for low-cost automation crafting.
     *
     * Orders by cost first, with a stable secondary order by item id, so a tie between equally
     * cheap candidates always resolves to the same Item for the same database state.
     *
     * @param  Skill  $skill  The character's already-resolved crafting skill for the target's discipline.
     * @param  string|array  $craftingType  The crafting type group used to query craftable items.
     * @param  string|null  $itemType  The optional specific item type narrowing the target within the group.
     * @return Item|null The cheapest currently craftable item, or null when no candidate exists.
     */
    public function findInexpensiveCraftableItem(Skill $skill, string|array $craftingType, ?string $itemType = null): ?Item
    {
        $query = $this->buildCraftableItemsQuery($skill, $craftingType);

        if (! is_null($itemType)) {
            $query->where('type', $itemType);
        }

        return $query->orderBy('cost')->orderBy('id')->first();
    }

    /**
     * Determine whether the given Crafting skill has reached its maximum level.
     *
     * @param  Skill  $skill  The Crafting skill being checked.
     * @return bool True when the skill is at or above its maximum level.
     */
    public function isSkillMaxed(Skill $skill): bool
    {
        return $skill->level >= $skill->max_level;
    }

    /**
     * Resolve one exact item by id, but only when the character can currently craft it.
     *
     * Applies the same craftability constraints used by the normal Crafting query path
     * (skill level, prefix/suffix, holy stacks, sockets) scoped to the requested item id.
     *
     * @param  Character  $character  The character requesting the item.
     * @param  int  $itemId  The exact item id to resolve.
     * @return Item|null The exact item when it exists and is currently craftable, otherwise null.
     */
    public function findCraftableItemForAutomation(Character $character, int $itemId): ?Item
    {
        $item = Item::find($itemId);

        if (is_null($item)) {
            return null;
        }

        $craftingType = in_array($item->type, ItemType::validWeapons(), true) ? $item->type : $item->crafting_type;

        $skill = $this->getCraftingSkillForAutomation($character, $craftingType);

        if (is_null($skill)) {
            return null;
        }

        return $this->buildCraftableItemsQuery($skill, $craftingType)
            ->where('id', $itemId)
            ->first();
    }

    /**
     * Apply the character's class-specific crafting timeout adjustment for the item.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Item  $item  The item being crafted.
     * @return void This method does not return a value.
     */
    protected function handleCraftingTimeOut(Character $character, Item $item): void
    {
        $craftingTimeOut = null;

        if (
            $character->classType()->isBlacksmith() &&
            (in_array($item->type, [ItemType::WEAPON->value, ItemType::STAVE->value, ItemType::HAMMER->value, ItemType::BOW->value, ItemType::GUN->value, ItemType::MACE->value, ItemType::FAN->value, ItemType::SCRATCH_AWL->value, ItemType::RING->value, ItemType::SWORD->value, ItemType::CENSOR->value, ItemType::CLAW->value, ItemType::WAND->value], true) ||
                ArmourType::tryFrom($item->type) !== null)
        ) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Blacksmith, your crafting timeout is reduced by 25% for weapons (including rings) and armour.');

            $craftingTimeOut = ceil(10 - 10 * 0.25);
        }

        if ($character->classType()->isBlacksmith() && in_array($item->type, [ItemType::SPELL_HEALING->value, ItemType::SPELL_DAMAGE->value], true)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Blacksmith, your crafting timeout is increased by 25% for spell crafting.');

            $craftingTimeOut = ceil(10 + 10 * 0.25);
        }

        if ($character->classType()->isArcaneAlchemist() && in_array($item->type, [ItemType::SPELL_HEALING->value, ItemType::SPELL_DAMAGE->value], true)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Arcane Alchemist, your crafting timeout is reduced by 15% for spell crafting.');

            $craftingTimeOut = ceil(10 - 10 * 0.15);
        }

        event(new CraftedItemTimeOutEvent($character, null, $craftingTimeOut));
    }

    /**
     * Return the character's class-adjusted Gold cost to craft the item.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Item  $item  The item being crafted.
     * @return int The class-adjusted Gold cost.
     */
    protected function getItemCost(Character $character, Item $item): int
    {
        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.30);
        }

        if ($character->classType()->isBlacksmith() && (in_array($item->type, [ItemType::WEAPON->value, ItemType::STAVE->value, ItemType::HAMMER->value, ItemType::BOW->value, ItemType::GUN->value, ItemType::MACE->value, ItemType::FAN->value, ItemType::SCRATCH_AWL->value, ItemType::RING->value, ItemType::SWORD->value, ItemType::CENSOR->value, ItemType::CLAW->value, ItemType::WAND->value], true) || ArmourType::tryFrom($item->type) !== null
        )) {
            $cost = floor($cost - $cost * 0.25);
        }

        if ($character->classType()->isArcaneAlchemist() && (in_array($item->type, [ItemType::SPELL_HEALING->value, ItemType::SPELL_DAMAGE->value], true))) {
            $cost = floor($cost - $cost * 0.15);
        }

        return $cost;
    }

    /**
     * Attempt to craft and pick up the item, applying skill checks, gold cost, and XP.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Skill  $skill  The character's crafting skill for the item's crafting type.
     * @param  Item  $item  The item to craft.
     * @return bool True when the item was successfully crafted and picked up.
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
     * Fetch the character's crafting skill for the requested crafting type.
     *
     * @param  Character  $character  The character requesting the skill.
     * @param  string  $craftingType  The requested crafting type.
     * @return Skill The character's crafting skill.
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
     * Return a list of items the player can craft for the requested crafting type(s), cost-reduced.
     *
     * @param  Character  $character  The character requesting craftable items.
     * @param  Skill  $skill  The character's crafting skill for the requested crafting type(s).
     * @param  string|array  $craftingType  The requested crafting type(s).
     * @param  bool  $merchantMessage  Whether to send the Merchant cost-reduction server message.
     * @return SupportCollection The cost-reduced craftable items.
     */
    protected function getItems(Character $character, Skill $skill, string|array $craftingType, bool $merchantMessage = true): SupportCollection
    {
        $items = $this->buildCraftableItemsQuery($skill, $craftingType)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        return $this->itemListCostTransformerService->reduceCostOfCraftingItems($character, $items, $merchantMessage);
    }

    /**
     * Resolve the cheapest currently craftable weapon across every weapon subtype, in one query.
     *
     * Orders by cost first, with a stable secondary order by item id, so a tie between equally
     * cheap weapons always resolves to the same Item for the same database state.
     *
     * @param  Skill  $skill  The character's already-resolved weapon crafting skill.
     * @return Item|null The cheapest currently craftable weapon, or null when nothing is craftable.
     */
    public function findInexpensiveCraftableWeaponForAutomation(Skill $skill): ?Item
    {
        return $this->baseCraftableItemsQuery($skill)
            ->whereIn('type', ItemType::validWeapons())
            ->orderBy('cost')
            ->orderBy('id')
            ->first();
    }

    /**
     * Build the shared eligible-craftable-items base query constraints for a character's skill.
     *
     * @param  Skill  $skill  The character's crafting skill.
     * @return Builder The base eligible-craftable-items query.
     */
    private function baseCraftableItemsQuery(Skill $skill): Builder
    {
        return Item::with(['itemPrefix', 'itemSuffix', 'appliedHolyStacks', 'itemSkillProgressions'])
            ->where('can_craft', true)
            ->where('skill_level_required', '<=', $skill->level)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets');
    }

    /**
     * Build the base eligible-craftable-items query for a character's skill and requested crafting type(s).
     *
     * @param  Skill  $skill  The character's crafting skill for the requested crafting type(s).
     * @param  string|array  $craftingType  The requested crafting type(s).
     * @return Builder The base eligible-craftable-items query.
     */
    private function buildCraftableItemsQuery(Skill $skill, string|array $craftingType): Builder
    {
        $twoHandedWeapons = [ItemType::BOW->value, ItemType::HAMMER->value, ItemType::STAVE->value];
        $craftingTypes = ['armour', 'ring', 'spell'];

        $query = $this->baseCraftableItemsQuery($skill);

        $craftingTypeArray = is_array($craftingType) ? $craftingType : [$craftingType];

        if (! empty(array_intersect($craftingTypeArray, $twoHandedWeapons))) {
            $query->whereIn('default_position', array_map('strtolower', $craftingTypeArray));

            return $query;
        }

        if (! empty(array_intersect($craftingTypeArray, $craftingTypes))) {
            $query->whereIn('crafting_type', array_map('strtolower', $craftingTypeArray));

            return $query;
        }

        $query->whereIn('type', array_map('strtolower', $craftingTypeArray));

        return $query;
    }

    /**
     * Handle picking up the crafted item into inventory, NPC handoff, or event handoff.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  Skill  $skill  The character's crafting skill for the item's crafting type.
     * @param  bool  $tooEasy  Whether the craft was trivial and should not award XP.
     * @param  bool  $updateGoldCost  Whether to charge the character's Gold for the craft.
     * @return void This method does not return a value.
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
     * Hand the crafted item over to an active NPC faction loyalty crafting task, when one exists.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  Skill  $skill  The character's crafting skill for the item's crafting type.
     * @param  bool  $tooEasy  Whether the craft was trivial and should not award XP.
     * @param  bool  $updateGoldCost  Whether to charge the character's Gold for the craft.
     * @return bool True when the item was handed over to an NPC faction loyalty task.
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
     * Hand the crafted item over to an active global event crafting goal, when one exists.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  Skill  $skill  The character's crafting skill for the item's crafting type.
     * @param  bool  $tooEasy  Whether the craft was trivial and should not award XP.
     * @param  bool  $updateGoldCost  Whether to charge the character's Gold for the craft.
     * @return bool True when the item was handed over to a global event crafting goal.
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
     * Attempt to pick up the crafted item into the character's inventory.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @return bool True when the item was picked up into inventory.
     */
    private function attemptToPickUpItem(Character $character, Item $item): bool
    {
        $this->lastCraftedInventorySlotId = null;

        if (! $character->isInventoryFull()) {
            $slot = $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            $this->lastCraftedInventorySlotId = $slot->id;

            event(new UpdateCharacterInventoryCountEvent($character));

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED, $item->name, $slot->id);

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::INVENTORY_IS_FULL);

        return false;
    }
}
