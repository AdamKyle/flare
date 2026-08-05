<?php

namespace App\Game\Npcs\Actions\QueenOfHearts\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Npcs\Actions\QueenOfHearts\Transformers\QueenInventorySlotTransformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QueenOfHeartsService
{
    use ResponseBuilder;

    private RandomEnchantmentService $randomEnchantmentService;

    private ReRollEnchantmentService $reRollEnchantmentService;

    private Pagination $pagination;

    public function __construct(
        RandomEnchantmentService $randomEnchantmentService,
        ReRollEnchantmentService $reRollEnchantmentService,
        Pagination $pagination,
        private readonly QueenInventorySlotTransformer $queenInventorySlotTransformer,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
    ) {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->reRollEnchantmentService = $reRollEnchantmentService;
        $this->pagination = $pagination;
    }

    /**
     * Fetches a paginated, searchable list of eligible unique source items.
     */
    public function fetchPaginatedUniqueItems(Character $character, int $perPage, int $page, string $search = ''): array
    {
        $query = $this->randomEnchantmentService->buildUniqueInventoryQuery($character);

        $this->applyItemSearch($query, $search);

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->queenInventorySlotTransformer);
    }

    /**
     * Fetches a paginated, searchable list of eligible Queen movement destination items.
     *
     * Uses the same authoritative unique and non-unique eligibility rules used to build
     * the Queen summary response, excludes the given source slot, and requires the source
     * slot to belong to the requested character.
     */
    public function fetchPaginatedDestinationItems(Character $character, int $sourceSlotId, int $perPage, int $page, string $search = ''): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        $sourceSlot = InventorySlot::where('inventory_id', $inventory->id)->where('id', $sourceSlotId)->first();

        if (is_null($sourceSlot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        $query = $this->randomEnchantmentService->buildDestinationInventoryQuery($character)
            ->where('id', '!=', $sourceSlotId);

        $this->applyItemSearch($query, $search);

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->successResult(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->queenInventorySlotTransformer)
        );
    }

    /**
     * Applies a name/prefix/suffix search to an item-bearing inventory-slot query.
     */
    private function applyItemSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->whereHas('item', function ($itemQuery) use ($search) {
            $itemQuery->where('name', 'LIKE', '%'.$search.'%')
                ->orWhereHas('itemPrefix', fn ($prefixQuery) => $prefixQuery->where('name', 'LIKE', '%'.$search.'%'))
                ->orWhereHas('itemSuffix', fn ($suffixQuery) => $suffixQuery->where('name', 'LIKE', '%'.$search.'%'));
        });
    }

    /**
     * Build the authoritative Queen of Hearts response.
     */
    public function buildQueenResponse(Character $character): array
    {
        $uniqueSlots = $this->randomEnchantmentService->fetchUniquesFromCharactersInventory($character);
        $nonUniqueSlots = $this->randomEnchantmentService->fetchNonUniqueItems($character);

        return [
            'unique_slots' => $uniqueSlots->map(fn ($slot) => $this->queenInventorySlotTransformer->transform($slot))->values(),
            'non_unique_slots' => $nonUniqueSlots->map(fn ($slot) => $this->queenInventorySlotTransformer->transform($slot))->values(),
            'costs' => $this->buildCosts($uniqueSlots),
        ];
    }

    /**
     * Re roll Unique.
     */
    public function reRollUnique(Character $character, int $selectedSlotId, string $selectedReRollType, string $selectedAffix): array
    {

        if (! $character->map->gameMap->mapType()->isHell()) {
            event(new GlobalMessageEvent('The Queen of Hell is not happy that '.$character->name.' tried to talk to her while not in hell. "Hmmmp child, I do not like you right now!" As she pouts'));

            $item = Item::where('type', 'quest')->where('effect', ItemEffectType::QUEEN_OF_HEARTS->value)->first();

            return $this->errorResult('You need to be in Hell to access The Queen of Hearts and have the quest item: '.$item->affix_name.'.');
        }

        $slot = $character->inventory->slots->filter(function ($slot) use ($selectedSlotId) {
            return $slot->id === $selectedSlotId;
        })->first();

        if (is_null($slot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        if (! $this->reRollEnchantmentService->canAfford($character, $selectedReRollType, $selectedAffix)) {
            return $this->errorResult('What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)');
        }

        $this->reRollEnchantmentService->reRoll(
            $character,
            $slot,
            $selectedAffix,
            $selectedReRollType
        );

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has re-rolled: '.$slot->item->affix_name, $slot->id));

        $slot = $slot->refresh();

        return $this->successResult(array_merge($this->buildQueenResponse($character), [
            'result_preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ]));
    }

    /**
     * Move the affixes.
     */
    public function moveAffixes(Character $character, int $selectedSlotId, int $selectedSecondarySlotId, string $selectedAffix): array
    {

        if (! $this->randomEnchantmentService->isPlayerInHell($character)) {
            event(new GlobalMessageEvent('The Queen of Hell is not happy that '.$character->name.' tried to talk to her while not in hell. "Hmmmp child, I do not like you right now!" As she pouts'));

            $item = Item::where('type', 'quest')->where('effect', ItemEffectType::QUEEN_OF_HEARTS->value)->first();

            return $this->errorResult('You need to be in Hell to access The Queen of Hearts and have the quest item: '.$item->affix_name.'.');
        }

        $slot = $character->inventory->slots->filter(function ($slot) use ($selectedSlotId) {
            return $slot->id === $selectedSlotId;
        })->first();

        $secondSlot = $character->inventory->slots->filter(function ($slot) use ($selectedSecondarySlotId) {
            return $slot->id === $selectedSecondarySlotId;
        })->first();

        if (is_null($slot) || is_null($secondSlot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        if ($slot->item->type === 'trinket' || $slot->item->type === 'artifact') {
            return $this->errorResult('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!');
        }

        if ($secondSlot->item->type === 'trinket' || $secondSlot->item->type === 'artifact') {
            return $this->errorResult('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!');
        }

        if (! $this->reRollEnchantmentService->canAffordMovementCost($character, $slot->item->id, $selectedAffix)) {
            return $this->errorResult('Child, you are so poor (Not enough currency) ...');
        }

        $this->reRollEnchantmentService->moveAffixes(
            $character,
            $slot,
            $secondSlot,
            $selectedAffix,
        );

        $character = $character->refresh();
        $slot = $slot->refresh();
        $secondSlot = $secondSlot->refresh();

        return $this->successResult(array_merge($this->buildQueenResponse($character), [
            'source_result_preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
            'destination_result_preview' => $this->craftingItemPreviewTransformer->transform($secondSlot->item, $secondSlot->id),
        ]));
    }

    private function buildCosts(Collection $uniqueSlots): array
    {
        return [
            'reroll' => $this->buildRerollCosts(),
            'movement' => $this->buildMovementCosts($uniqueSlots),
        ];
    }

    private function buildRerollCosts(): array
    {
        $costs = [];

        foreach ($this->reRollEnchantmentService->getSupportedAffixSelections() as $affixSelection) {
            foreach ($this->reRollEnchantmentService->getSupportedReRollTypes() as $reRollType) {
                $costs[$affixSelection][$reRollType] = $this->reRollEnchantmentService->getReRollCosts($reRollType, $affixSelection);
            }
        }

        return $costs;
    }

    private function buildMovementCosts(Collection $uniqueSlots): array
    {
        $costs = [];

        foreach ($uniqueSlots as $slot) {
            foreach ($this->reRollEnchantmentService->getSupportedMovementAffixSelections($slot->item) as $affixSelection) {
                $costs[$slot->id][$affixSelection] = $this->reRollEnchantmentService->getMovementCosts($slot->item_id, $affixSelection);
            }
        }

        return $costs;
    }
}
