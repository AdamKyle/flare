<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;

class SetupFightHandler {

    use CreateBattleMessages;

    private $battleLogs = [];

    private $attackType = null;

    private $defender   = null;

    private $processed  = false;

    private $monsterDevoided = false;

    private $monsterVoided   = false;

    private $characterInformationBuilder;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function setUpFight($attacker, $defender) {


        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            if ($this->devoidEnemy($attacker)) {
                $message = 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!';

                $this->battleLogs = $this->addMessage($message, 'action-fired', $this->battleLogs);

                $this->monsterDevoided = true;
            }

            if ($this->voidedEnemy($attacker)) {
                $message = 'The light of the heavens shines through this darkness. The enemy is voided!';

                $this->battleLogs = $this->addMessage($message, 'action-fired', $this->battleLogs);

                $this->monsterVoided = true;
            }
        }

//        if ($defender instanceof Monster && !$this->monsterDevoided) {
//            if ($this->voidedEnemy($defender)) {
//                $message = $defender->name . ' has voided your enchantments! You feel much weaker!';
//
//                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);
//
//                $this->attackType = 'voided_';
//            }
//        }

        // Only do this once per fight and if you are not voided.
        if (is_null($this->attackType) && !$this->processed) {
            if ($attacker instanceof Character && is_null($this->attackType)) {
                $this->defender = $this->reduceEnemyStats($defender);
            }
        }

        $this->defender = $defender;

        $this->processed = true;
    }

    public function getAttackType(): ?string {
        return $this->attackType;
    }

    public function getIsMonsterDevoided(): bool {
        return $this->monsterDevoided;
    }

    public function getIsMonsterVoided(): bool {
        return $this->monsterVoided;
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function reset() {
        $this->battleLogs = [];
        $this->attackType = null;
        $this->defender   = null;
    }

    public function getModifiedDefender(): Monster {
        return $this->defender;
    }

    protected function voidedEnemy($defender) {

        if ($defender instanceof Character) {
            $devouringLight = $this->characterInformationBuilder->setCharacter($defender)->getDevouringLight();
        } else {
            $devouringLight = $defender->devouring_light_chance;
        }

        if ($devouringLight >= 1) {
            return true;
        }

        $dc = 100 - 100 * $devouringLight;

        return rand(1, 100) > $dc;
    }

    protected function devoidEnemy($attacker) {
        if ($attacker->devouring_darkeness >= 1) {
            return true;
        }

        $dc = 100 - 100 * $attacker->devouring_darkeness;

        return rand(1, 100) > $dc;
    }

    protected function reduceEnemyStats($defender) {
        $affix = $this->characterInformationBuilder->findPrefixStatReductionAffix();

        if (!is_null($affix)) {
            $dc    = 100 - $defender->affix_resistance;

            if ($dc <= 0 || rand(1, 100) > $dc) {
                $message = 'Your enemy laughs at your attempt to make them week fails.';

                $battleLogs       = $this->addMessage($message, 'info-damage', $this->battleLogs);
                $this->battleLogs = [...$this->battleLogs, ...$battleLogs];

                return $defender;
            }

            $defender->str   = $defender->str - ($defender->str * $affix->str_reduction);
            $defender->dex   = $defender->dex - ($defender->dex * $affix->dex_reduction);
            $defender->int   = $defender->int - ($defender->int * $affix->int_reduction);
            $defender->dur   = $defender->dur - ($defender->dur * $affix->dur_reduction);
            $defender->chr   = $defender->chr - ($defender->chr * $affix->chr_reduction);
            $defender->agi   = $defender->agi - ($defender->agi * $affix->agi_reduction);
            $defender->focus = $defender->focus - ($defender->focus * $affix->focus_reduction);

            $stats = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

            for ($i = 0; $i < count($stats); $i++) {
                $iteratee = $stats[$i] . '_reduction';
                $sumOfReductions = $this->characterInformationBuilder->findSuffixStatReductionAffixes()->sum($iteratee);

                $defender->{$stats[$i]} = $defender->{$stats[$i]} - ($defender->{$stats[$i]} * $sumOfReductions);
            }

            $message = 'Your enemy sinks to their knees in agony as you make them weaker.';

            $battleLogs       = $this->addMessage($message, 'info-damage', $this->battleLogs);
            $this->battleLogs = [...$this->battleLogs, ...$battleLogs];
        }

        return $defender;
    }
}