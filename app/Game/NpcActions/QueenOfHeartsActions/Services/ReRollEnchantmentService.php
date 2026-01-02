<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Items\Builders\AffixAttributeBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;

class ReRollEnchantmentService
{
    private AffixAttributeBuilder $affixAttributeBuilder;

    private RandomEnchantmentService $randomEnchantmentService;

    private int $goldDust;

    private int $shardCost;

    private $functionMap = [
        'base' => [
            'setCoreModifiers',
        ],
        'stats' => [
            'increaseStats',
            'reduceEnemyStats',
        ],
        'skills' => [
            'setSkillDetails',
            // 'setSkillBonuses',
        ],
        'damage' => [
            'setDamageDetails',
            'setDevouringLight',
            'setLifeStealingAmount',
            'setEntrancingAmount',
        ],
        'resistance' => [
            'setReductions',
        ],
    ];

    public function __construct(AffixAttributeBuilder $affixAttributeBuilder, RandomEnchantmentService $randomEnchantmentService)
    {
        $this->affixAttributeBuilder = $affixAttributeBuilder;
        $this->randomEnchantmentService = $randomEnchantmentService;
    }

    public function canAfford(Character $character, string $type, string $selectedAffix): bool
    {
        $goldDust = $this->getGoldDustCost($character, $type, $selectedAffix);
        $shards = $this->getShardsCost($character, $type, $selectedAffix);

        return $character->gold_dust > $goldDust && $character->shards > $shards;
    }

    public function reRoll(Character $character, InventorySlot $slot, string $affixType, string $reRollType)
    {
        $character->update([
            'gold_dust' => $character->gold_dust - $this->goldDust,
            'shards' => $character->shards - $this->shardCost,
        ]);

        $duplicateItem = $this->doReRoll($character, $slot->item, $affixType, $reRollType);

        $slot->update([
            'item_id' => $duplicateItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done it, child! I have made the modifications and I think you\'ll be happy! Oh child, I am so happy! Ooh hoo hoo hoo!'));
    }

    public function doReRoll(Character $character, Item $item, string $affixType, string $reRollType)
    {
        $duplicateItem = DuplicateItemHandler::duplicateItem($item);

        $affixes = $this->fetchAffixesForReRoll($duplicateItem, $affixType);

        foreach ($affixes as $affix) {
            $this->changeAffix($character, $duplicateItem, $affix, $reRollType);
        }

        $duplicateItem = $duplicateItem->refresh();

        $duplicateItem->update([
            'market_sellable' => true,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
        ]);

        return $duplicateItem->refresh();
    }

    public function canAffordMovementCost(Character $character, int $selectedItemToMoveId, string $selectedAffix)
    {
        $costs = $this->getMovementCosts($selectedItemToMoveId, $selectedAffix);

        return $character->gold_dust >= $costs['gold_dust_cost'] && $character->shards >= $costs['shards_cost'];
    }

    public function getMovementCosts(int $selectedItemToMoveId, string $selectedAffix): array
    {
        $item = Item::find($selectedItemToMoveId);

        if (is_null($item)) {
            return [];
        }

        $cost = 0;

        if ($selectedAffix === 'all-enchantments') {
            if (! is_null($item->item_prefix_id)) {
                $cost += $item->itemPrefix->cost;
            }

            if (! is_null($item->item_suffix_id)) {
                $cost += $item->itemSuffix->cost;
            }
        } else {
            $cost += ItemAffix::find($item->{'item_'.$selectedAffix.'_id'})->cost;
        }

        $cost = $cost / 1000000;
        $shardCost = $cost * .005;

        $shardCost = (int) round($shardCost);

        return [
            'gold_dust_cost' => $cost,
            'shards_cost' => $shardCost,
        ];
    }

