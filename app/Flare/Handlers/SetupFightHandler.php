<?php

namespace App\Flare\Handlers;


use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Adventures\Traits\CreateBattleMessages;

class SetupFightHandler {

    use CreateBattleMessages;

    private $battleLogs            = [];

    private $attackType            = null;

    private $defender              = null;

    private $processed             = false;

    private $monsterDevoided       = false;

    private $monsterVoided         = false;

    private $characterDmgDeduction = 0.0;

    private $characterInformationBuilder;

    private $holyStacks;

    private $buildMonsterCacheService;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder, HolyStacks $holyStacks, BuildMonsterCacheService $buildMonsterCacheService) {
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->holyStacks                  = $holyStacks;
        $this->buildMonsterCacheService    = $buildMonsterCacheService;
    }

    public function setUpFight($attacker, $defender) {

        $enemyStateBonus = $attacker->map->gameMap->enemy_stat_bonus;
        $damageReduction = $attacker->map->gameMap->character_attack_reduction;

        $defender  = $this->getDefenderFromSpecialLocation($attacker, $defender);
        $defender  = $this->applyEnemyStatIncrease($defender, $enemyStateBonus);

        switch($defender->gameMap->name) {
            case 'Hell':
            case 'Purgatory':
                $this->monsterVoidsPlayer($defender, $attacker);
                break;
            default:
                $this->playerVoidsMonster($defender, $attacker);
        }

        if (!is_null($damageReduction)) {
            $this->characterDmgDeduction = $damageReduction;
        }

        // Only do this once per fight and if you are not voided.
        if (is_null($this->attackType) && !$this->processed) {
            $defender = $this->reduceEnemyStats($defender);

            $defender = $this->reduceEnemySkills($defender);

            $defender = $this->reduceEnemyResistances($defender);
        }

        $this->defender = $defender;

        $this->processed = true;
    }

    protected function monsterVoidsPlayer($defender, $attacker) {
        $characterIsDevoided = false;
        $monsterIsDevoided   = false;

        if ($this->canMonsterDevoidCharacter($defender, $attacker)) {
            $message = $defender->name . ' has devoided your voidance! You feel fear starting to build up.';

            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $characterIsDevoided = true;
        }

        if ($this->canCharacterDevoidEnemy($attacker) && !$characterIsDevoided) {
            $message           = 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!';

            $this->battleLogs  = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $monsterIsDevoided = true;
        }

        if ($this->canMonsterVoidCharacter($defender, $attacker) && !$monsterIsDevoided) {
            $message          = $defender->name . ' has voided your enchantments! You feel much weaker!';

            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $this->attackType = 'voided_';
        }

        if ($this->canCharacterVoidEnemy($attacker) && $this->attackType !== 'voided_') {
            $message             = 'The light of the heavens shines through this darkness. The enemy is voided!';

            $this->battleLogs    = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $this->monsterVoided = true;
        }
    }

    protected function playerVoidsMonster($defender, $attacker) {
        $characterIsDevoided = false;
        $monsterIsDevoided   = false;

        if ($this->canCharacterDevoidEnemy($attacker)) {
            $message           = 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!';

            $this->battleLogs  = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $monsterIsDevoided = true;
        }

        if ($this->canMonsterDevoidCharacter($defender, $attacker) && !$monsterIsDevoided) {
            $message = $defender->name . ' has devoided your voidance! You feel fear starting to build up.';

            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $characterIsDevoided = true;
        }

        if ($this->canCharacterVoidEnemy($attacker) && !$characterIsDevoided) {
            $message             = 'The light of the heavens shines through this darkness. The enemy is voided!';

            $this->battleLogs    = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $this->monsterVoided = true;
        }

        if ($this->canMonsterVoidCharacter($defender, $attacker) && !$monsterIsDevoided) {
            $message          = $defender->name . ' has voided your enchantments! You feel much weaker!';

            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $this->attackType = 'voided_';
        }

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

    protected function canMonsterVoidCharacter($defender, $attacker): bool {
        $devouringLight = $defender->devouring_light_chance;
        $resistance     = $this->holyStacks->fetchDevouringResistanceBonus($attacker);

        $devouringLight -= $resistance;

        if ($devouringLight > 1) {
            return true;
        }

        $dc = 100 - (100 * $devouringLight);

        return rand(1, 100) > $dc;
    }

    protected function canMonsterDevoidCharacter($defender, $attacker): bool {

        $devouringDarkness = $defender->devouring_darkness_chance;

        $resistance        = $this->holyStacks->fetchDevouringResistanceBonus($attacker);

        $devouringDarkness -= $resistance;

        if ($devouringDarkness > 1) {
            return true;
        }

        $dc = 100 - (100 * $devouringDarkness);

        return rand(1, 100) > $dc;
    }

    protected function canCharacterVoidEnemy($defender) {

        if ($defender instanceof Character) {
            $devouringLight = $this->characterInformationBuilder->setCharacter($defender)->getDevouringLight();
        } else {
            $devouringLight = $defender->devouring_light_chance;
        }

        if ($devouringLight >= 1) {
            return true;
        }

        $dc   = 100 - 100 * $devouringLight;
        $roll = rand(1, 100);

        return $roll > $dc;
    }

    protected function canCharacterDevoidEnemy($attacker) {
        $devouringDarknessChance = $this->characterInformationBuilder->setCharacter($attacker)->getDevouringDarkness();

        if ($devouringDarknessChance >= 1) {
            return true;
        }

        $dc   = 100 - 100 * $devouringDarknessChance;
        $roll = rand(1, 100);

        return $roll > $dc;
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


    protected function reduceEnemyStats($defender) {
        $prefix                 = $this->characterInformationBuilder->findPrefixStatReductionAffix();
        $affixesAreIrresistible = $this->characterInformationBuilder->canAffixesBeResisted();

        if (!is_null($prefix)) {

            if (!$this->canReduce($defender, $prefix, $affixesAreIrresistible)) {
                return $defender;
            }

            $defender->str   = $defender->str - ($defender->str * $prefix->str_reduction);
            $defender->dex   = $defender->dex - ($defender->dex * $prefix->dex_reduction);
            $defender->int   = $defender->int - ($defender->int * $prefix->int_reduction);
            $defender->dur   = $defender->dur - ($defender->dur * $prefix->dur_reduction);
            $defender->chr   = $defender->chr - ($defender->chr * $prefix->chr_reduction);
            $defender->agi   = $defender->agi - ($defender->agi * $prefix->agi_reduction);
            $defender->focus = $defender->focus - ($defender->focus * $prefix->focus_reduction);
        }

        return $this->reduceSuffixStatsOnEnemies($defender, $affixesAreIrresistible);
    }

    protected function canReduce($defender, ItemAffix $affix = null, bool $irresistible = false): bool {

        if ($irresistible) {
            return true;
        }

        if (is_null($affix)) {
            $dc    = 50 + 50 * ($defender->affix_resistance);
        } else {
            $dc    = 50 + 50 * ($defender->affix_resistance - $affix->resistance_reduction);
        }

        if ($dc > 100) {
            $dc = 99;
        }

        if (rand(1, 100) < $dc) {
            $message = 'Your enemy laughs at your attempt to make them weak fails.';

            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

            return false;
        }

        return true;
    }

    protected function reduceSuffixStatsOnEnemies($defender, bool $irresistible = false) {
        $stats               = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

        for ($i = 0; $i < count($stats); $i++) {
            $iteratee = $stats[$i] . '_reduction';

            $affix = $this->characterInformationBuilder->findSuffixStatReductionAffixes()->whereNotNull($iteratee)->sortBy([
                'resistance_reduction' => 'asc'
            ])->first();

            if (!$this->canReduce($defender, $affix, $irresistible)) {
                return $defender;
            }

            $sumOfReductions = $this->characterInformationBuilder->findSuffixStatReductionAffixes()->sum($iteratee);
            $defender->{$stats[$i]} = $defender->{$stats[$i]} - ($defender->{$stats[$i]} * $sumOfReductions);

            if ($defender->{$stats[$i]} < 0.0) {
                $defender->{$stats[$i]} = 0;
            }
        }

        $message = 'Your enemy sinks to their knees in agony as you make them weaker.';

        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

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

            $message = 'The enemy looks in awe at the shiny artifacts. They seem less resistant to their allure than before!';

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
