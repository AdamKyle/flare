<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Adventures\Traits\CreateBattleMessages;

class SetupFightHandler {

    use CreateBattleMessages;

    private $battleLogs = [];

    private $attackType = null;

    private $defender   = null;

    private $processed  = false;

    private $monsterDevoided = false;

    private $monsterVoided   = false;

    private $characterDmgDeduction = 0.0;

    private $characterInformationBuilder;

    private $buildMonsterCacheService;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder, BuildMonsterCacheService $buildMonsterCacheService) {
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->buildMonsterCacheService    = $buildMonsterCacheService;
    }

    public function setUpFight($attacker, $defender) {

        $reduction = $attacker->map->gameMap->enemy_stat_bonus;

        $defender  = $this->getDefenderFromSpecialLocation($attacker, $defender);
        $defender  = $this->applyEnemyStatIncrease($defender, $reduction);

        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            if (!is_null($reduction)) {
                $this->characterDmgDeduction = $reduction;
            }

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

        if ($defender instanceof Monster && !$this->monsterDevoided) {
            if ($this->voidedEnemy($defender)) {
                $message = $defender->name . ' has voided your enchantments! You feel much weaker!';

                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

                $this->attackType = 'voided_';
            }
        }

        // Only do this once per fight and if you are not voided.
        if (is_null($this->attackType) && !$this->processed) {
            if ($attacker instanceof Character && is_null($this->attackType)) {
                $defender = $this->reduceEnemyStats($defender);

                $defender = $this->reduceEnemySkills($defender);

                $defender = $this->reduceEnemyResistances($defender);
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

    public function getCharacterDamageReduction(): float {
        return $this->characterDmgDeduction;
    }

    public function reset() {
        $this->battleLogs      = [];
        $this->attackType      = null;
        $this->defender        = null;
        $this->processed       = false;
        $this->monsterDevoided = false;
        $this->monsterVoided   = false;
    }

    public function getModifiedDefender(): Monster|\stdClass {
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

    protected function getDefenderFromSpecialLocation($attacker, $defender) {
        $location = Location::where('x', $attacker->x_position)
                            ->where('y', $attacker->y_position)
                            ->where('game_map_id', $attacker->map->game_map_id)
                            ->first();

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type)) {
                $defender = json_decode(json_encode($this->buildMonsterCacheService->fetchMonsterFromCache($location->name, $defender->name)));
            }
        }

        return $defender;
    }

    protected function devoidEnemy($attacker) {
        $devouringDarknessChance = $this->characterInformationBuilder->setCharacter($attacker)->getDevouringDarkness();

        if ($devouringDarknessChance >= 1) {
            return true;
        }

        $dc = 100 - 100 * $devouringDarknessChance;

        return rand(1, 100) > $dc;
    }

    protected function reduceEnemyStats($defender) {
        $affix = $this->characterInformationBuilder->findPrefixStatReductionAffix();

        if (!is_null($affix)) {
            $dc    = 100 - $defender->affix_resistance;

            if ($dc <= 0 || rand(1, 100) > $dc) {
                $message = 'Your enemy laughs at your attempt to make them weak fails.';

                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

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

                if ($defender->{$stats[$i]} < 0.0) {
                    $defender->{$stats[$i]} = 0;
                }
            }

            $message = 'Your enemy sinks to their knees in agony as you make them weaker.';

            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
        }

        return $defender;
    }

    protected function reduceEnemySkills($defender) {
        $skillReduction = $this->characterInformationBuilder->getBestSkillReduction();

        if ($skillReduction > 0.0) {
            $defender->accuracy           = $defender->accuracy - $skillReduction;
            $defender->casting_accuracy   = $defender->casting_accuracy - $skillReduction;
            $defender->criticality        = $defender->criticality - $skillReduction;
            $defender->dodge              = $defender->dodge - $skillReduction;

            if ($defender->accuracy < 0.0) {
                $defender->accuracy = 0.0;
            }

            if ($defender->casting_accuracy < 0.0) {
                $defender->casting_accuracy = 0.0;
            }

            if ($defender->criticality < 0.0) {
                $defender->criticality = 0.0;
            }

            if ($defender->dodge < 0.0) {
                $defender->dodge = 0.0;
            }

            $message = 'Your enemy stumbles around confused as you reduce their chances at life!';

            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
        }

        return $defender;
    }

    protected function reduceEnemyResistances($defender) {
        $reduction = $this->characterInformationBuilder->getBestResistanceReduction();

        if ($reduction > 0.0) {
            $defender->spell_evasion        = $defender->spell_evasion - $reduction;
            $defender->artifact_annulment   = $defender->artifact_annulment - $reduction;
            $defender->affix_resistance     = $defender->affix_resistance - $reduction;

            if ($defender->spell_evasion < 0.0) {
                $defender->spell_evasion = 0.0;
            }

            if ($defender->artifact_annulment < 0.0) {
                $defender->artifact_annulment = 0.0;
            }

            if ($defender->affix_resistance < 0.0) {
                $defender->affix_resistance = 0.0;
            }

            $message = 'The enemy looks in awe at the shiny artifacts. They seem less resistant to their allure then before!';

            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
        }

        return $defender;
    }

    protected function applyEnemyStatIncrease($defender, ?float $increaseBy = null) {
        if (is_null($increaseBy)) {
            return $defender;
        }

        $stats = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

        for ($i = 0; $i < count($stats); $i++) {
            $defender->{$stats[$i]} = $defender->{$stats[$i]} + $defender->{$stats[$i]} * $increaseBy;
        }

        return $defender;
    }
}