<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use App\Game\Skills\Transformers\AlchemyItemTransformer;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class AlchemyService
{
    use ResponseBuilder, UpdateCharacterCurrency;

    private SkillCheckService $skillCheckService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    private Pagination $pagination;

    public function __construct(
        SkillCheckService $skillCheckService,
        ItemListCostTransformerService $itemListCostTransformerService,
        Pagination $pagination,
        private readonly AlchemyItemTransformer $alchemyItemTransformer,
        private readonly UsableItemTransformer $usableItemTransformer,
    ) {
        $this->skillCheckService = $skillCheckService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
        $this->pagination = $pagination;
    }

    /**
     * Fetches a paginated, searchable list of Alchemy items eligible for crafting.
     */
    public function fetchPaginatedAlchemistItems(Character $character, int $perPage, int $page, string $search = ''): array
    {
        $skill = $this->fetchAlchemySkill($character);

        $query = $this->buildAlchemyItemsQuery($skill);

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        $paginator = $query->orderBy('skill_level_required', 'asc')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $this->attachOwnedAmounts($character, $this->itemListCostTransformerService->reduceCostOfAlchemyItems($character, $paginator->getCollection(), false))
        );

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->alchemyItemTransformer);
    }

    /**
     * Fetch every alchemy item currently craftable by the character.
     *
     * @param Character $character The character requesting alchemy items.
     * @param bool $showMerchantMessage Whether to send the Merchant cost-reduction server message.
     * @return SupportCollection The craftable alchemy items.
     */
    public function fetchAlchemistItems(Character $character, bool $showMerchantMessage = true)
    {
        $skill = $this->fetchAlchemySkill($character);

        $items = $this->buildAlchemyItemsQuery($skill)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        $items = $this->itemListCostTransformerService->reduceCostOfAlchemyItems($character, $items, $showMerchantMessage);

        return $this->attachOwnedAmounts($character, $items);
    }

    /**
     * Resolve the character's Alchemy skill, assuming it exists.
     *
     * @param Character $character The character being checked.
     * @return Skill The character's Alchemy skill.
     */
    private function fetchAlchemySkill(Character $character): Skill
    {
        return $this->findAlchemySkill($character);
    }

    /**
     * Resolve the character's Alchemy skill, tolerating its absence.
     *
     * @param Character $character The character being checked.
     * @return Skill|null The character's Alchemy skill, or null when it does not exist.
     */
    public function findAlchemySkill(Character $character): ?Skill
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        if (is_null($gameSkill)) {
            return null;
        }

        return Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
    }

    /**
     * Find the character's highest-requirement Alchemy item that still meaningfully grants XP.
     *
     * @param Character $character The character requesting the target.
     * @return Item|null The resolved meaningful item, or null when none currently applies.
     */
    public function findMeaningfulBatchItem(Character $character): ?Item
    {
        $skill = $this->findAlchemySkill($character);

        if (is_null($skill) || $skill->level >= $skill->max_level) {
            return null;
        }

        return $this->buildAlchemyItemsQuery($skill)
            ->where('skill_level_trivial', '>=', $skill->level)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();
    }

    /**
     * Resolve the character's class-adjusted Gold Dust/Shards cost to transmute the item.
     *
     * @param Character $character The character transmuting the item.
     * @param Item $item The Alchemy item being transmuted.
     * @return array{gold_dust: int, shards: int} The class-adjusted currency cost.
     */
    public function resolveCost(Character $character, Item $item): array
    {
        $goldDustCost = $item->gold_dust_cost;
        $shardsCost = $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost = floor($shardsCost - $shardsCost * 0.10);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.15);
            $shardsCost = floor($shardsCost - $shardsCost * 0.15);
        }

        return ['gold_dust' => $goldDustCost, 'shards' => $shardsCost];
    }

    /**
     * Build the base eligible-alchemy-items query for the character's Alchemy skill.
     *
     * @param Skill $skill The character's Alchemy skill.
     * @return Builder The base eligible-alchemy-items query.
     */
    private function buildAlchemyItemsQuery(Skill $skill): Builder
    {
        return Item::where('can_craft', true)
            ->where('crafting_type', 'alchemy')
            ->where('skill_level_required', '<=', $skill->level)
            ->where('item_prefix_id', null)
            ->where('item_suffix_id', null);
    }

    /**
     * Attach the character's currently owned Alchemy Bag amount to each candidate item.
     *
     * @param Character $character The character requesting owned amounts.
     * @param SupportCollection $items The candidate alchemy items.
     * @return SupportCollection The items with owned amounts attached.
     */
    private function attachOwnedAmounts(Character $character, SupportCollection $items): SupportCollection
    {
        $alchemyBag = $character->alchemyBag;
        $ownedAmounts = [];

        if (! is_null($alchemyBag)) {
            $ownedAmounts = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
                ->pluck('amount', 'item_id')
                ->toArray();
        }

        return $items->map(function ($item) use ($ownedAmounts) {
            $item->owned_amount = $ownedAmounts[$item->id] ?? 0;

            return $item;
        });
    }

    /**
     * Return the character's current Alchemy skill XP progress.
     *
     * @param Character $character The character requesting Alchemy XP.
     * @return array The Alchemy XP progress.
     */
    public function fetchSkillXP(Character $character): array
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        $skill = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    /**
     * $isBatch suppresses the manual Crafting Timeout bar event: Batch Crafting
     * has its own runtime/progress timers and processes many items per run, so
     * firing the manual per-action timeout here would incorrectly show that bar
     * during batch processing.
     *
     * $bypassBagCapacity is batch-only: it lets a batch disposition that will
     * immediately destroy/list/use the produced item (never retain it) proceed
     * even when the Alchemy Bag is full, since the item never actually needs to
     * occupy a retained bag slot. Manual (non-batch) calls never pass this.
     */
    public function transmute(Character $character, int $itemId, bool $isBatch = false, bool $bypassBagCapacity = false): ?array
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();
        $skill = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
        $item = Item::find($itemId);

        if (is_null($item)) {
            event(new ServerMessageEvent($character->user, 'Nope. Item does not exist.'));

            return null;
        }

        $setTime = null;

        if ($character->classType()->isArcaneAlchemist() && $item->crafting_type === 'alchemy') {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Arcane Alchemist, your crafting timeout for Alchemy items, is reduced by 15%.');

            $setTime = floor(10 - 10 * 0.15);
        }

        if (! $isBatch) {
            event(new CraftedItemTimeOutEvent($character, null, $setTime));
        }

        $cost = $this->resolveCost($character, $item);

        if ($cost['gold_dust'] > $character->gold_dust) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_GOLD_DUST);

            return null;
        }

        if ($cost['shards'] > $character->shards) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_SHARDS);

            return null;
        }

        return $this->attemptTransmute($character, $skill, $item, $bypassBagCapacity);
    }

    /**
     * Attempt the transmute skill check and roll for the requested item.
     *
     * @param Character $character The character transmuting the item.
     * @param Skill $skill The character's Alchemy skill.
     * @param Item $item The item being transmuted.
     * @param bool $bypassBagCapacity Whether to bypass the Alchemy Bag capacity check.
     * @return array|null The transmute outcome, or null when the attempt could not proceed.
     */
    private function attemptTransmute(Character $character, Skill $skill, Item $item, bool $bypassBagCapacity = false): ?array
    {
        $this->updateAlchemyCost($character, $item);

        if ($skill->level < $item->skill_level_required) {

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            $result = $this->pickUpItem($character, $item, $skill, true, $bypassBagCapacity);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return $result;
        }

        if ($skill->level > $item->skill_level_trivial) {

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $result = $this->pickUpItem($character, $item, $skill, true, $bypassBagCapacity);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return $result;
        }

        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck = $this->skillCheckService->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $result = $this->pickUpItem($character, $item, $skill, false, $bypassBagCapacity);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return $result;
        }

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::FAILED_TO_TRANSMUTE);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return null;
    }

    /**
     * Place the successfully transmuted item into the character's Alchemy Bag and award XP.
     *
     * @param Character $character The character transmuting the item.
     * @param Item $item The item being placed.
     * @param Skill $skill The character's Alchemy skill.
     * @param bool $tooEasy Whether the transmute was trivial and should not award XP.
     * @param bool $bypassBagCapacity Whether to bypass the Alchemy Bag capacity check.
     * @return array|null The resulting Alchemy Bag slot facts, or null when placement failed.
     */
    private function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false, bool $bypassBagCapacity = false): ?array
    {
        $alchemyBagSlot = $this->attemptToPickUpItem($character, $item, $bypassBagCapacity);

        if (is_null($alchemyBagSlot)) {
            return null;
        }

        if (! $tooEasy) {
            event(new UpdateSkillEvent($skill));
        }

        $itemPreview = array_merge($this->usableItemTransformer->transform($item), [
            'id' => $alchemyBagSlot->id,
            'item_id' => $item->id,
            'slot_id' => $alchemyBagSlot->id,
            'amount' => $alchemyBagSlot->amount,
        ]);

        return [
            'item_id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'amount_created' => 1,
            'current_amount' => $alchemyBagSlot->amount,
            'slot_id' => $alchemyBagSlot->id,
            'item_preview' => $itemPreview,
        ];
    }

    /**
     * Add the transmuted item to an existing or new Alchemy Bag slot, respecting bag capacity.
     *
     * @param Character $character The character transmuting the item.
     * @param Item $item The item being added.
     * @param bool $bypassBagCapacity Whether to bypass the Alchemy Bag capacity check.
     * @return AlchemyBagSlot|null The resulting Alchemy Bag slot, or null when the bag is full.
     */
    private function attemptToPickUpItem(Character $character, Item $item, bool $bypassBagCapacity = false): ?AlchemyBagSlot
    {
        if (! $bypassBagCapacity && ! $character->canAddToAlchemyBag(1)) {
            event(new ServerMessageEvent($character->user, 'Your Alchemy Bag is full. Use or remove alchemy items before crafting more.'));

            return null;
        }

        $alchemyBag = AlchemyBag::firstOrCreate(['character_id' => $character->id]);

        $existingSlot = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
            ->where('item_id', $item->id)
            ->first();

        if (! is_null($existingSlot)) {
            $existingSlot->update(['amount' => $existingSlot->amount + 1]);
            $alchemyBagSlot = $existingSlot->refresh();

        } else {
            $alchemyBagSlot = AlchemyBagSlot::create([
                'alchemy_bag_id' => $alchemyBag->id,
                'character_id' => $character->id,
                'item_id' => $item->id,
                'amount' => 1,
            ]);
        }

        event(new ServerMessageEvent(
            $character->user,
            'You manage to create: '.$item->name.' from gold dust!',
            $alchemyBagSlot->id,
            'alchemy_bag',
            $item->id,
            $item->name,
        ));

        return $alchemyBagSlot;
    }
}
