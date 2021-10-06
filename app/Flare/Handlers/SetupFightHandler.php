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

    private $defedner   = null;

    private $processed  = false;

    private $characterInformationBuilder;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function setUpFight($attacker, $defender) {
        if ($defender instanceof Character) {
            if ($this->isVoided($defender)) {
                $message = 'You voided your enemies enchantments. They feel much weaker!';

                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }

        if ($defender instanceof Monster) {
            if ($this->isVoided($defender)) {
                $message = $defender->name . ' has voided your enchantments! You feel much weaker!';

                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

                $this->attackType = 'voided';
            }
        }

        // Only do this once per fight.
        if (is_null($this->attackType) && !$this->processed) {
            if ($attacker instanceof Character) {
                $this->defender = $this->reduceEnemyStats();
            }
        }

        $this->processed = true;
    }

    public function getAttackType(): ?string {
        return $this->attackType;
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function getModifiedDefender(): array {
        return $this->defender;
    }

    protected function isVoided($defender) {
        $dc = 100 - 100 * $defender->devouring_light_chance;

        return rand(1, 100) > $dc;
    }

    protected function reduceEnemyStats($defender) {
        $affix = $this->characterInformation->findPrefixStatReductionAffix();

        if (!is_null($affix)) {
            $dc    = 100 - $defender->affix_resistance;

            if ($dc <= 0 || rand(1, 100) > $dc) {
                $message = 'Your enemy laughs at your attempt to make them week fails.';

                $battleLogs       = $this->addMessage($message, 'info-damage', $this->battleLogs);
                $this->battleLogs = [...$this->battleLogs, ...$battleLogs];

                return $defender;
            }

            $defender->str   = $this->monster->str - ($this->monster->str * $affix->str_reduction);
            $defender->dex   = $this->monster->dex - ($this->monster->dex * $affix->dex_reduction);
            $defender->int   = $this->monster->int - ($this->monster->int * $affix->int_reduction);
            $defender->dur   = $this->monster->dur - ($this->monster->dur * $affix->dur_reduction);
            $defender->chr   = $this->monster->chr - ($this->monster->chr * $affix->chr_reduction);
            $defender->agi   = $this->monster->agi - ($this->monster->agi * $affix->agi_reduction);
            $defender->focus = $this->monster->focus - ($this->monster->focus * $affix->focus_reduction);

            $stats = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

            for ($i = 0; $i < count($stats); $i++) {
                $iteratee = $stats[$i] . '_reduction';
                $sumOfReductions = $this->characterInformation->findSuffixStatReductionAffixes()->sum($iteratee);

                $defender->{$stats[$i]} = $defender->{$stats[$i]} - ($defender->{$stats[$i]} * $sumOfReductions);
            }

            $message = 'Your enemy sinks to their knees in agony as you make them weaker.';

            $battleLogs       = $this->addMessage($message, 'info-damage', $this->battleLogs);
            $this->battleLogs = [...$this->battleLogs, ...$battleLogs];
        }

        return $defender;
    }
}