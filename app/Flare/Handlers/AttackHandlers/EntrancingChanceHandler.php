<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;

class EntrancingChanceHandler {

    use CreateBattleMessages;

    private $characterInformationBuilder;

    private $battleLogs = [];

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function setCharacter($attacker) {
        return $this->characterInformationBuilder->setCharacter($attacker);
    }

    public function getBattleLogs(): array {
        return $this->battleLogs;
    }

    public function entrancedEnemy($attacker, $defender, bool $isDefenderVoided = false): bool {
        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->setCharacter($attacker);

            $chance = $this->characterInformationBuilder->getEntrancedChance();

            if ($chance > 0.0) {

                $canEntrance = rand (1, 100) > (100 - (100 * $chance));

                if ($this->characterInformationBuilder->canAffixesBeResisted() || $canEntrance) {
                    $message          = 'The enemy is dazed by your enchantments!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    return true;
                } else if ($canEntrance) {
                    $dc = 100 - (100 * $defender->affix_resistance);

                    if ($dc <= 0 || rand(1, 100) > $dc) {
                        $message = 'The enemy is resists your entrancing enchantments!';
                        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
                        return false;
                    }

                    $message = 'The enemy is dazed by your enchantments!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    return true;
                } else {
                    $message = 'The enemy is resists your entrancing enchantments!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    return false;
                }
            }
        }

        if ($defender instanceof  Character) {
            if ($this->canMonsterEntrance($attacker, $defender, $isDefenderVoided)) {
                $message = $attacker->name .  ' has trapped you in a trance like state with their enchantments!';
                $this->battleLogs = $this->addMessage($message, 'enemy-fired-action', $this->battleLogs);

                return true;
            }
        }

        $message = 'You resist the alluring entrancing enchantments on your enemy!';
        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

        return false;
    }

    protected function canMonsterEntrance($attacker, $defender, bool $isDefenderVoided = false): bool {
        $baseDC = 100;
        $focus  = 100;

        if ($defender->classType()->isHeretic() || $defender->classType()->isProphet()) {
            if ($isDefenderVoided) {
                $baseDC = $defender->focus * 0.05;
                $focus  = $baseDC;
            } else {
                $baseDC = $this->characterInformationBuilder->setCharacter($defender)->statMod('focus') * 0.05;
                $focus  = $baseDC;
            }
        }

        $baseDC -= $baseDC * $attacker->entrancing_chance;

        if ($baseDC <= 0) {
            return true;
        }

        return rand(1, $focus) > $baseDC;
    }
}
