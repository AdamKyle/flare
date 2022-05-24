import ExtraActionType from "./extra-action-type";
import {random} from "lodash";
import BattleBase from "../../battle-base";
import {formatNumber} from "../../../../format-number";

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
          totalDamage = attacker.non_stacking_damage - attacker.non_stacking_damage * damageDeduction;
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
    if (attacker.extra_action_chance.class_name === attacker.class) {
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

    dc             -= Math.ceil(dc * skillBonus);

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
    const skillBonus = attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let healFor = attackData.heal_for;

    const dc = 100 - 100 * skillBonus;

    if (random(1, 100) > dc) {
      this.addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'regular')

      healFor *= 2;
    }

    if (extraHealing) {
      healFor += healFor * 0.15
    }

    this.characterCurrentHealth += healFor

    this.addMessage('Your healing spell(s) heals for an additional: ' + formatNumber(parseInt(healFor.toFixed(0))), 'player-action')
  }

  hammerSmash(attacker, monsterCurrentHealth, attackData) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.HAMMER_SMASH && extraActionChance.has_item) {
        this.addMessage('You raise your mighty hammer high above your head and bring it crashing down!', 'regular');

        let damage = attacker.str_modded * 0.30;

        if (attackData.damage_reduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          damage -= damage * attackData.damage_reduction;
        }

        monsterCurrentHealth -= damage;

        this.addMessage(attacker.name + ' hits for (Hammer): ' + formatNumber(damage), 'player-action');

        let roll = random(1, 100);
        roll += roll * 0.60;

        if (roll > 99) {
          this.addMessage('The enemy feels the aftershocks of the Hammer Smash!', 'regular');

          for (let i = 3; i > 0; i--) {
            damage -= damage * 0.15;

            if (damage >= 1) {
              monsterCurrentHealth -= damage;

              this.addMessage('Aftershock hits for: ' + formatNumber(damage), 'player-action');
            }
          }
        }
      }
    }

    return monsterCurrentHealth;
  }

  alchemistsRavenousDream(attacker, monsterCurrentHealth, attackData) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.ARCANE_ALCHEMISTS_DREAMS && extraActionChance.has_item) {
        this.addMessage('The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.', 'regular');

        let damage = attacker.int_modded * 0.10;

        if (attackData.damage_reduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          damage -= damage * attackData.damage_reduction;
        }

        monsterCurrentHealth -= damage;

        this.addMessage(attacker.name + ' hits for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');

        let times = random(2, 6);
        const originalTimes = times;

        while (times > 0) {

          if (times === originalTimes) {
            monsterCurrentHealth -= damage;

            this.addMessage(attacker.name + ' hits for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');
          } else {
            let damage = attacker.int_modded * 0.10;

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
        this.addMessage('A fury takes over you. You notch the arrows thrice at the enemy\'s direction', 'regular');

        for (let i = 1; i <= 3; i++) {
          let totalDamage    = (attackData.weapon_damage + attackData.weapon_damage * .15).toFixed(0);

          if (attackData.damage_reduction > 0.0) {
            this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            totalDamage -= totalDamage * attackData.damage_reduction;
          }

          monsterCurrentHealth = monsterCurrentHealth - totalDamage;

          this.addMessage(attacker.name + ' hits for (weapon - triple attack) ' + formatNumber(totalDamage));
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
        this.addMessage('The strength of your rage courses through your veins!', 'regular');

        let totalDamage = (attackData.weapon_damage + attackData.weapon_damage * .15).toFixed(0);

        if (attackData.damage_reduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          totalDamage -= totalDamage * attackData.damage_reduction;
        }

        for (let i = 1; i <= 2; i++) {

          monsterCurrentHealth = monsterCurrentHealth - totalDamage;

          this.addMessage(attacker.name + ' hit for (weapon - double attack) ' + formatNumber(totalDamage), 'player-action');
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
        this.addMessage('Magic crackles through the air as you cast again!', 'regular');

        let totalDamage     = attackData.spell_damage + attackData.spell_damage * 0.15;

        if (attackData.damage_reduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          totalDamage -= totalDamage * attackData.damage_reduction;
        }

        monsterCurrentHealth -= totalDamage;

        this.addMessage('Your spell(s) hits for: ' +  formatNumber(totalDamage), 'player-action');
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

        this.addMessage('Your prayers were heard by The Creator and he grants you extra life!', 'regular');

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
        this.addMessage('There is a thirst, child, it\'s in your soul! Lash out and kill!', 'regular');

        let totalAttack = Math.round(attacker.dur_modded + attacker.dur_modded * 0.15);

        if (attackData.damage_reduction > 0.0) {
          this.addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

          totalAttack -= totalAttack * attackData.damage_reduction;
        }

        if (totalAttack > attacker.dur_modded) {
          totalAttack = attacker.dur_modded;
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

  canUse(extraActionChance) {
    if (extraActionChance >= 1.0) {
      return true;
    }

    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }
}
