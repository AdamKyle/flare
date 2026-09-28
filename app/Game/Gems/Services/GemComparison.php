<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Models\Item;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Transformers\CharacterGemTransformer;

class GemComparison
{
    use ResponseBuilder;

    /**
     * @param CharacterGemTransformer $characterGemTransformer
     */
    public function __construct(private readonly CharacterGemTransformer $characterGemTransformer) {}

    /**
     * Build factual generic Gem replacement options for one owned inventory Item.
     *
     * @param Character $character
     * @param int $inventorySlotId
     * @param int $gemSlotId
     * @return array
     */
    public function compareGemForItem(Character $character, int $inventorySlotId, int $gemSlotId): array
    {
        $slot = $character->inventory->slots()
            ->with('item.sockets.gem.characterModifiers.gameGemAbility')
            ->find($inventorySlotId);

        if (is_null($slot)) {
            return $this->errorResult('Selected item was not found in your inventory.');
        }

        $gemSlot = $character->gemBag->gemSlots()
            ->with('gem.characterModifiers.gameGemAbility')
            ->find($gemSlotId);

        if (is_null($gemSlot)) {
            return $this->errorResult('Selected gem was not found in your gem bag.');
        }

        $addedGem = $this->characterGemTransformer->transform($gemSlot->gem);
        $attachedGems = $slot->item->sockets
            ->filter(fn ($socket): bool => ! is_null($socket->gem))
            ->map(fn ($socket): array => $this->characterGemTransformer->transform($socket->gem))
            ->values()
            ->all();
        $replacements = $slot->item->sockets
            ->filter(fn ($socket): bool => ! is_null($socket->gem))
            ->map(fn ($socket): array => [
                'removed_gem' => $this->characterGemTransformer->transform($socket->gem),
                'added_gem' => $addedGem,
            ])
            ->values()
            ->all();

        return $this->successResult([
            'attached_gems' => $attachedGems,
            'socket_data' => [
                'item_sockets' => $slot->item->socket_count,
                'current_used_slots' => $slot->item->sockets->count(),
                'item_name' => $slot->item->affix_name,
            ],
            'has_gems_on_item' => $attachedGems !== [],
            'removed_gem' => null,
            'added_gem' => $addedGem,
            'replacements' => $replacements,
        ]);
    }

    /**
     * Return the generic Gems and modifiers that removing all Item sockets would remove.
     *
     * @param Item $item
     * @return array
     */
    public function ifItemGemsAreRemoved(Item $item): array
    {
        $item->loadMissing('sockets.gem.characterModifiers.gameGemAbility');

        return [
            'removed_gems' => $item->sockets
                ->filter(fn ($socket): bool => ! is_null($socket->gem))
                ->map(fn ($socket): array => $this->characterGemTransformer->transform($socket->gem))
                ->values()
                ->all(),
        ];
    }

    /**
     * Return one factual removed/added Gem pair without inferring final Character deltas.
     *
     * @param Gem $gemToAdd
     * @param Gem $gemToRemove
     * @return array
     */
    public function compareGems(Gem $gemToAdd, Gem $gemToRemove): array
    {
        return [
            'removed_gem' => $this->characterGemTransformer->transform($gemToRemove),
            'added_gem' => $this->characterGemTransformer->transform($gemToAdd),
        ];
    }
}
