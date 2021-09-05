import ExtraActionType from "./extra-action-type";
import {random} from "lodash";

export default class Damage {

  constructor() {
    this.battleMessages = [];
  }


  doAttack(attacker, monsterCurrentHealth) {
    monsterCurrentHealth = monsterCurrentHealth - attacker.attack;

    if (attacker.has_affixes) {
      this.battleMessages.push({
        message: 'Your enchanted equipment glows before the enemy.'
      });
    }

    this.battleMessages.push({
      message: attacker.name + ' hit for (weapon) ' + attacker.attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
    });

    monsterCurrentHealth = this.tripleAttackChance(attacker, monsterCurrentHealth);
    monsterCurrentHealth = this.doubleDamage(attacker, monsterCurrentHealth);
    monsterCurrentHealth = this.vampireThirstChance(attacker, monsterCurrentHealth);

    return monsterCurrentHealth;
  }

  spellDamage(attacker, defender, monsterCurrentHealth) {
    monsterCurrentHealth = this.calculateSpellDamage(attacker, defender, monsterCurrentHealth);

    return this.doubleCastChance(attacker, defender, monsterCurrentHealth, true);
  }

  canAutoHit(attacker) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return false;
      }

      if (extraActionChance.type === ExtraActionType.THIEVES_SHADOW_DANCE && extraActionChance.has_item) {

        this.battleMessages.push({
          message: 'You dance along in the shadows, the enemy doesn\'t see you. Strike now!'
        });

        return true;
      }
    }

    return false;
  }

  calculateSpellDamage(attacker, defender, monsterCurrentHealth, increaseDamage) {
    let totalDamage = Math.round(attacker.spell_damage - (attacker.spell_damage * defender.spell_evasion));

    if (totalDamage < 0) {
      this.battleMessages.push({
        message: this.attackerName + '\'s Spells have no effect!'
      });

      return;
    }

    if (increaseDamage) {
      totalDamage = Math.round(totalDamage + totalDamage * 0.5);
    }

    monsterCurrentHealth = monsterCurrentHealth - totalDamage;

    this.battleMessages.push({
      message: attacker.name + ' spells hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
    });

    return monsterCurrentHealth;
  }

  tripleAttackChance(attacker, monsterCurrentHealth) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.RANGER_TRIPLE_ATTACK && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'A fury takes over you. You notch the arrows thrice at the enemies direction',
        });

        for (let i = 1; i <= 3; i++) {
          monsterCurrentHealth = monsterCurrentHealth - attacker.attack;

          this.battleMessages.push({
            message: attacker.name + ' hit for (weapon - triple attack) ' + attacker.attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
          });
        }
      }
    }

    return monsterCurrentHealth;
  }

  doubleDamage(attacker, monsterCurrentHealth) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.FIGHTERS_DOUBLE_DAMAGE && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'The strength of your rage courses through your veins!',
        });

        const totalDamage = (attacker.attack + attacker.attack * .5);

        monsterCurrentHealth = monsterCurrentHealth - totalDamage;

        this.battleMessages.push({
          message: attacker.name + ' hit for (weapon - double attack) ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
        });
      }
    }

    return monsterCurrentHealth;
  }

  doubleCastChance(attacker, defender, monsterCurrentHealth) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return monsterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.HERETICS_DOUBLE_CAST && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'Magic crackles through the air as you cast again!',
        });

        monsterCurrentHealth = this.calculateSpellDamage(attacker, defender, monsterCurrentHealth, true);
      }
    }

    return monsterCurrentHealth;
  }

  vampireThirstChance(attacker, monsterCurrentHealth) {
    if (attacker.extra_action_chance.class_name === attacker.class) {
      const extraActionChance = attacker.extra_action_chance;

      if (extraActionChance.type === ExtraActionType.VAMPIRE_THIRST) {
        this.battleMessages.push({
          message: 'There is a thirst child, its in your soul! Lash out and kill!',
        });

        const totalAttack = Math.round(attacker.dur - attacker.dur * 0.95);

        monsterCurrentHealth = monsterCurrentHealth - totalAttack;

        this.battleMessages.push({
          message: attacker.name + ' hit for (thirst!) ' + totalAttack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
        });
      }
    }

    return monsterCurrentHealth;
  }

  canUse(extraActionChance) {
    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }

  getMessages() {
    return this.battleMessages;
  }

}
