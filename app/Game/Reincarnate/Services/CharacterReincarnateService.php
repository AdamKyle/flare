<?php

namespace App\Game\Reincarnate\Services;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\FeatureTypes;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\CharacterMaxLevel;
use App\Game\Core\Traits\ResponseBuilder;

class CharacterReincarnateService {

    const MAX_STATS = 9999999999;

    use ResponseBuilder, CharacterMaxLevel;

    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function reincarnate(Character $character): array {

        $maxLevel = MaxLevelConfiguration::first()->max_level;

        if ($this->getMaxLevel($character) < $maxLevel) {
            return $this->errorResult('You need to complete the quest: Reach for the stars (Labyrinth, one off quests) to be able to reincarnate');
        }

        if ($character->level < $maxLevel) {
            return $this->errorResult('You must be at max level to reincarnate.');
        }

        $completedQuest = $character->questsCompleted()->whereNotNull('quest_id')->get()->filter(function ($completedQuest) {
            return $completedQuest->quest->unlocks_feature === FeatureTypes::REINCARNATION;
        })->first();

        if (is_null($completedQuest)) {
            return $this->errorResult('You must complete: "The story of rebirth" quest line in Hell first.');
        }

        if ($character->copper_coins < 50000) {
            return $this->errorResult('Reincarnation costs 50,000 Copper Coins');
        }

        return $this->doReincarnation($character);
    }

    public function doReincarnation(Character $character): array {
        $baseStats    = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $updatedStats = [];
        $baseStat     = resolve(BaseStatValue::class)->setRace($character->race)->setClass($character->class);

        foreach ($baseStats as $stat) {

            $characterStat = $character->{$stat};

            if ($characterStat >= self::MAX_STATS) {
                continue;
            }

            $base            = $baseStat->{$stat}() + $character->reincarnated_stat_increase;
            $characterBonus  = $characterStat * 0.20;
            $base            = $base + $characterBonus;

            if ($base >= self::MAX_STATS) {
                $base = self::MAX_STATS;
            }

            $updatedStats[$stat] = $base;
        }

        if (empty($updatedStats)) {
            return $this->errorResult('You have maxed all stats to '.number_format(self::MAX_STATS).'.');
        }



        $newReincarnatedStatBonus = $character->reincarnated_stat_increase + $characterBonus;

        if ($newReincarnatedStatBonus > self::MAX_STATS) {
            $newReincarnatedStatBonus = self::MAX_STATS;
        }

        $timesReincarnated = $character->times_reincarnated + 1;

        $baseXpPenalty = 0.05;

        if ($timesReincarnated >= 10 && $timesReincarnated < 25) {
            $baseXpPenalty = 0.08;
        }

        if ($timesReincarnated >= 25 && $timesReincarnated < 50) {
            $baseXpPenalty = 0.10;
        }

        if ($timesReincarnated >= 50) {
            $baseXpPenalty = 0.12;
        }

        $xpPenalty = $character->xp_penalty + $baseXpPenalty;


        $additionalUpdates = [
            'xp_penalty'                 => $xpPenalty,
            'level'                      => 1,
            'xp'                         => 0,
            'xp_next'                    => 100 + 100 * $xpPenalty,
            'copper_coins'               => $character->copper_coins > 0 ? $character->copper_coins - 50000 : 0,
            'reincarnated_stat_increase' => $newReincarnatedStatBonus,
            'times_reincarnated'         => $timesReincarnated,
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
