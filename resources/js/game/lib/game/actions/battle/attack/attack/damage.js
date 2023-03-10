import ExtraActionType from "./extra-action-type";
import {random} from "lodash";
import BattleBase from "../../battle-base";
import {formatNumber} from "../../../../format-number";
import SpecialAttackClasses from "./special-attack-classes";
import SpecialAttacks from "./special-attacks/special-attacks";

export default class Damage extends BattleBase {

  constructor() {
    super();

    this.battleMessages = [];
  }

  affixLifeSteal(attacker, defender, monsterCurrentHealth, characterCurrentHealth, stacking, damageDeduction) {

    if (monsterCurrentHealth <= 0) {
      return {
        characterHealth: characterCurrentHealth,
        monsterCurrentHealth: monsterCurrentHealth,
      };
    }

    let totalDamage   = monsterCurrentHealth * attacker[stacking ? 'stacking_life_stealing' : 'life_stealing'];

    if (totalDamage > attacker.dur_modded) {
      totalDamage = attacker.dur_modded;
    }

    const cantResist  = attacker.cant_be_resisted;

    if (totalDamage <= 0 || totalDamage <= 0.0) {
      return {
        characterHealth: characterCurrentHealth,
        monsterCurrentHealth: monsterCurrentHealth,
      };
    }

    if (totalDamage > 0.0) {
      if (stacking) {
        this.addMessage('The enemy screams in pain as you siphon large amounts of their health towards you!', 'player-action');
      } else {
        this.addMessage('One of your life stealing enchantments causes the enemy to fall to their knees in agony!', 'player-action');
      }

      if (cantResist) {

        totalDamage = totalDamage - totalDamage * damageDeduction;

        this.addMessage('The enemy\'s blood flows through the air and gives you life: ' + formatNumber(Math.ceil(totalDamage)), 'player-action');

        monsterCurrentHealth -= totalDamage;
        characterCurrentHealth += totalDamage;
      } else {

        totalDamage = totalDamage - totalDamage * damageDeduction;

        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.addMessage('The enemy resists your attempt to steal it\'s life.', 'enemy-action');
        } else {

          this.addMessage('The enemy\'s blood flows through the air and gives you life: ' + formatNumber(Math.ceil(totalDamage)), 'player-action');

          monsterCurrentHealth -= totalDamage;
          characterCurrentHealth += totalDamage;
        }
      }
    }

