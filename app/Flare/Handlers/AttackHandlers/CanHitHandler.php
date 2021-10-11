<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Models\Character;

class CanHitHandler {

    private $attackExtraActionHandler;

    private $characterInformationBuilder;

    public function __construct(AttackExtraActionHandler $attackExtraActionHandler, CharacterInformationBuilder $characterInformationBuilder) {
        $this->attackExtraActionHandler    = $attackExtraActionHandler;
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function canHit($attacker, $defender, bool $isVoided = false): bool {

        if ($attacker instanceof  Character) {

            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            if ($this->attackExtraActionHandler->canAutoAttack($this->characterInformationBuilder)) {
                return true;
            }
        }


        $accuracyBonus = $this->fetchAccuracyBonus($attacker);
        $dodgeBonus    = $this->fetchDodgeBonus($defender);

        if ($accuracyBonus > 1.0) {
            return true;
        }

        if ($dodgeBonus > 1.0) {
            return false;
        }

        $toHit = 0;

        if ($defender instanceof  Character) {

            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($defender);

            $stat = $isVoided ? $defender->dex : $this->characterInformationBuilder->statMod('dex');

            $toHit = $this->toHitCalculation($attacker->dex, $stat, $accuracyBonus, $dodgeBonus);
        }

        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            $stat = $isVoided ? $attacker->{$attacker->class->to_hit_stat} :
                $this->characterInformationBuilder->statMod($attacker->class->to_hit_stat);

            $toHit = $this->toHitCalculation($stat, $defender->dex, $accuracyBonus, $dodgeBonus);
        }

        if ($toHit > 1.0) {
            return true;
        }

        $percent = floor((100 - $toHit));
        $needToHit = 100 - $percent;

        return rand(1, 100) > $needToHit;
    }

    /**
     * Fetch the accuracy bonus of the attacker.
     *
     * @param $attacker
     * @return float
     */
    protected function fetchAccuracyBonus($attacker): float {
        $accuracyBonus = $attacker->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                ->where('game_skills.name', 'Accuracy');
        })->first();

        if (is_null($accuracyBonus)) {
            $accuracyBonus = 0.0;
        } else {
            $accuracyBonus = $accuracyBonus->skill_bonus;
        }

        return $accuracyBonus;
    }

    /**
     * Fetch the dpdge bonus of the defender.
     *
     * @param $defender
     * @return float
     */
    protected function fetchDodgeBonus($defender): float {
        $dodgeBonus    = $defender->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                ->where('game_skills.name', 'Dodge');
        })->first();

        if (is_null($dodgeBonus)) {
            $dodgeBonus = 0.0;
        } else {
            $dodgeBonus = $dodgeBonus->skill_bonus;
        }

        return $dodgeBonus;
    }

    /**
     * Calculate the ToHit Percentage.
     *
     * This consists of dexterity, to hit stat, accuracy and the enemy dodge.
     *
     * @param int $toHit
     * @param int $dex
     * @param float $accuracy
     * @param float $dodge
     * @return float|int
     */
    protected function toHitCalculation(int $toHit, int $dex, float $accuracy, float $dodge) {
        $dex   = ($dex / 10000);
        $toHit = ($toHit + $toHit * $accuracy) / 100;

        return ($dex + $dex * $dodge) - $toHit;
    }
}