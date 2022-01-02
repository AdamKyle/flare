<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class ReRollEnchantmentService {

    private AffixAttributeBuilder $affixAttributeBuilder;

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

    public function __construct(AffixAttributeBuilder $affixAttributeBuilder) {
        $this->affixAttributeBuilder = $affixAttributeBuilder;
    }

    public function reRoll(Character $character, Item $item, string $affixType, string $reRollType, int $goldDustCost, int $shardCost) {
        $character->update([
            'gold_dust' => $character->gold_dust - $goldDustCost,
            'shards'    => $character->shards - $shardCost,
        ]);

        foreach ($this->fetchAffixesForReRoll($item, $affixType) as $affix) {
            $this->changeAffix($character, $item, $affix, $reRollType);
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new ServerMessageEvent($character->user, 'Ooooh hoo hoo hoo! I have done it child! I have made the modifications and I think you\'ll be happy! Oh child I am so happy! ooh hoo hoo hoo!', true));
    }

    protected function fetchAffixesForReRoll(Item $item, string $affixType): array {
        $affixes = [];

        if ($affixType === 'all-enchantments') {
            $affixes[] = $item->itemPrefix;
            $affixes[] = $item->itemSuffix;
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

        dump($changes);

        $duplicateAffix->update($changes);

        $item->update(['item_' . $itemAffix->type . '_id' => $duplicateAffix->id]);
    }
}