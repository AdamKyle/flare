import ExtraActionType from "./extra-action-type";
import {random} from "lodash";
import BattleBase from "../../battle-base";
import {formatNumber} from "../../../../format-number";
import SpecialAttackClasses from "./special-attack-classes";

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

  canUse(extraActionChance) {

    if (extraActionChance >= 1.0) {
      return true;
    }

    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }
}