    return {
      characterHealth: characterCurrentHealth,
      monsterCurrentHealth: monsterCurrentHealth,
    }
  }

  affixDamage(attacker, defender, monsterCurrentHealth, damageDeduction) {
    let totalDamage   = attacker.stacking_damage - attacker.stacking_damage * damageDeduction;
    const cantResist  = attacker.cant_be_resisted;

    if (cantResist) {
      this.addMessage('The enemy cannot resist your enchantments! They are so glowy!', 'regular');

      totalDamage += attacker.non_stacking_damage;
    } else {
      if (attacker.non_stacking_damage > 0) {
        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.addMessage('Your damaging enchantments (resistible) have been resisted.', 'enemy-action');
        } else {
          totalDamage += attacker.non_stacking_damage - attacker.non_stacking_damage * damageDeduction;
        }
      }
    }

    if (totalDamage <= 0.0) {
      return monsterCurrentHealth;
    }

    if (totalDamage > 0) {
      monsterCurrentHealth = monsterCurrentHealth - totalDamage;

      let cowerMessage = 'cowers. (non resisted dmg): ';

      if (!cantResist) {
        cowerMessage = 'cowers: ';
      }

      cowerMessage = cowerMessage + formatNumber(Math.ceil(totalDamage));

      this.addMessage('Your enchantments glow with rage. Your enemy ' + cowerMessage, 'player-action');
    }

    return monsterCurrentHealth;
  }

  spellDamage(attacker, defender, monsterCurrentHealth) {
    monsterCurrentHealth = this.calculateSpellDamage(attacker, defender, monsterCurrentHealth);

    return this.doubleCastChance(attacker, defender, monsterCurrentHealth);
  }

  canAutoHit(attacker) {
    if (SpecialAttackClasses.isThief(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (extraActionChance.type === ExtraActionType.THIEVES_SHADOW_DANCE && extraActionChance.has_item) {

        if (!this.canUse(extraActionChance.chance)) {
          return false;
        }

        this.addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

        return true;
      }
    }

    return false;
  }

  calculateSpellDamage(attacker, defender, monsterCurrentHealth, skillBonus) {
    if (!defender.hasOwnProperty('spell_evasion')) {
      defender = defender.monster;
    }


    let dc          = 75 + Math.ceil(75 * defender.spell_evasion);
    let roll        = random(1, 100);
    let totalDamage = attacker.spell_damage;

    if (dc >= 100) {
      dc = 99;
    }

    dc  -= Math.ceil(dc * skillBonus);

    if (roll < dc) {
      this.addMessage('The enemy evades your magic!', 'enemy-action');

      return monsterCurrentHealth;
    }

    totalDamage = totalDamage - totalDamage * attacker.damage_deduction;

    monsterCurrentHealth = monsterCurrentHealth - totalDamage;

    this.addMessage(attacker.name + ' spells hit for: ' + formatNumber(totalDamage), 'player-action');

    return monsterCurrentHealth;
  }

  calculateHealingTotal(attacker, attackData, extraHealing) {
    let skillBonus = 0;

    if (Array.isArray(attacker.skills)) {
      skillBonus = attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;
    } else {
      // Could be an object of name: float
      skillBonus = attacker.skills.criticality;
    }

    let healFor = attackData.heal_for;

    const dc   = 100 - 100 * skillBonus;
    const roll = random(1, 100)

    if (roll > dc) {
      this.addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'regular')

      healFor *= 2;
    }

    if (extraHealing) {
      healFor += healFor * 0.15
    }

    this.addMessage('Your healing spell(s) heals for an additional: ' + formatNumber(parseInt(healFor.toFixed(0))), 'player-action');

    return healFor;
  }

  alchemistsRavenousDream(attacker, monsterCurrentHealth, attackData) {
    if (SpecialAttackClasses.isArcaneAlchemist(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.ARCANE_ALCHEMISTS_DREAMS && extraActionChance.has_item) {
        this.addMessage('The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.', 'regular');

        let times = random(2, 6);
        const originalTimes = times;
        let percent     = 0.10;

        while (times > 0) {

          if (times === originalTimes) {
            let damage          = attacker.int_modded * 0.10;

            if (attackData.damage_reduction > 0.0) {
              this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

              damage -= damage * attackData.damage_reduction;
            }

            monsterCurrentHealth -= damage;

            this.addMessage(attacker.name + ' hits for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');
          } else {
            let damage = attacker.int_modded * percent;

            if (attackData.damage_reduction > 0.0) {
              this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

              damage -= damage * attackData.damage_reduction;
            }

            if (damage >= 1) {
                this.addMessage('The earth shakes as you cause a multitude of explosions to engulf the enemy.', 'regular');

                monsterCurrentHealth -= damage;

                this.addMessage(attacker.name + ' hits for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');
            }
          }

          times--;
          percent += 0.03;
        }
      }
    }

    return monsterCurrentHealth;
  }

  doubleHeal(attacker, characterCurrentHealth, attackData, extraHealing) {
    if (SpecialAttackClasses.isProphet(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return characterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.PROPHET_HEALING && extraActionChance.has_item) {

        this.addMessage('Your prayers were heard by The Creator and he grants you extra life!', 'regular');

        characterCurrentHealth += this.calculateHealingTotal(attacker, attackData, extraHealing);
      }
    }

    return characterCurrentHealth;
  }

  vampireThirstChance(attacker, monsterCurrentHealth, characterCurrentHealth, damageDeduction) {

    if (SpecialAttackClasses.isVampire(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return {
          monster_hp: monsterCurrentHealth,
          character_hp: characterCurrentHealth,
        };
      }

      if (extraActionChance.type === ExtraActionType.VAMPIRE_THIRST) {
        this.addMessage('There is a thirst, child, it\'s in your soul! Lash out and kill!', 'regular');

        let totalAttack = Math.round(attacker.dur_modded + attacker.dur_modded * 0.15);

        if (damageDeduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          totalAttack -= totalAttack * damageDeduction;
        }

        monsterCurrentHealth   = monsterCurrentHealth - totalAttack;
        characterCurrentHealth = characterCurrentHealth + totalAttack

        this.addMessage(attacker.name + ' hits for (thirst!) (and healed for) ' + formatNumber(totalAttack), 'player-action');
      }
    }

    return {
      monster_hp: monsterCurrentHealth,
      character_hp: characterCurrentHealth,
    };
  }

  prisonersRage(attacker, monsterCurrentHealth, attackData, damageDeduction) {
    if (SpecialAttackClasses.isPrisoner(attacker.class)) {

      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      this.addMessage('You cannot let them keep you prisoner! Lash out and kill!', 'regular');

      let strAmount  = attacker.str_modded * 0.15;
      let damageToDo = (attackData.weapon_damage + strAmount);

      if (damageDeduction > 0.0) {
        this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');
        damageToDo = damageToDo - (damageToDo * damageDeduction);
      }

      const times = Math.random(1, 4);

      for (let i = 0; i <= times; i++) {
          monsterCurrentHealth = monsterCurrentHealth - damageToDo;

        this.addMessage('You slash, you thrash, you bash and you crash your way through! (You dealt: '+formatNumber(damageToDo)+')', 'player-action');
      }
    }

    return monsterCurrentHealth
  }

  bloodyPuke(attacker, monsterCurrentHealth, attackerCurrentHealth, attackData, damageDeduction) {
    if (SpecialAttackClasses.isAlcoholic(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      this.addMessage('You drink and you drink and you drink ...', 'player-action');

      let damageToDo     = attacker.dur_modded * 0.30;
      let damageToSuffer = attacker.dur_modded * 0.15;

      if (damageDeduction > 0.0) {
        this.addMessage('The Plane weakens your ability to do full damage! You will still suffer the 15% damage for vomiting blood.', 'enemy-action');
        damageToDo = damageToDo - (damageToDo * damageDeduction);
      }

      this.addMessage('You cannot hold it in, you vomit blood and bile so acidic your enemy cannot handle it! (You dealt: '+formatNumber(damageToDo)+')', 'player-action');
      this.addMessage('You lost a lot of blood in your attack. You took: ' + formatNumber(damageToSuffer) + ' damage.', 'enemy-action');

      return {
        monsterHealth: monsterCurrentHealth - damageToDo,
        attackerHealth: attackerCurrentHealth - damageToSuffer
      }
    }

    return {
      monsterHealth:  monsterCurrentHealth,
      attackerHealth: attackerCurrentHealth,
    }
  }

  merchantsSupply(attacker, monsterCurrentHealth, attackData, damageDeduction) {

    if (SpecialAttackClasses.isMerchant(attacker.class)) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      this.addMessage('You stare the enemy down as pull a coin out of your pocket to flip ...', 'player-action');

      const chance = random(1, 100);
      let damage = attackData.weapon_damage;

      if (chance > 50) {
         damage = damage * 4;

        if (damageDeduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');
          damage = damage - (damage * damageDeduction);
        }

         monsterCurrentHealth = monsterCurrentHealth - damage;

        this.addMessage('You flip the coin: Heads! You do 4x the damage for a total of: ' + formatNumber(damage), 'player-action');
      } else {
        damage = damage * 2;

        if (damageDeduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');
          damage = damage - (damage * damageDeduction);
        }

        monsterCurrentHealth = monsterCurrentHealth - damage;

        this.addMessage('You flip the coin: Tails! You do 2x the damage for a total of: ' + formatNumber(damage), 'player-action');
      }
    }

    return monsterCurrentHealth;
  }

  canUse(extraActionChance) {

    if (extraActionChance >= 1.0) {
      return true;
    }

    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }
}
