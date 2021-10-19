<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;

class ItemHandler {

    use CreateBattleMessages;

    private $battleLogs = [];

    private $characterInformationBuilder;

    private $currentMonsterHealth;

    private $currentCharacterHealth;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function setMonsterHealth(int $monsterHealth): ItemHandler {
        $this->currentMonsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): ItemHandler {
        $this->currentCharacterHealth = $characterHealth;

        return $this;
    }

    public function useItems($attacker, $defender, bool $voided = false) {
        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            if ($attacker->classType()->isVampire() && !$voided) {
                $canResist  = $this->characterInformationBuilder->canAffixesBeResisted();
                $damage     = $this->characterInformationBuilder->findLifeStealingAffixes(true);

                $this->useLifeStealingAffixes($defender, $damage, $canResist);
            }
        }

        $this->useAffixes($attacker, $defender, $voided);
        $this->useArtifacts($attacker, $defender, $voided);
        $this->useRings($attacker, $voided);
    }

    public function getMonsterHealth(): int {
        return $this->currentMonsterHealth;
    }

    public function getCharacterHealth(): int {
        return $this->currentCharacterHealth;
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function resetLogs() {
        $this->battleLogs = [];
    }

    /**
     * Let your affixes fire off.
     *
     * @param $attacker
     * @param $defender
     */
    protected function useAffixes($attacker, $defender, bool $voided = false) {

        if ($attacker instanceof Character && !$voided) {
            $totalDamage = $this->characterInformationBuilder->getTotalAffixDamage();
            $cantResist  = $this->characterInformationBuilder->canAffixesBeResisted() || $this->characterInformationBuilder->hasIrresistibleAffix();

            if ($totalDamage <= 0) {
                return;
            }

            if ($cantResist) {
                $message = 'The enemy cannot resist your enchantments! They are so glowy!';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $totalDamage += $this->characterInformationBuilder->getTotalAffixDamage($voided);
            } else {
                $dc = 100 - (100 * $defender->affix_resistance);

                if ($dc <= 0 || rand(1, 100) > $dc) {
                    $message = 'Your damaging enchantments (resistible) have been resisted.';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
                } else {
                    $totalDamage += $this->characterInformationBuilder->getTotalAffixDamage($voided);
                }
            }

            $monsterNewHealth = $this->currentMonsterHealth - $totalDamage;

            if ($monsterNewHealth < 0) {
                $this->currentMonsterHealth = 0;
            } else {
                $this->currentMonsterHealth = $monsterNewHealth;
            }

            $cantResistMessage = 'cowers. (non resisted dmg): ';

            if ($cantResist) {
                $cantResistMessage = 'cowers: ';
            }

            $message = 'Your enchantments glow with rage. Your enemy ' . $cantResistMessage . $totalDamage;
            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
        }
    }

    public function useLifeStealingAffixes($defender, float $damage, bool $canResist = true) {
        $totalDamage = ceil($this->currentMonsterHealth * $damage);

        if ($totalDamage > 0) {
            if ($canResist) {
                $message = 'The enemies blood flows through the air and gives you life: ' . number_format($totalDamage);

                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $this->currentMonsterHealth   -= $totalDamage;
                $this->currentCharacterHealth += $totalDamage;
            } else {
                $dc = 100 - (100 * $defender->affix_resistance);

                if ($dc <= 0 || rand(1, 100) > $dc) {
                    $message = 'The enemy resists your attempt to steal it\'s life.';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
                } else {
                    $message = 'The enemies blood flows through the air and gives you life: ' . number_format($totalDamage);
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                    $this->currentMonsterHealth   -= $totalDamage;
                    $this->currentCharacterHealth += $totalDamage;
                }
            }
        }
    }

    /**
     * Casts your healing spells.
     *
     * Monsters do not have healing spells, but players do.
     *
     * @param $defender
     * @return array
     * @throws \Exception
     */
    protected function castHealingSpell($defender) {
        $messages = [];

        if ($defender instanceof Character) {
            $healFor = $this->characterInformationBuilder->buildHealFor();

            if ($healFor > 0 && $this->currentCharacterHealth !== $this->characterInformationBuilder->buildHealth()) {
                $this->currentCharacterHealth += $healFor;

                $message = 'Light floods your eyes as your wounds heal over for: ' . number_format($healFor);
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }

        return $messages;
    }

    /**
     * Casts spells and deals with dealing damage.
     *
     * @param $attacker
     * @param $defender
     * @param int|null $monsterSpellDamage
     * @return array
     */
    public function castSpell($attacker, $defender, ?int $monsterSpellDamage = null) {
        $messages = [];

        if ($attacker instanceof Character) {
            if ($this->characterInformationBuilder->hasDamageSpells()) {
                $message = 'Your spells burst forward towards the enemy!';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }

        if ($attacker->can_cast) {
            $message = 'The enemy begins to cast their spells!';
            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

            $messages[] = $this->spellDamage($attacker, $defender, $monsterSpellDamage);
        }

        return $messages;
    }

    /**
     * Use rings.
     *
     * Only the character has rings.
     *
     * @param $attacker
     * @param bool $isVoided
     * @return array
     */
    protected function useRings($attacker, bool $isVoided = false): array {
        $messages = [];

        if ($attacker instanceof Character) {
            $ringDamage = $this->characterInformationBuilder->getTotalRingDamage($isVoided);

            if ($ringDamage > 0) {
                $message = 'Your rings begin to shimmer in the presence of the enemy';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $this->currentMonsterHealth -= $ringDamage;

                $message = 'Your rings lash out at the enemy for: ' . number_format($ringDamage);
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }

        return $messages;
    }

    /**
     * Deal artifact damage.
     *
     * @param $attacker
     * @param $defender
     * @param bool $voided
     */
    public function useArtifacts($attacker, $defender, bool $voided = false) {

        if ($attacker instanceOf Character) {
            if ($this->characterInformationBuilder->hasArtifacts()) {
                $message = 'Your artifacts glow before the enemy!';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $this->artifactDamage($attacker, $defender, $voided);
            }
        }

        if ($attacker instanceOf Monster) {
            if ($attacker->can_use_artifacts) {
                $message = 'The enemies artifacts begin to glow ...';
                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

                $this->artifactDamage($attacker, $defender, $voided);
            }
        }
    }

    /**
     * Deals artifact damage either from player or monster.
     *
     * Damage can be annulled based on defenders' annulment percentage.
     *
     * @param $attacker
     * @param $defender
     */
    protected function artifactDamage($attacker, $defender, bool $voided = false) {

        if ($attacker instanceof Character) {
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);
            $defenderArtifactAnnulment = $this->characterInformationBuilder->getTotalAnnulment();
            $dc                        = 100 - $defenderArtifactAnnulment;
            $artifactDamage            = $this->characterInformationBuilder->getTotalArtifactDamage($voided);

            if ($dc <= 0 || rand(1, 100) > $dc) {
                $message = 'Your artifacts were annulled ...';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                return;
            }

            if ($artifactDamage > 0) {
                $health = ceil($this->currentMonsterHealth - $artifactDamage);

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentMonsterHealth = $health;

                $message = 'Your artifacts hit the enemy for: ' . $artifactDamage;
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }

        if ($defender instanceof Character){
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($defender);
            $defenderArtifactAnnulment = $this->characterInformationBuilder->getTotalDeduction('artifact_annulment');
            $dc                        = 100 - $defenderArtifactAnnulment;
            $artifactDamage            = rand(1, $attacker->max_artifact_damage);

            if ($artifactDamage > 0) {
                if ($dc <= 0 || rand(1, 100) > $dc) {
                    $message = 'The enemies artifacts were annulled!';
                    $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
                    return;
                }

                $health = $this->currentCharacterHealth - $artifactDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                $message = 'The enemies artifacts lash out in intense energy doing: ' . $artifactDamage;
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);
            }
        }
    }

    /**
     * Deal spell damage to the enemy.
     *
     * Spell damage can be evaded based on the defenders spell evasion.
     *
     * @param $attacker
     * @param $defender
     * @param int|null $monsterSpellDamage
     * @return void
     */
    protected function spellDamage($attacker, $defender, ?int $monsterSpellDamage = null) {
        if ($attacker instanceof Character) {

            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($attacker);

            $extraAttack = resolve(AttackExtraActionHandler::class);

            $extraAttack->castSpells($this->characterInformationBuilder, $this->currentMonsterHealth, $defender);

            return $extraAttack->getMessages();
        }

        if ($defender instanceof Character){
            $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($defender);

            $defenderArtifactAnnulment = $this->characterInformationBuilder->getTotalDeduction('spell_evasion');
            $dc                        = 100 - $defenderArtifactAnnulment;
            $spellDamage               = $monsterSpellDamage;

            if ($spellDamage > 0) {
                if ($dc <= 0 || rand(1, 100) > $dc) {
                    $message = 'The enemies spells have no effect!';
                    $this->battleLogs = $this->addMessage($message, 'info-battle', $this->battleLogs);

                    return;
                }

                $health = $this->currentCharacterHealth - $spellDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                $message = 'The enemies spells burst towards you, slamming into you for: ' . $spellDamage;
                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);
            }
        }
    }
}