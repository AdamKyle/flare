<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateQueenOfHeartsPanel;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class ReRollEnchantmentService {

    private AffixAttributeBuilder $affixAttributeBuilder;

    private RandomEnchantmentService $randomEnchantmentService;

    private $functionMap = [
        'base'       => [
            'setCoreModifiers',
            'setDamageDetails',
            'setClassBonus',
            'setSkillBonuses',
        ],
        'stats'      => [
            'increaseStats',
            'reduceEnemyStats',
        ],
        'skills'     => [
            'setSkillDetails',
            'setSkillBonuses',
        ],
        'damage'     => [
            'setDamageDetails',
            'setDevouringLight',
        ],
        'resistance' => [
            'setReductions',
        ],
    ];

    public function __construct(AffixAttributeBuilder $affixAttributeBuilder, RandomEnchantmentService $randomEnchantmentService) {
        $this->affixAttributeBuilder    = $affixAttributeBuilder;
        $this->randomEnchantmentService = $randomEnchantmentService;
    }

    public function reRoll(Character $character, InventorySlot $slot, string $affixType, string $reRollType, int $goldDustCost, int $shardCost) {
        $character->update([
            'gold_dust' => $character->gold_dust - $goldDustCost,
            'shards'    => $character->shards - $shardCost,
        ]);

        $duplicateItem   = $slot->item->duplicate();

        $duplicateItem   = $this->applyHolyStacks($slot->item, $duplicateItem);

        foreach ($this->fetchAffixesForReRoll($duplicateItem, $affixType) as $affix) {
            $this->changeAffix($character, $duplicateItem, $affix, $reRollType);
        }

        $duplicateItem->update([
            'market_sellable' => true,
        ]);

        $slot->update([
            'item_id' => $duplicateItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateQueenOfHeartsPanel($character->user, $this->randomEnchantmentService->fetchDataForApi($character)));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done it child! I have made the modifications and I think you\'ll be happy! Oh child I am so happy! ooh hoo hoo hoo!', true));
    }

    public function moveAffixes(Character $character, InventorySlot $slot, InventorySlot $secondarySlot, string $affixType, int $goldCost, int $shardCost) {
        $character->update([
            'gold'    => $character->gold - $goldCost,
            'shards'  => $character->shards - $shardCost,
        ]);

        $duplicateSecondaryItem = $secondarySlot->item->duplicate();
        $duplicateUnique        = $slot->item->duplicate();
        $duplicateSecondaryItem = $this->applyHolyStacks($slot->item, $duplicateSecondaryItem);

        $duplicateUnique->update([
            'market_sellable' => true,
        ]);

        $deletedAll  = false;
        $deletedSome = false;
        $deletedNone = false;

        if ($affixType === 'all-enchantments') {
            $deletedOne  = false;
            $deletedTwo  = false;


            if (!is_null($slot->item->item_suffix_id)) {
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

            if (!is_null($slot->item->item_prefix_id)) {
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

            if ($deletedOne && $deletedTwo) {
                $slot->delete();
                $duplicateUnique->delete();

                $deletedAll = true;
            } else {
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
                    'item_id' => $duplicateUnique->id
                ]);

                $deletedNone = true;
            }
        }

        $secondarySlot->update([
            'item_id' => $duplicateSecondaryItem->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateQueenOfHeartsPanel($character->user, $this->randomEnchantmentService->fetchDataForApi($character)));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done as thou have requested, my lovely, beautiful gorgeous child! Oh look at how powerful you are!', true));

        if ($deletedAll) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts glow so bright, thousands of demons in Hell are banished by her beauty and power alone!'));
        }

        if ($deletedSome) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts laugh! She is falling in love!'));
        }

        if ($deletedNone) {
            event(new GlobalMessageEvent($character->name . ' Makes the Queen of Hearts blush! She is attracted to them now.'));
        }
    }

    /**
     * Apply the old items holy stacks to the new item.
     *
     * @param Item $oldItem
     * @param Item $item
     * @return Item
     */
    protected function applyHolyStacks(Item $oldItem, Item $item): Item {
        if ($oldItem->appliedHolyStacks()->count() > 0) {

            foreach ($oldItem->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $item->id;

                $item->appliedHolyStacks()->create($stackAttributes);
            }
        }

        return $item->refresh();
    }

    protected function fetchAffixesForReRoll(Item $item, string $affixType): array {
        $affixes = [];

        if ($affixType === 'all-enchantments') {

            if (!is_null($item->item_prefix_id)) {
                if ($item->itemPrefix->randomly_generated) {
                    $affixes[] = $item->itemPrefix;
                }
            }

            if (!is_null($item->item_suffix_id)) {
                if ($item->itemSuffix->randomly_generated) {
                    $affixes[] = $item->itemSuffix;
                }
            }
        } else {
            $affixes[] = $item->{'item' . ucfirst($affixType)};
        }

        return $affixes;
    }

    protected function changeAffix(Character $character, Item $item, ItemAffix $itemAffix, string $changeType) {
        $amountPaid             = new RandomAffixDetails($itemAffix->cost);

        $affixeAttributeBuilder = $this->affixAttributeBuilder->setPercentageRange($amountPaid->getPercentageRange())
                                                              ->setDamageRange($amountPaid->getDamageRange())
                                                              ->setCharacterSkills($character->skills);
        if ($changeType === 'everything') {
            $changes = $affixeAttributeBuilder->buildAttributes($itemAffix->type, $itemAffix->cost);

            unset($changes['name']);
        } else {
            $changes = [];

            foreach ($this->functionMap[$changeType] as $functionName) {
                $changes = array_merge($changes, $affixeAttributeBuilder->{$functionName}());
            }
        }

        $duplicateAffix = $itemAffix->duplicate();

        $duplicateAffix->update($changes);

        $item->update(['item_' . $itemAffix->type . '_id' => $duplicateAffix->id]);
    }
}
