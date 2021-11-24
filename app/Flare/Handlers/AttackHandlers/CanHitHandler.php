<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class CanHitHandler {

    private $attackExtraActionHandler;

    private $characterInformationBuilder;

    public function __construct(AttackExtraActionHandler $attackExtraActionHandler, CharacterInformationBuilder $characterInformationBuilder) {
        $this->attackExtraActionHandler    = $attackExtraActionHandler;
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function canHit($attacker, $defender, bool $isVoided = false): bool {

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

        $needToHit = 100 - 100 * $toHit;

        return rand(1, 100) > $needToHit;
    }

    public function canCast($attacker, $defender, bool $isVoided = false): bool {

        $accuracyBonus = $this->fetchCastingAccuracyBonus($attacker);
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

            $stat = $isVoided ? $defender->focus : $this->characterInformationBuilder->statMod('focus');

            $toHit = $this->toHitCalculation($attacker->focus, $stat, $accuracyBonus, $dodgeBonus);
        }

        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            $stat = $isVoided ? $attacker->{$attacker->class->to_hit_stat} :
                $this->characterInformationBuilder->statMod($attacker->class->to_hit_stat);

            $toHit = $this->toHitCalculation($stat, $defender->focus, $accuracyBonus, $dodgeBonus);
        }

        if ($toHit > 1.0) {
            return true;
        }

        $needToHit = 100 - 100 * $toHit;

        return rand(1, 100) > $needToHit;
    }

    /**
     * Fetch the accuracy bonus of the attacker.
     *
     * @param $attacker
     * @return float
     */
    protected function fetchAccuracyBonus($attacker): float {

        if ($attacker instanceof  Monster  || $attacker instanceof \stdClass) {
            return $attacker->accuracy;
        }

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

        if ($defender instanceof Monster || $defender instanceof \stdClass) {
            return $defender->dodge;
        }

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

    protected function fetchCastingAccuracyBonus($defender) {
        if ($defender instanceof Monster || $defender instanceof \stdClass) {
            return $defender->casting_accuracy;
        }

        $bonus    = $defender->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                ->where('game_skills.name', 'Casting Accuracy');
        })->first();

        if (is_null($bonus)) {
            $bonus = 0.0;
        } else {
            $bonus = $bonus->skill_bonus;
        }

        return $bonus;
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
        if ($dex > 2000000000) {
            return 1.0;
        }

        if ($dodge >= 1.0) {
            return 1.0;
        }

        $enemyDex  = $dex / 2000000000;
        $toHit     = $toHit / 2000000000;
        $hitChance = ($toHit + $accuracy);

        $enemyDodgeChance = $enemyDex + $dodge;

        if ($enemyDodgeChance > $hitChance) {
            return $enemyDodgeChance - $hitChance;
        }

        return 1.0;
    }
}