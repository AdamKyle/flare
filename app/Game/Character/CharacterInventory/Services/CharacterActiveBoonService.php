<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class CharacterActiveBoonService
{
    public function __construct(
        private readonly Manager $manager,
        private readonly UsableItemTransformer $usableItemTransformer,
    ) {}

    /**
     * Build the presentation-ready active Boon rows for the character.
     */
    public function activeBoons(Character $character): array
    {
        return $character->boons()->active()->with('itemUsed')->get()
            ->map(fn (CharacterBoon $boon): array => $this->buildBoonRow($character, $boon))
            ->all();
    }

    /**
     * Build one presentation-ready active Boon row, including its source item and remaining Alchemy Bag amount.
     */
    private function buildBoonRow(Character $character, CharacterBoon $boon): array
    {
        $boonAppliedItem = $this->manager->createData(new Item($boon->itemUsed, $this->usableItemTransformer))->toArray()['data'];
        $boonAppliedItem['name'] = $boon->itemUsed->name;

        return [
            'id' => $boon->id,
            'character_id' => $boon->character_id,
            'item_id' => $boon->item_id,
            'last_for_minutes' => $boon->last_for_minutes,
            'amount_used' => $boon->amount_used,
            'started' => $boon->started,
            'complete' => $boon->complete,
            'boon_applied' => $boonAppliedItem,
            'amount_left' => $this->amountLeft($character, $boon),
        ];
    }

    /**
     * Resolve the remaining Alchemy Bag amount for the boon's source item.
     */
    private function amountLeft(Character $character, CharacterBoon $boon): int
    {
        if (is_null($character->alchemyBag)) {
            return 0;
        }

        return $character->alchemyBag->slots()
            ->where('character_id', $character->id)
            ->where('item_id', $boon->item_id)
            ->get()
            ->sum('amount');
    }
}
