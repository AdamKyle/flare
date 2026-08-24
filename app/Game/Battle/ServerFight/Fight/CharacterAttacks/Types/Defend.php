<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;

class Defend extends BattleBase
{
    protected array $attackData;

    protected bool $isVoided;

    private Entrance $entrance;

    private CanHit $canHit;

    private SecondaryAttacks $secondaryAttacks;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, ChanceCalculator $chanceCalculator, RandomNumberGenerator $randomNumberGenerator, Entrance $entrance, CanHit $canHit, SecondaryAttacks $secondaryAttacks, SpecialAttacks $specialAttacks)
    {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);

        $this->entrance = $entrance;
        $this->canHit = $canHit;
        $this->secondaryAttacks = $secondaryAttacks;
        $this->specialAttacks = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): Defend
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_defend' : 'defend');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function defend(Character $character, ServerMonster $serverMonster): Defend
    {

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

    public function resetMessages()
    {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    private function secondaryAttack(Character $character, ?ServerMonster $monster = null, float $affixReduction = 0.0)
    {
        if (! $this->isVoided) {

            $this->secondaryAttacks->setMonsterHealth($this->monsterHealth);
            $this->secondaryAttacks->setCharacterHealth($this->characterHealth);
            $this->secondaryAttacks->setAttackData($this->attackData);

            $this->secondaryAttacks->affixLifeStealingDamage($character, $monster, $affixReduction);
            $this->secondaryAttacks->affixDamage($character, $monster, $affixReduction);
            $this->secondaryAttacks->ringDamage();

            $this->mergeMessages($this->secondaryAttacks->getMessages());
            $this->secondaryAttacks->clearMessages();
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }

        $this->classSpecialtyDamage();

        $this->vampireSpecial($character, $this->attackData);
    }

    private function vampireSpecial(Character $character, array $attackData)
    {
        if ($character->classType()->isVampire()) {
            $this->specialAttacks
                ->setCharacterHealth($this->characterHealth)
                ->setMonsterHealth($this->monsterHealth)
                ->vampireThirst($character, $attackData);

            $this->characterHealth = $this->specialAttacks->getCharacterHealth();
            $this->monsterHealth = $this->specialAttacks->getMonsterHealth();

            $this->mergeMessages($this->specialAttacks->getMessages());

            $this->specialAttacks->clearMessages();
        }
    }

    private function classSpecialtyDamage()
    {
        $special = $this->attackData['special_damage'];

        if (empty($special)) {
            return;
        }

        if ($special['required_attack_type'] === $this->attackData['attack_type']) {
            $this->monsterHealth -= $special['damage'];

            $this->addMessage('Your class special: '.$special['name'].' fires off and you do: '.number_format($special['damage']).' damage to the enemy!', 'player-action');
        }
    }
}
