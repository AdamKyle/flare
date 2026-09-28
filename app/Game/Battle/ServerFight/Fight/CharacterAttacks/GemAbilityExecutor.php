<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Gems\Values\GemAbilityScalingSource;

class GemAbilityExecutor
{
    /**
     * @param CharacterCacheData $characterCacheData
     * @param ChanceCalculator $chanceCalculator
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
        private readonly ChanceCalculator $chanceCalculator,
    ) {}

    /**
     * Execute the cached active Gem Abilities once after a completed top-level Character action.
     *
     * @param Character $character
     * @param string $attackType
     * @param int $characterHealth
     * @param int $monsterHealth
     * @param bool $isRaidBoss
     * @return array
     */
    public function execute(
        Character $character,
        string $attackType,
        int $characterHealth,
        int $monsterHealth,
        bool $isRaidBoss,
    ): array {
        if ($characterHealth <= 0 || $monsterHealth <= 0) {
            return ['monster_health' => $monsterHealth, 'messages' => []];
        }

        $attackData = $this->characterCacheData->getDataFromAttackCache($character, $attackType);
        $abilities = $attackData['gem_abilities']['active'] ?? [];
        $messages = [];

        foreach ($abilities as $ability) {
            if (! $this->chanceCalculator->passesPercentage($ability['proc_chance'] * 100)) {
                continue;
            }

            $damage = floor($this->scalingValue($attackData, $ability['scaling_source']) * $ability['effect_value']);

            if ($isRaidBoss) {
                $damage = min($damage, BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES);
            }

            $monsterHealth -= $damage;
            $messages[] = [
                'message' => $ability['name'].' erupts from your socketed Gem and deals '.number_format($damage).' bonus damage.',
                'type' => 'player-action',
            ];

            if ($monsterHealth <= 0) {
                break;
            }
        }

        return ['monster_health' => $monsterHealth, 'messages' => $messages];
    }

    /**
     * Return the cached combat value selected by an active Gem Ability scaling source.
     *
     * @param array $attackData
     * @param string $scalingSource
     * @return float
     */
    private function scalingValue(array $attackData, string $scalingSource): float
    {
        return match ($scalingSource) {
            GemAbilityScalingSource::WEAPON_ATTACK->value => $attackData['weapon_damage'] ?? 0,
            GemAbilityScalingSource::SPELL_ATTACK->value => $attackData['spell_damage'] ?? 0,
            GemAbilityScalingSource::DAMAGE_STAT->value => $attackData['damage_stat_amount'] ?? 0,
            GemAbilityScalingSource::DEFENCE->value => $attackData['defence'] ?? 0,
            default => 0,
        };
    }
}
