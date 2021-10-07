<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
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

    public function entrancedEnemy($attacker, $defender): bool {
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
                        $message          = 'The enemy is resists your entrancing enchantments!';
                        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
                        return false;
                    }

                    $message          = 'The enemy is dazed by your enchantments!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    return true;
                } else {
                    $message          = 'The enemy is resists your entrancing enchantments!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    return false;
                }
            }
        }

        return false;
    }
}
