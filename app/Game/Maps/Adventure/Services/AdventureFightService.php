<?php

namespace App\Game\Maps\Adventure\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Game\Core\Exceptions\CharacterIsDeadException;
use App\Game\Core\Exceptions\MonsterIsDeadException;

class AdventureFightService {

    private $character;

    private $adventure;

    private $monster;

    private $logInformation = [];

    private $currentCharacterHealth = 0;

    private $currentMonsterHealth   = 0;

    private $characterInformation;

    public function __construct(Character $character, Adventure $adventure) {

        $this->characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        
        $this->character     = $character;
        $this->adventure     = $adventure;

        $this->currentCharacterHealth = $this->characterInformation->buildHealth();
    }

    public function processBattle() {
        $this->monster = $this->adventure->monsters()->inRandomOrder()->first();

        $healthRange = explode('-', $this->monster->health_range);

        $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        $this->attack($this->character, $this->monster);
    }

    public function getLogInformation() {
        return $this->logInformation;
    }

    public function getMonster() {
        return $this->monster;
    }

    protected function attack($attacker, $defender) {
        if ($this->isCharacterDead()) {
            throw new CharacterIsDeadException();
        }

        if ($this->isMonsterDead()) {
            throw new MonsterIsDeadException();
        }

        if (!$this->canHit($attacker, $defender)) {
            $this->logInformation[] = [
                'attacker' => $attacker->name,
                'defender' => $defender->name,
                'message'  => $attacker->name . ' Missed!',
            ];

            return $this->attack($defender, $attacker);
        } 

        if ($this->blockedAttack($defender, $attacker)) {
            $this->logInformation[] = [
                'attacker' => $attacker->name,
                'defender' => $defender->name,
                'message'  => $defender->name . ' blocked the attack!',
            ];

            return $this->attack($defender, $attacker);
        }

        $this->logInformation[] = [
            'attacker' => $attacker->name,
            'defender' => $defender->name,
            'messages' => $this->completeAttack($attacker, $defender),
        ];
        
        $this->attack($defender, $attacker);
    }

    protected function canHit($attacker, $defender): bool {
        $accuracyBonus = $attacker->skills->where('name', 'Accuracy')->first()->skill_bonus;
        $dodgeBonus    = $defender->skills->where('name', 'Dodge')->first()->skill_bonus;

        return (rand(1, 20) * (1 + $accuracyBonus)) > ($defender->ac * (1 + $dodgeBonus));
    }

    protected function blockedAttack($defender, $attacker): bool {
        $accuracyBonus = $attacker->skills->where('name', 'Accuracy')->first()->skill_bonus;
        $ac            = $defender->ac;

        if ($defender instanceof Character) {
            $ac = $this->characterInformation->buildDefence();
        }

        return (rand(1, 20) * (1 + $accuracyBonus)) > $ac;
    }

    protected function completeAttack($attacker, $defender): array {
        $messages = [];

        if ($attacker instanceof Character) {
            $characterAttack = $this->characterInformation->buildAttack();

            $this->currentMonsterHealth -= $characterAttack;

            if ($this->characterInformation->hasArtifacts()) {
                $messages = ['Your artifacts glow before the enemy!'];
            }

            if ($this->characterInformation->hasAffixes()) {
                $messages = ['The enchantments on your equipment lash out at the enemy!'];
            }

            if ($this->characterInformation->hasDamageSpells()) {
                $messages = ['Your spells burst forward towards the enemy!'];
            }

            $healFor = $this->characterInformation->buildHealFor();

            if ($healFor > 0) {
                if ($this->currentCharacterHealth <= ($this->currentCharacterHealth * 0.75)) {
                    $this->currentCharacterHealth = $healFor;

                    $messages = ['Light floods your eyes as your wounds heal over.'];
                }
            }

            $messages = [$this->character->name . ' hit for ' . $characterAttack];

            return $messages;
        }

        $monsterAttack = $this->fetchMonsterAttack($attacker);
        
        $this->currentCharacterHealth -= $monsterAttack;

        return [
            $attacker->name . ' hit for ' . $monsterAttack,
        ];
    }

    protected function isCharacterDead(): bool {
        return $this->currentCharacterHealth <= 0;
    }

    protected function isMonsterDead(): bool {
        return $this->currentMonsterHealth <= 0;
    }

    protected function fetchMonsterAttack($attacker) {
        $attackRange = explode('-', $attacker->attack_range);

        return rand($attackRange[0], $attackRange[1]) + $attacker->{$attacker->damage_stat};
    }
}