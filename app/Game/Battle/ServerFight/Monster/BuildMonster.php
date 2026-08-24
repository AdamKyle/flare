<?php

namespace App\Game\Battle\ServerFight\Monster;

use App\Game\Battle\ServerFight\BattleMessages;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;

class BuildMonster extends BattleMessages
{
    private ServerMonster $serverMonster;

    public function __construct(
        ServerMonster $serverMonster,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly RandomNumberGenerator $randomNumberGenerator,
    ) {
        parent::__construct();

        $this->serverMonster = $serverMonster;
    }

    public function setServerMonster(array $monster): ServerMonster
    {
        return $this->serverMonster->setMonster($monster);
    }

    public function buildMonster(array $monster, array $characterStatReductionAffixes, float $skillReduction, float $resistanceReduction): ServerMonster
    {

        $monster = $this->reduceEnemySkills($monster, $skillReduction);
        $monster = $this->reduceResistances($monster, $resistanceReduction);
        $monster = $this->reduceAllStats($monster, $characterStatReductionAffixes, $resistanceReduction);

        $this->serverMonster->setMonster($monster)->setHealth($this->buildHealth($monster));

        return $this->serverMonster;
    }

    public function canMonsterHaveStatsReduced(array $monster, float $resistanceReduction, bool $canBeResisted): bool
    {
        if ($canBeResisted) {
            return true;
        }

        $chance = $monster['affix_resistance'] - $resistanceReduction;

        if ($chance > 1) {
            return true;
        }

        $dc = 50 + ceil(50 * $chance);

        if ($dc >= 100) {
            $dc = 99;
        }

        return $this->chanceCalculator->passesPercentage(100 - $dc);
    }

    private function buildHealth(array $monster): int
    {
        $healthArray = explode('-', $monster['health_range']);

        $health = $this->randomNumberGenerator->numberBetween($healthArray[0], $healthArray[1]);

        $increasesHealthBy = $monster['increases_damage_by'];

        if (! is_null($increasesHealthBy)) {
            $health = $health + $health * $increasesHealthBy;
        }

        return $health;
    }

    private function reduceEnemySkills(array $monster, float $skillReduction): array
    {

        if (! is_null($monster['only_for_location_type'])) {
            $skillReduction = $skillReduction / 2;
        }

        if ($skillReduction > 0.0) {

            $monster['accuracy'] = $monster['accuracy'] - $skillReduction;
            $monster['casting_accuracy'] = $monster['casting_accuracy'] - $skillReduction;
            $monster['dodge'] = $monster['dodge'] - $skillReduction;
            $monster['criticality'] = $monster['criticality'] - $skillReduction;

            if ($monster['accuracy'] <= 0) {
                $monster['accuracy'] = 0.0;
            }

            if ($monster['casting_accuracy'] <= 0) {
                $monster['casting_accuracy'] = 0.0;
            }

            if ($monster['dodge'] <= 0) {
                $monster['dodge'] = 0.0;
            }

            if ($monster['criticality'] <= 0) {
                $monster['criticality'] = 0.0;
            }

            $this->addMessage($monster['name'].' Thrashes around blindly with out agility or sound! (skills % reduced)', 'player-action');
        }

        return $monster;
    }

    private function reduceResistances(array $monster, float $resistanceReduction): array
    {

        if (! is_null($monster['only_for_location_type'])) {
            $resistanceReduction = $resistanceReduction / 2;
        }

        if ($resistanceReduction > 0.0) {
            $monster['spell_evasion'] = $monster['spell_evasion'] - $resistanceReduction;
            $monster['affix_resistance'] = $monster['affix_resistance'] - $resistanceReduction;
            $monster['counter_resistance_chance'] = $monster['counter_resistance_chance'] - $resistanceReduction;
            $monster['ambush_resistance_chance'] = $monster['ambush_resistance_chance'] - $resistanceReduction;

            if ($monster['spell_evasion'] < 0) {
                $monster['spell_evasion'] = 0;
            }

            if ($monster['affix_resistance'] < 0) {
                $monster['affix_resistance'] = 0;
            }

            if ($monster['counter_resistance_chance'] < 0) {
                $monster['counter_resistance_chance'] = 0;
            }

            if ($monster['ambush_resistance_chance'] < 0) {
                $monster['ambush_resistance_chance'] = 0;
            }

            $this->addMessage($monster['name'].' is less resistant to your charms! (spell/affix/ambush/counter resistance\'s reduced!)', 'player-action');
        }

        return $monster;
    }

    private function reduceAllStats(array $monster, array $characterStatReductionAffixes, float $resistanceReduction): array|bool
    {

        $allStatReduction = $characterStatReductionAffixes['all_stat_reduction'];

        if (is_null($allStatReduction)) {
            return $monster;
        }

        if ($this->canMonsterHaveStatsReduced($monster, $resistanceReduction, $characterStatReductionAffixes['cant_be_resisted'])) {

            $stats = ['str', 'int', 'dex', 'dur', 'agi', 'chr', 'focus'];

            foreach ($stats as $stat) {
                if (! is_null($monster['only_for_location_type'])) {

                    $reduction = $allStatReduction[$stat.'_reduction'] / 2;
                } else {
                    $reduction = $allStatReduction[$stat.'_reduction'];
                }

                $monster[$stat] = ceil($monster[$stat] - $monster[$stat] * $reduction);
            }

            return $monster;
        }

        return $monster;
    }
}
