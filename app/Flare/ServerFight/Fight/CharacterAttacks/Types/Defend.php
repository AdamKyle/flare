<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class Defend extends BattleBase {

    protected array $attackData;

    protected bool $isVoided;

    private Entrance $entrance;

    private CanHit $canHit;

    private SecondaryAttacks $secondaryAttacks;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SecondaryAttacks $secondaryAttacks, SpecialAttacks $specialAttacks) {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->secondaryAttacks   = $secondaryAttacks;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): Defend {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_defend' : 'defend');
        $this->isVoided   = $isVoided;

        return $this;
    }

    public function pvpDefend(Character $attacker, Character $defender): Defend {
        $this->entrance->attackerEntrancesDefender($attacker, $this->attackData, $this->isVoided);

        $this->mergeAttackerMessages($this->entrance->getAttackerMessages());
        $this->mergeDefenderMessages($this->entrance->getDefenderMessages());

        $this->characterCacheData->setCharacterDefendAc($attacker, $this->attackData['defence']);

        if ($this->entrance->isEnemyEntranced()) {

            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);

            return $this;
        }

        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);

        return $this;
    }

    public function defend(Character $character, ServerMonster $serverMonster): Defend {

        $this->entrance->playerEntrance($character, $serverMonster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        $this->characterCacheData->setCharacterDefendAc($character, $this->attackData['defence']);

        if ($this->entrance->isEnemyEntranced()) {
            $this->secondaryAttack($character, $serverMonster);

            return $this;
        }

        $this->secondaryAttack($character, $serverMonster);

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        if (!$this->isVoided) {

            $this->secondaryAttacks->setMonsterHealth($this->monsterHealth);
            $this->secondaryAttacks->setCharacterHealth($this->characterHealth);
            $this->secondaryAttacks->setAttackData($this->attackData);


            $this->secondaryAttacks->affixLifeStealingDamage($character, $monster, $affixReduction, $isPvp);
            $this->secondaryAttacks->affixDamage($character, $monster, $affixReduction, $isPvp);
            $this->secondaryAttacks->ringDamage($isPvp);

            if ($isPvp) {
                $this->mergeAttackerMessages($this->secondaryAttacks->getAttackerMessages());
                $this->mergeDefenderMessages($this->secondaryAttacks->getDefenderMessages());
            } else {
                $this->secondaryAttacks->mergeMessages($this->secondaryAttacks->getMessages());
            }

            $this->secondaryAttacks->clearMessages();

        } else {
            if ($isPvp) {
                $this->addAttackerMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            } else {
                $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            }
        }

        $this->classSpecialtyDamage($isPvp);

        $this->vampireSpecial($character, $this->attackData, $isPvp);
    }

    protected function vampireSpecial(Character $character, array $attackData, bool $isPvp = false) {
        if ($character->classType()->isVampire()) {
            $this->specialAttacks
                 ->setCharacterHealth($this->characterHealth)
                 ->setMonsterHealth($this->monsterHealth)
                 ->vampireThirst($character, $attackData, $isPvp);

            $this->characterHealth = $this->specialAttacks->getCharacterHealth();
            $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

            if (!$isPvp) {
                $this->mergeMessages($this->specialAttacks->getMessages());
            } else {
                $this->mergeAttackerMessages($this->specialAttacks->getAttackerMessages());
                $this->mergeDefenderMessages($this->specialAttacks->getDefenderMessages());
            }

            $this->specialAttacks->clearMessages();
        }
    }

    protected function classSpecialtyDamage(bool $isPvp = false) {
        $special = $this->attackData['special_damage'];

        if (empty($special)) {
            return;
        }

        if ($special['required_attack_type'] === $this->attackData['attack_type']) {
            $this->monsterHealth -= $special['damage'];

            $this->addMessage('Your class special: ' . $special['name'] . ' fires off and you do: ' . number_format($special['damage']) . ' damage to the enemy!', "player-action", $isPvp);

            if ($isPvp) {
                $this->addDefenderMessage('The enemy lashes out using one of their coveted skills (class special) to do:  ' . $special['damage'] . ' damage.', 'enemy-action');
            }
        }
    }
}
