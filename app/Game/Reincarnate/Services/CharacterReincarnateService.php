<?php

namespace App\Game\Reincarnate\Services;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\FeatureTypes;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;

class CharacterReincarnateService {

    use ResponseBuilder;

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function reincarnate(Character $character): array {

        $completedQuest = $character->questsCompleted->filter(function ($completedQuest) {
            return $completedQuest->quest->unlocks_feature === FeatureTypes::REINCARNATION;
        })->first();

        if (is_null($completedQuest)) {
            return $this->errorResult('You must complete: "The story of rebirth" quest line in Hell first.');
        }

        if ($character->copper_coins < 50000) {
            return $this->errorResult('Reincarnation costs 50,000 Copper Coins');
        }

        $baseStats    = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $updatedStats = [];
        $baseStat     = resolve(BaseStatValue::class)->setRace($character->race)->setClass($character->class);

        foreach ($baseStats as $stat) {

            $characterStat = $character->{$stat};

            if ($characterStat >= 999999) {
               continue;
            }

            $base            = $baseStat->{$stat}() + $character->reincarnated_stat_increase;
            $characterBonus  = $characterStat * 0.20;
            $base            = $base + $characterBonus;

            if ($base >= 999999) {
                $base = 999999;
            }

            $updatedStats[$stat] = $base;
        }

        if (empty($updatedStats)) {
            return $this->errorResult('You have maxed all stats to 999,999.');
        }

        $xpPenalty = $character->xp_penalty + 0.05;

        $newReincarnatedStatBonus = $character->reincarnated_stat_increase + $characterBonus;

        if ($newReincarnatedStatBonus > 999999) {
            $newReincarnatedStatBonus = 999999;
        }

        $additionalUpdates = [
            'xp_penalty'                 => $xpPenalty,
            'level'                      => 1,
            'xp'                         => 0,
            'xp_next'                    => 100 + 100 * $xpPenalty,
            'copper_coins'               => $character->copper_coins - 50000,
            'reincarnated_stat_increase' => $newReincarnatedStatBonus,
            'times_reincarnated'         => $character->times_reincarnated + 1,
        ];

        $character->update(array_merge($updatedStats, $additionalUpdates));

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'Reincarnated character and applied 20% of your current level (base) stats toward your new (base) stats.'
        ]);
    }
}
