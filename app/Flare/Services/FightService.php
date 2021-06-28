<?php
namespace App\Flare\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class FightService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Monster $monster
     */
    private $monster;

    /**
     * @var array $logInformation
     */
    private $logInformation = [];

    /**
     * @var int $currentCharacterhealth
     */
    private $currentCharacterHealth = 0;

    /**
     * @var int $currentMonsterHealth
     */
    private $currentMonsterHealth   = 0;

    /**
     * @var CharacterInformationBuilder $characterInformation
     */
    private $characterInformation;

    /**
     * Used to stop adventures from going on too long.
     *
     * @var int $counter
     */
    private $counter = 0;

    /**
     * used to stop adventures from going on too long.
     *
     * @var bool $tookTooLong
     */
    private $tookTooLong = false;

    /**
     * @param Character $character
     * @param Monster $monster
     * @return void
     */
    public function __construct(Character $character, Monster $monster, int $monsterCurrentHealth = null, int $characterHealth = null) {
        $this->character = $character;
        $this->monster   = $monster;

        // Set character information
        if (is_null($characterHealth)) {
            $this->characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);
            $this->currentCharacterHealth = $this->characterInformation->buildHealth();
        } else {
            $this->currentCharacterHealth = $characterHealth;
        }

        // Set monster information
        if (is_null($monsterCurrentHealth)) {
            $healthRange = explode('-', $this->monster->health_range);
            $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;
        } else {
            $this->currentMonsterHealth = $monsterCurrentHealth;
        }
    }

    /**
     * Get the log information.
     *
     * @return array
     */
    public function getLogInformation(): array {
        return $this->logInformation;
    }

    /**
     * Reset the log information
     *
     * @return void
     */
    public function resetLogInfo(): void {
        $this->logInformation = [];
    }

    /**
     * Get the monster.
     *
     * @return Monster
     */
    public function getMonster(): Monster {
        return $this->monster;
    }

    /**
     * Is the character dead?
     *
     * @return bool
     */
    public function isCharacterDead(): bool {
        return $this->currentCharacterHealth <= 0;
    }

    /**
     * Is the monster dead?
     *
     * @return bool
     */
    public function isMonsterDead(): bool {
        return $this->currentMonsterHealth <= 0;
    }

    /**
     * Did the adventure take too long?
     *
     * @return bool
     */
    public function tooLong(): bool {
        return $this->tookTooLong;
    }

    /**
     * Get current monster health.
     *
     * @return int
     */
    public function getRemainingMonsterHealth(): int {
        return $this->currentMonsterHealth;
    }

    /**
     * Get current character health.
     *
     * @return int
     */
    public function getRemainingCharacterHealth(): int {
        return $this->currentCharacterHealth;
    }

    /**
     * Attack the enemy.
     *
     * This attack method mirrors the one on the client side.
     *
     * @param mixed $attacker | Character or Monster
     * @param mixed $defender | Character or Monster
     * @return void
     */
    public function attack($attacker, $defender) {
        if ($this->isCharacterDead() || $this->isMonsterDead()) {

            if ($this->isMonsterDead()) {
                $this->logInformation[] = [
                    'attacker'   => $defender->name,
                    'defender'   => $attacker->name,
                    'message'    => $attacker->name . ' has been defeated!',
                    'is_monster' => $defender instanceOf Character ? false : true
                ];
            }

            return;
        }

        if ($this->counter >= 10) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'message'    => 'Floor took too long.',
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->tookTooLong = true;

            $this->counter = 0;

            return;
        }

        if (!$this->canHit($attacker, $defender)) {
            $messages   = $this->castSpell($attacker, $defender);
            $messages   = array_merge($messages, $this->useAtifacts($attacker, $defender));
            $messages[] = [$attacker->name . '(weapon) Missed!'];

            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'messages'   => $messages,
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->counter += 1;

            return $this->attack($defender, $attacker);
        }

        if ($this->blockedAttack($defender, $attacker)) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'message'    => $defender->name . ' blocked the attack!',
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->counter += 1;

            return $this->attack($defender, $attacker);
        }

        $messages          = $this->completeAttack($attacker, $defender);

        $this->counter     = 0;

        $this->tookTooLong = false;

        $this->logInformation[] = [
            'attacker'   => $attacker->name,
            'defender'   => $defender->name,
            'messages'   => $messages,
            'is_monster' => $attacker instanceof Character ? false : true
        ];

        $this->attack($defender, $attacker);
    }

    protected function canHit($attacker, $defender): bool {
        $accuracyBonus = $attacker->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                     ->where('game_skills.name', 'Accuracy');
        })->first()->skill_bonus;

        $dodgeBonus    = $defender->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                     ->where('game_skills.name', 'Dodge');
        })->first()->skill_bonus;

        if ($accuracyBonus < 1) {
            $accuracyBonus += 1;
        }

        if ($dodgeBonus < 1) {
            $dodgeBonus += 1;
        }

        $defenderDex      = $defender->dex;
        $defenderBaseStat = $defender->{$defender->damage_stat};

        $attackerDex      = $defender->dex;
        $attackerBaseStat = $defender->{$defender->damage_stat};

        if ($defender instanceof  Character) {
            $defenderDex      = $this->characterInformation->statMod('dex');
            $defenderBaseStat = $this->characterInformation->statMod($defender->damage_stat);
        }

        if ($attacker instanceof Character) {
            $attackerDex      = $this->characterInformation->statMod('dex');
            $attackerBaseStat = $this->characterInformation->statMod($defender->damage_stat);
        }

        $attack = $attackerBaseStat + round($attackerDex / 2) * $accuracyBonus;
        $dodge  = $defenderBaseStat + round($defenderDex / 2) * $dodgeBonus;

        return $attack > $dodge;
    }

    protected function blockedAttack($defender, $attacker): bool {
        $baseStat = $attacker->{$defender->damage_stat};
        $ac       = $defender->ac;

        if ($defender instanceof  Character) {
            $ac = $this->characterInformation->buildDefence();
        }

        if ($attacker instanceof Character) {
            $baseStat = $this->characterInformation->statMod($attacker->damage_stat);
        }

        return $ac > $baseStat;
    }

    protected function castSpell($attacker, $defender) {
        $messages = [];

        if ($attacker instanceof Character) {
            if ($this->characterInformation->hasDamageSpells()) {
                $messages[] = ['Your spells burst forward towards the enemy!'];
                $messages[] = $this->spellDamage($attacker, $defender);
            }

            $healFor = $this->characterInformation->buildHealFor();

            if ($healFor > 0) {
                $this->currentCharacterHealth = $healFor;

                $messages[] = ['Light floods your eyes as your wounds heal over for: ' . $healFor];
            }
        }

        if ($attacker->can_cast) {
            $messages[] = ['The enemy begins to cast their spells!'];

            $messages[] = $this->spellDamage($attacker, $defender);
        }

        return $messages;
    }

    protected function useAtifacts($attacker, $defender) {
        $messages = [];

        if ($attacker instanceOf Character) {
            if ($this->characterInformation->hasArtifacts()) {
                $messages[] = ['Your artifacts glow before the enemy!'];
                $messages[] = $this->artifactDamage($attacker, $defender);
            }
        }

        if ($defender instanceOf Character) {
            if ($defender->can_use_artifacts) {
                $messages[] = ['The enemies artifacts begin to glow ...'];
                $messages[] = $this->artifactDamage($attacker, $defender);
            }
        }

        return $messages;
    }

    protected function artifactDamage($attacker, $defender) {
        if ($attacker instanceof Character) {
            $artifactDamage = $this->characterInformation->getTotalArtifactDamage();
            $artifactDamage = $artifactDamage - ($artifactDamage * $defender->artifact_annulment);

            if ($artifactDamage > 0) {
                $health = ceil($this->currentMonsterHealth - $artifactDamage);

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentMonsterHealth = $health;

                return [
                    'Your artifacts hit the enemy for: ' . $artifactDamage,
                ];
            } else {
                return [
                    'Your artifacts have no effect ...'
                ];
            }
        }

        if ($defender instanceof Character){
            $artifactDamage = rand(1, $defender->max_artifact_damage);
            $artifactDamage = $artifactDamage - ($artifactDamage * $this->characterInformation->getTotalAnnulment());

            if ($artifactDamage > 0) {
                $health = $this->currentCharacterHealth - $artifactDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                return [
                    'The enemies artifacts lash out in intense energy doing: ' . $artifactDamage,
                ];
            } else {
                return [
                    'The enemies artifacts have no effect ...'
                ];
            }
        }
    }

    protected function spellDamage($attacker, $defender) {
        if ($attacker instanceof Character) {
            $spellDamage = $this->characterInformation->getTotalSpellDamage();
            $totalDamage = ceil($spellDamage - ($spellDamage * $defender->spell_evasion));

            if ($totalDamage > 0) {
                $health = $this->currentMonsterHealth - $totalDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentMonsterHealth = $health;

                return [
                    'Your spells hit the enemy for: ' . $totalDamage,
                ];
            } else {
                return [
                    'Your spells have no effect ...'
                ];
            }
        }

        if ($defender instanceof Character){
            $spellDamage = rand(1, $attacker->max_spell_damage);
            $totalDamage = $spellDamage - ($spellDamage * $this->characterInformation->getTotalSpellEvasion());

            if ($totalDamage > 0) {
                $health = $this->currentCharacterHealth - $totalDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                return [
                    'The enemies spells burst towards you, slamming into you for: ' . $totalDamage,
                ];
            } else {
                return [
                    'The enemies spells have no effect ...'
                ];
            }
        }
    }

    protected function completeAttack($attacker, $defender): array {
        $messages = [];

        if ($attacker instanceof Character) {

            $characterAttack = $this->characterInformation->buildAttack();

            $this->currentMonsterHealth -= $characterAttack;

            if ($this->characterInformation->hasAffixes()) {
                $messages[] = ['The enchantments on your equipment lash out at the enemy!'];
            }

            $messages = array_merge($messages, $this->castSpell($attacker, $defender));
            $messages = array_merge($messages, $this->useAtifacts($attacker, $defender));

            $messages[] = [$this->character->name . ' hit for (weapon): ' . number_format($characterAttack)];

        } else {
            $monsterAttack = $this->fetchMonsterAttack($attacker);

            $this->currentCharacterHealth -= $monsterAttack;

            $messages = array_merge($messages, $this->castSpell($attacker, $defender));
            $messages = array_merge($messages, $this->useAtifacts($attacker, $defender));

            $messages[] =  [$attacker->name . ' hit for ' . number_format($monsterAttack)];
        }

        return $messages;
    }

    protected function fetchMonsterAttack($attacker) {
        $attackRange = explode('-', $attacker->attack_range);

        return rand($attackRange[0], $attackRange[1]) + $attacker->{$attacker->damage_stat};
    }
}