    public function moveAffixes(Character $character, InventorySlot $slot, InventorySlot $secondarySlot, string $affixType)
    {
        $costs = $this->getMovementCosts($slot->item_id, $affixType);

        $character->update([
            'gold_dust' => $character->gold_dust - $costs['gold_dust_cost'],
            'shards' => $character->shards - $costs['shards_cost'],
        ]);

        $duplicateSecondaryItem = DuplicateItemHandler::duplicateItem($secondarySlot->item);
        $duplicateUnique = DuplicateItemHandler::duplicateItem($slot->item);

        $duplicateUnique->update([
            'market_sellable' => true,
        ]);

        $duplicateUnique = $duplicateUnique->refresh();

        $deletedAll = false;
        $deletedSome = false;
        $deletedNone = false;

        if ($affixType === 'all-enchantments') {
            $deletedOne = false;
            $deletedTwo = false;

            if (! is_null($slot->item->item_suffix_id)) {
                if ($slot->item->itemSuffix->randomly_generated) {
                    $duplicateSecondaryItem->update([
                        'item_suffix_id' => $slot->item->item_suffix_id,
                    ]);

                    $duplicateUnique->update([
                        'item_suffix_id' => null,
                    ]);

                    $deletedOne = true;
                }
            }

            if (! is_null($slot->item->item_prefix_id)) {
                if ($slot->item->itemPrefix->randomly_generated) {
                    $duplicateSecondaryItem->update([
                        'item_prefix_id' => $slot->item->item_prefix_id,
                    ]);

                    $duplicateUnique->update([
                        'item_prefix_id' => null,
                    ]);

                    $deletedTwo = true;
                }
            }

            if ($deletedOne && $deletedTwo && $slot->item->appliedHolyStacks->isEmpty() && $slot->item->socket_count === 0) {
                $slot->delete();
                $duplicateUnique->delete();

                $deletedAll = true;
            } else {

                if ($deletedOne && $deletedTwo) {
                    $duplicateUnique->update([
                        'is_mythic' => false,
                        'is_cosmic' => false,
                    ]);

                    $duplicateUnique = $duplicateUnique->refresh();
                }

                $slot->update([
                    'item_id' => $duplicateUnique->id,
                ]);

                $deletedSome = true;
            }
        } else {
            $duplicateSecondaryItem->update([
                'item_'.$affixType.'_id' => $slot->item->{'item_'.$affixType.'_id'},
            ]);

            $duplicateUnique->update([
                'item_'.$affixType.'_id' => null,
            ]);

            $duplicateUnique = $duplicateUnique->refresh();

            if (is_null($duplicateUnique->itemSuffix) && is_null($duplicateUnique->itemPrefix)) {
                $duplicateUnique->delete();
                $slot->delete();

                $deletedSome = true;
            } else {
                $slot->update([
                    'item_id' => $duplicateUnique->id,
                ]);

                $deletedNone = true;
            }
        }

        $duplicateSecondaryItem->update([
            'is_market_sellable' => true,
            'is_mythic' => $slot->item->is_mythic,
            'is_cosmic' => $slot->item->is_cosmic,
        ]);

        $secondarySlot->update([
            'item_id' => $duplicateSecondaryItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done as thou have requested, my lovely, beautiful, gorgeous child! Oh look at how powerful you are!'));

        if ($deletedAll) {
            event(new GlobalMessageEvent($character->name.' Makes the Queen of Hearts glow so bright, thousands of demons in Hell are banished by her beauty and power alone!'));
        }

        if ($deletedSome) {
            event(new GlobalMessageEvent($character->name.' Makes the Queen of Hearts laugh! She is falling in love!'));
        }

        if ($deletedNone) {
            event(new GlobalMessageEvent($character->name.' Makes the Queen of Hearts blush! She is attracted to them now.'));
        }

        $slot = $secondarySlot->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has moved the affixes and created the item: '.$slot->item->affix_name, $slot->id));
    }

    protected function getGoldDustCost(Character $character, string $type, string $selectedAffix): int
    {
        $goldDust = 10000;

        if ($selectedAffix === 'all-enchantments') {
            $goldDust *= 2;
        }

        if ($type === 'everything') {
            $goldDust += 500;
        } else {
            $goldDust += 100;
        }

        return $this->goldDust = $goldDust;
    }

    protected function getShardsCost(Character $character, string $type, string $selectedAffix): int
    {
        $shardCost = 100;

        if ($selectedAffix === 'all-enchantments') {
            $shardCost *= 2;
        }

        if ($type === 'everything') {
            $shardCost += 250;
        } else {
            $shardCost += 100;
        }

        return $this->shardCost = $shardCost;
    }

    protected function fetchAffixesForReRoll(Item $item, string $affixType): array
    {
        $affixes = [];

        if ($affixType === 'all-enchantments') {
            if (! is_null($item->item_prefix_id)) {
                if ($item->itemPrefix->randomly_generated) {
                    $affixes[] = $item->itemPrefix;
                }
            }

            if (! is_null($item->item_suffix_id)) {
                if ($item->itemSuffix->randomly_generated) {
                    $affixes[] = $item->itemSuffix;
                }
            }
        } else {
            $affixes[] = $item->{'item'.ucfirst($affixType)};
        }

        return $affixes;
    }

    protected function changeAffix(Character $character, Item $item, ItemAffix $itemAffix, string $changeType)
    {

        $oldCost = 100000000000; // Old Legendary Cost
        $updateNewCost = 0;

        if ($oldCost === $itemAffix->cost) {
            $updateNewCost = RandomAffixDetails::LEGENDARY;
        }

        $amountPaid = new RandomAffixDetails($updateNewCost > 0 ? $updateNewCost : $itemAffix->cost);

        $affixAttributeBuilder = $this->affixAttributeBuilder->setPercentageRange($amountPaid->getPercentageRange())
            ->setDamageRange($amountPaid->getDamageRange())
            ->setCharacterSkills($character->skills);

        if ($changeType === 'everything') {
            $changes = $affixAttributeBuilder->buildAttributes($itemAffix->type, $itemAffix->cost);

            unset($changes['name']);
        } else {
            $changes = [];

            foreach ($this->functionMap[$changeType] as $functionName) {

                $changes = array_merge($changes, $affixAttributeBuilder->{$functionName}());
            }
        }

        if ($updateNewCost > 0) {
            $changes['cost'] = $updateNewCost;
        }

        $duplicateAffix = $itemAffix->duplicate();

        $duplicateAffix->update($changes);

        $item->update(['item_'.$itemAffix->type.'_id' => $duplicateAffix->id]);
    }
}
