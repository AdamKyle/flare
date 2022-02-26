import ExtraActionType from "./extra-action-type";
import {random} from "lodash";

export default class Damage {

  constructor() {
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
        this.addMessage('The enemy screams in pain as you siphon large amounts of their health towards you!');
      } else {
        this.addMessage('One of your life stealing enchantments causes the enemy to fall to their knees in agony!');
      }

      if (cantResist) {

        totalDamage = totalDamage - totalDamage * damageDeduction;

        this.addActionMessage('The enemies blood flows through the air and gives you life: ' + this.formatNumber(Math.ceil(totalDamage)));

        monsterCurrentHealth -= totalDamage;
        characterCurrentHealth += totalDamage;
      } else {

        totalDamage = totalDamage - totalDamage * damageDeduction;

        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.addMessage('The enemy resists your attempt to steal it\'s life.');
        } else {

          this.addActionMessage('The enemies blood flows through the air and gives you life: ' + this.formatNumber(Math.ceil(totalDamage)));

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
      this.addMessage('The enemy cannot resist your enchantments! They are so glowy!');

      totalDamage += attacker.non_stacking_damage;
    } else {
      if (attacker.non_stacking_damage > 0) {
        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.addMessage('Your damaging enchantments (resistible) have been resisted.');
        } else {
          totalDamage += attacker.non_stacking_damage;
        }
      }
    }

    if (totalDamage <= 0.0) {
      return monsterCurrentHealth;
    }

    if (totalDamage > 0) {
      monsterCurrentHealth = monsterCurrentHealth - totalDamage;

      let cowerMessage = 'cowers. (non resisted dmg): ';

      if (cantResist) {
        cowerMessage = 'cowers: ';
      }

      cowerMessage = cowerMessage + this.formatNumber(totalDamage);

      this.addActionMessage('Your enchantments glow with rage. Your enemy ' + cowerMessage);
    }

    return monsterCurrentHealth;
  }

  spellDamage(attacker, defender, monsterCurrentHealth) {
    monsterCurrentHealth = this.calculateSpellDamage(attacker, defender, monsterCurrentHealth);

    return this.doubleCastChance(attacker, defender, monsterCurrentHealth);
  }

  canAutoHit(attacker) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (extraActionChance.type === ExtraActionType.THIEVES_SHADOW_DANCE && extraActionChance.has_item) {
        this.addActionMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!');

        return true;
      }

      if (!this.canUse(extraActionChance.chance)) {
        return false;
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
    let totalDamage = attacker.spell_damag;

    if (dc >= 100) {
      dc = 99;
    }

    dc             -= Math.ceil(dc * skillBonus);

    if (roll < dc) {
      this.battleMessages.push({
        message: 'The enemy evades your magic',
        class: 'enemy-action-fired'
      });

      return monsterCurrentHealth;
    }

    totalDamage = totalDamage - totalDamage * attacker.damage_deduction;

    monsterCurrentHealth = monsterCurrentHealth - totalDamage;

    this.battleMessages.push({
      message: attacker.name + ' spells hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      class: 'info-damage',
    });

    return monsterCurrentHealth;
  }

  calculateHealingTotal(attacker, attackData, extraHealing) {
    const skillBonus = attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let healFor = attackData.heal_for;

    const dc = 100 - 100 * skillBonus;

    if (random(1, 100) > dc) {
      this.addActionMessage('The heavens open and your wounds start to heal over (Critical heal!)')

      healFor *= 2;
    }

    if (extraHealing) {
      healFor += healFor * 0.15
    }

    this.characterCurrentHealth += healFor

    this.addActionMessage('Your healing spell(s) heals for: ' + this.formatNumber(healFor.toFixed(0)))
  }

  hammerSmash(attacker, monsterCurrentHealth, attackData) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.HAMMER_SMASH && extraActionChance.has_item) {
        this.addActionMessage('You raise your mighty hammer high above your head and bring it crashing down!');

        let damage = attacker.str_modded * 0.30;

        if (attackData.damage_reduction > 0.0) {
          this.addActionMessage('The Plane weakens your ability to do full damage!');

          damage -= damage * attackData.damage_reduction;
        }

        monsterCurrentHealth -= damage;

        this.addMessage(attacker.name + ' hit for (Hammer): ' + this.formatNumber(damage));

        let roll = random(1, 100);
        roll += roll * 0.60;

        if (roll > 99) {
          this.addActionMessage('The enemy feels the after shocks of the Hammer Smash!');

          for (let i = 3; i > 0; i--) {
            damage -= damage * 0.15;

            monsterCurrentHealth -= damage;

            this.addActionMessage('Aftershock hit for: ' + this.formatNumber(damage));
          }
        }
      }
    }

    return monsterCurrentHealth;
  }

  tripleAttackChance(attacker, monsterCurrentHealth, attackData) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.RANGER_TRIPLE_ATTACK && extraActionChance.has_item) {
        this.addActionMessage('A fury takes over you. You notch the arrows thrice at the enemies direction');

        for (let i = 1; i <= 3; i++) {
          const totalDamage    = (attackData.weapon_damage + attackData.weapon_damage * .15).toFixed(0);
          monsterCurrentHealth = monsterCurrentHealth - totalDamage;

          this.addMessage(attacker.name + ' hit for (weapon - triple attack) ' + this.formatNumber(totalDamage));
        }
      }
    }

    return monsterCurrentHealth;
  }

  doubleDamage(attacker, monsterCurrentHealth, attackData) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.FIGHTERS_DOUBLE_DAMAGE && extraActionChance.has_item) {
        for (let i = 1; i <= 2; i++) {
          this.battleMessages.push({
            message: 'The strength of your rage courses through your veins!',
            class: 'action-fired'
          });

          const totalDamage = (attackData.weapon_damage + attackData.weapon_damage * .15).toFixed(0);

          monsterCurrentHealth = monsterCurrentHealth - totalDamage;

          this.battleMessages.push({
            message: attacker.name + ' hit for (weapon - double attack) ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
            class: 'action-fired'
          });
        }
      }
    }

    return monsterCurrentHealth;
  }

  doubleCastChance(attacker, attackData, monsterCurrentHealth) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.HERETICS_DOUBLE_CAST && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'Magic crackles through the air as you cast again!',
          class: 'action-fired'
        });

        const totalDamage     = attackData.spell_damage + attackData.spell_damage * 0.15;

        monsterCurrentHealth -= totalDamage;

        this.battleMessages.push({
          message: 'Your spell(s) hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
          class: 'info-damage',
        });

      }
    }

    return monsterCurrentHealth;
  }

  doubleHeal(attacker, characterCurrentHealth, attackData, extraHealing) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return characterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.PROPHET_HEALING && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'Your prayers are heard by The Creator and he grants you extra life!',
          class: 'action-fired'
        });

        characterCurrentHealth = this.calculateHealingTotal(attacker, attackData, extraHealing);
      }
    }

    return characterCurrentHealth;
  }

  vampireThirstChance(attacker, monsterCurrentHealth, characterCurrentHealth, damageDeduction) {

    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return {
          monster_hp: monsterCurrentHealth,
          character_hp: characterCurrentHealth,
        };
      }

      if (extraActionChance.type === ExtraActionType.VAMPIRE_THIRST) {
        this.addMessage('There is a thirst child, its in your soul! Lash out and kill!');

        let totalAttack = Math.round(attacker.dur_modded + attacker.dur_modded * 0.15);

        totalAttack     = totalAttack - totalAttack * damageDeduction;

        if (totalAttack > attacker.dur_modded) {
          totalAttack = attacker.dur_modded;
        }

        monsterCurrentHealth   = monsterCurrentHealth - totalAttack;
        characterCurrentHealth = characterCurrentHealth + totalAttack

        this.addActionMessage(attacker.name + ' hit for (thirst!) (and healed for) ' + this.formatNumber(totalAttack));
      }
    }

    return {
      monster_hp: monsterCurrentHealth,
      character_hp: characterCurrentHealth,
    };
  }

  canUse(extraActionChance) {
    if (extraActionChance >= 1.0) {
      return true;
    }

    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }

  getMessages() {
    return this.battleMessages;
  }

  formatNumber(number) {

    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  addMessage(message) {
    this.battleMessages.push({message: message, class: 'info-damage'});
  }

  addActionMessage(message) {
    this.battleMessages.push({message: message, class: 'action-fired'});
  }
}
