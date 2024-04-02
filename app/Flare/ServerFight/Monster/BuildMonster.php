<?php

namespace App\Flare\ServerFight\Monster;


use App\Flare\ServerFight\BattleMessages;

class BuildMonster extends BattleMessages {

    private ServerMonster $serverMonster;

    public function __construct(ServerMonster $serverMonster) {
        parent::__construct();

        $this->serverMonster = $serverMonster;
    }

    public function setServerMonster(array $monster): ServerMonster {
        return $this->serverMonster->setMonster($monster);
    }

    public function buildMonster(array $monster, array $characterStatReductionAffixes, float $skillReduction, float $resistanceReduction): ServerMonster {

        $monster = $this->reduceEnemySkills($monster, $skillReduction);
        $monster = $this->reduceResistances($monster, $resistanceReduction);
        $monster = $this->reduceAllStats($monster, $characterStatReductionAffixes, $resistanceReduction);

        $this->serverMonster->setMonster($monster)->setHealth($this->buildHealth($monster));

        return $this->serverMonster;
    }

    public function canMonsterHaveStatsReduced(array $monster, float $resistanceReduction, bool $canBeResisted): bool {
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

        return rand(1, 100) > $dc;
    }

    protected function buildHealth(array $monster): int {
        $healthArray = explode('-', $monster['health_range']);

        $health = rand($healthArray[0], $healthArray[1]);

        $increasesHealthBy = $monster['increases_damage_by'];

        if (!is_null($increasesHealthBy)) {
            $health = $health + $health * $increasesHealthBy;
        }

        return $health;
    }

    protected function reduceEnemyStats(array $monster, array $characterStatReductionAffixes): array {
        if (is_null($characterStatReductionAffixes['all_stat_reduction']) && empty($characterStatReductionAffixes['stat_reduction'])) {
            return $monster;
        }

        if (!is_null($characterStatReductionAffixes['all_stat_reduction'])) {
            $monster = $this->reduceAllStats($monster, $characterStatReductionAffixes);

            if (!$monster) {
                $this->addMessage($monster['name'] . ' laughs at your attempt to make them weak (All Stat Reduction Failed).', 'regular');
            } else {
                $this->addMessage($monster['name'] . ' sinks to their knees in agony!', 'player-action');
            }
        }

        if (!empty($characterStatReductionAffixes['stat_reduction'])) {
            $monster = $this->reduceSpecificStats($monster, $characterStatReductionAffixes);
        }

        return $monster;
    }

    protected function reduceEnemySkills(array $monster, float $skillReduction): array {

        if (!is_null($monster['only_for_location_type'])) {
            $skillReduction = $skillReduction / 2;
        }

        if ($skillReduction > 0.0) {

            $monster['accuracy']         = $monster['accuracy'] - $skillReduction;
            $monster['casting_accuracy'] = $monster['casting_accuracy'] - $skillReduction;
            $monster['dodge']            = $monster['dodge'] - $skillReduction;
            $monster['criticality']      = $monster['criticality'] - $skillReduction;

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

            $this->addMessage($monster['name'] . ' Thrashes around blindly with out agility or sound! (skills % reduced)', 'player-action');
        }

        return $monster;
    }

    protected function reduceResistances(array $monster, float $resistanceReduction): array {

        if (!is_null($monster['only_for_location_type'])) {
            $resistanceReduction = $resistanceReduction / 2;
        }

        if ($resistanceReduction > 0.0) {
            $monster['spell_evasion']             = $monster['spell_evasion'] - $resistanceReduction;
            $monster['affix_resistance']          = $monster['affix_resistance'] - $resistanceReduction;
            $monster['counter_resistance_chance'] = $monster['counter_resistance_chance'] - $resistanceReduction;
            $monster['ambush_resistance_chance']  = $monster['ambush_resistance_chance'] - $resistanceReduction;

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

            $this->addMessage($monster['name'] . ' is less resistant to your charms! (spell/affix/ambush/counter resistance\'s reduced!)', 'player-action');
        }

        return $monster;
    }

    private function reduceAllStats(array $monster, array $characterStatReductionAffixes, float $resistanceReduction): array|bool {

        $allStatReduction = $characterStatReductionAffixes['all_stat_reduction'];

        if (is_null($allStatReduction)) {
            return $monster;
        }

        if ($this->canMonsterHaveStatsReduced($monster, $resistanceReduction, $characterStatReductionAffixes['cant_be_resisted'])) {

            $stats = ['str', 'int', 'dex', 'dur', 'agi', 'chr', 'focus'];

            foreach ($stats as $stat) {
                if (!is_null($monster['only_for_location_type'])) {

                    $reduction = $allStatReduction[$stat . '_reduction'] / 2;
                } else {
                    $reduction = $allStatReduction[$stat . '_reduction'];
                }

                $monster[$stat] = ceil($monster['str'] - $monster['str'] * $reduction);
            }

            return $monster;
        }

        return $monster;
    }

    private function reduceSpecificStats(array $monster, array $characterStatReductionAffixes): array {
        $statReduction = $characterStatReductionAffixes['stat_reduction'];
        $stats         = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

        foreach ($stats as $stat) {
            $key = $stat . '_reduction';
            $sumOfReduction = array_sum(array_column($statReduction, $key));
            $maxResistance  = max(array_column($statReduction, 'resistance_reduction'));

            if ($this->canMonsterHaveStatsReduced($monster, $maxResistance, $statReduction['cant_be_resisted'])) {

                if (!is_null($monster['only_for_location_type'])) {
                    $sumOfReduction = $sumOfReduction / 2;
                }

                $monster[$stat] = ceil($monster[$stat] - $monster[$stat] * $sumOfReduction);

                if ($monster[$stat] <= 0) {
                    $monster[$stat] = 1;
                }
            } else {
                $this->addMessage($monster['name'] . ' taunts you as one of your stat reducing affixes fails to fire! ('.$stat.' failed to fire)', 'regular');
            }
        }

        return $monster;
    }
}
