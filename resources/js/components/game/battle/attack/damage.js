import ExtraActionType from "./extra-action-type";
import {random} from "lodash";

export default class Damage {

  constructor() {
    this.battleMessages = [];
  }


  doAttack(attacker, monsterCurrentHealth) {
    monsterCurrentHealth = monsterCurrentHealth - attacker.attack;

    this.battleMessages.push({
      message: attacker.name + ' hit for (weapon) ' + attacker.attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
    });

    monsterCurrentHealth = this.tripleAttackChance(attacker, monsterCurrentHealth);
    monsterCurrentHealth = this.doubleDamage(attacker, monsterCurrentHealth);
    monsterCurrentHealth = this.vampireThirstChance(attacker, monsterCurrentHealth);

    return monsterCurrentHealth;
  }

  affixLifeSteal(attacker, defender, monsterCurrentHealth, characterCurrentHealth, stacking) {
    defender          = defender.getMonster();
    let totalDamage   = monsterCurrentHealth * attacker[stacking ? 'stacking_life_stealing' : 'life_stealing'];
    const cantResist  = attacker.cant_be_resisted;

    if (stacking) {
      this.battleMessages.push({
        message: 'The enemy screams in pain as you, again, drain it\'s life!'
      });
    }

    if (totalDamage > 0) {
      if (cantResist) {
        this.battleMessages.push({
          'message': 'The enemies blood flows through the air and gives you life: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        });
      } else {
        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.battleMessages.push({
            'message': 'The enemy resists your attempt to steal it\'s life.'
          });
        } else {
          this.battleMessages.push({
            'message': 'The enemies blood flows through the air and gives you life: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
          });

          monsterCurrentHealth = monsterCurrentHealth - totalDamage;
          characterCurrentHealth = characterCurrentHealth + totalDamage;
        }
      }
    }

    return {
      characterHealth: characterCurrentHealth,
      monsterCurrentHealth: monsterCurrentHealth,
    }
  }

  affixDamage(attacker, defender, monsterCurrentHealth) {
    defender          = defender.getMonster();
    let totalDamage   = attacker.stacking_damage;
    const cantResist  = attacker.cant_be_resisted;

    if (cantResist) {
      this.battleMessages.push({
        'message': 'The enemy cannot resist your enchantments! They are so glowy!'
      });

      totalDamage += attacker.non_stacking_damage
    } else {
      if (attacker.non_stacking_damage > 0) {
        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(1, 100) > dc) {
          this.battleMessages.push({
            'message': 'Your damaging enchantments (resistible) have been resisted.'
          });
        } else {
          totalDamage += attacker.non_stacking_damage
        }
      }
    }

    if (totalDamage > 0) {
      monsterCurrentHealth = monsterCurrentHealth - totalDamage;

      let cowerMessage = 'cowers. (non resisted dmg): ';

      if (cantResist) {
        cowerMessage = 'cowers: ';
      }

      cowerMessage = cowerMessage + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")

      this.battleMessages.push({
        'message': 'Your enchantments glow with rage. Your enemy ' + cowerMessage,
      });
    }

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
    if (!defender.hasOwnProperty('spell_evasion')) {
      defender = defender.getMonster();
    }

    const dc        = 100 - (100 * defender.spell_evasion);
    let totalDamage = attacker.spell_damage;

    if (dc <= 0 || random(1, 100) > dc) {
      this.battleMessages.push({
        message: 'Your spells failed to do anything.'
      });

      return monsterCurrentHealth;
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
