<?php

namespace App\Game\Reincarnate\Services;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\FeatureTypes;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Traits\CharacterMaxLevel;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Reincarnate\Values\MaxReincarnationStats;

class CharacterReincarnationService
{
    use CharacterMaxLevel, ResponseBuilder;

    public function __construct(
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        private readonly BaseStatValue $baseStatValue
    ) {}

    public function reincarnate(Character $character): array
    {

        $maxLevel = MaxLevelConfiguration::first()->max_level;

        if ($this->getMaxLevel($character) < $maxLevel) {
            return $this->errorResult('You need to complete the quest: Reach for the stars (Labyrinth, one off quests) to be able to reincarnate');
        }

        if ($character->level < $maxLevel) {
            return $this->errorResult('You must be at max level to reincarnate. Max level is 5,000 which you can level to by obtaining the "Sash of the Heavens" from the "Reach for the stars" Labyrinth one off quest');
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

        $baseStats = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $baseStatsToReincarnate = [];

        foreach ($baseStats as $stat) {
            if ($character->{$stat} < MaxReincarnationStats::MAX_STATS) {
                $baseStatsToReincarnate[] = $stat;
            }
        }

        if (empty($baseStatsToReincarnate)) {
            return $this->errorResult('You have maxed all stats to '.number_format(MaxReincarnationStats::MAX_STATS).'.');
        }

        return $this->doReincarnation($character, $baseStatsToReincarnate);
    }

    public function doReincarnation(Character $character, ?array $baseStats = null): array
    {
        $skipMaxedStats = is_null($baseStats);
        $baseStats = $baseStats ?? ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $updatedStats = [];
        $lastReincarnatedStatBonus = 0;
        $baseStat = $this->baseStatValue->setRace($character->race)->setClass($character->class);

        foreach ($baseStats as $stat) {

            $characterStat = $character->{$stat};

            if ($skipMaxedStats && $characterStat >= MaxReincarnationStats::MAX_STATS) {
                continue;
            }

            $base = $baseStat->{$stat}() + $character->reincarnated_stat_increase;

            $characterBonus = $characterStat * 0.05;
            $lastReincarnatedStatBonus = $characterBonus;
            $base = $base + $characterBonus;

            if ($base >= MaxReincarnationStats::MAX_STATS) {
                $base = MaxReincarnationStats::MAX_STATS;
            }

            $updatedStats[$stat] = $base;
        }

        if (empty($updatedStats)) {
            return $this->errorResult('You have maxed all stats to '.number_format(MaxReincarnationStats::MAX_STATS).'.');
        }

        $newReincarnatedStatBonus = $character->reincarnated_stat_increase + $lastReincarnatedStatBonus;

        if ($newReincarnatedStatBonus > MaxReincarnationStats::MAX_STATS) {
            $newReincarnatedStatBonus = MaxReincarnationStats::MAX_STATS;
        }

        $timesReincarnated = $character->times_reincarnated + 1;

        $baseXpPenalty = 0.02;

        $xpPenalty = $character->xp_penalty + $baseXpPenalty;

        $additionalUpdates = [
            'xp_penalty' => $xpPenalty,
            'level' => 1,
            'xp' => 0,
            'xp_next' => 100 + 100 * $xpPenalty,
            'copper_coins' => $character->copper_coins > 0 ? $character->copper_coins - 50000 : 0,
            'reincarnated_stat_increase' => $newReincarnatedStatBonus,
            'times_reincarnated' => $timesReincarnated,
        ];

        $character->update(array_merge($updatedStats, $additionalUpdates));

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $this->successResult([
            'message' => 'Reincarnated character and applied 5% of your current level (base) stats toward your new (base) stats.',
        ]);
    }
}
