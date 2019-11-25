import Monster from '../monster/monster';
import {randomNumber} from '../../helpers/random_number';

export default class Attack {

  constructor(attacker, defender, characterCurrenthealth, monsterCurrenthealth) {
    this.attacker               = attacker;
    this.defender               = defender;
    this.characterCurrentHealth = characterCurrenthealth;
    this.monsterCurrentHealth   = monsterCurrenthealth;
    this.battleMessages         = [];
    this.attackerName           = '';
  }

  attack(attacker, defender, attackAgain, type) {
    this.attackerName = attacker.name;

    if (this.isMonsterDead()) {
      this.battleMessages.push({
        message: this.defender.name + ' has been defeated!'
      });

      this.monsterCurrentHealth = 0;

      return this;
    }

    if (this.isCharacterDead()) {
      this.battleMessages.push({
        message: 'You must ressurect first!'
      });

      this.characterCurrentHealth = 0;

      return this;
    }

    if (!this.canHit(attacker, defender)) {
      this.battleMessages.push({
        message: this.attackerName + ' missed!'
      });

      if (attackAgain) {
        return this.attack(defender, attacker, false, 'monster');
      }
    } else {
      if (this.blockedAttack(defender, attacker)) {
        this.battleMessages.push({
          message: defender.name + ' blocked the attack!'
        });

        if (attackAgain) {
          return this.attack(defender, attacker, false, 'monster');
        }
      } else {
        this.doAttack(attacker, type);

        if (attackAgain) {
          return this.attack(defender, attacker, false, 'monster');
        }
      }
    }

    return this;
  }

  getState() {
    return {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth:   this.monsterCurrentHealth,
      battleMessages:         this.battleMessages,
    }
  }

  canHit(attacker, defender) {
    const attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    const defenderDodge    = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;

    return (randomNumber(1, 20) + attackerAccuracy) > 10 + defenderDodge;
  }

  blockedAttack(defender, attacker) {
    const attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;

    return (randomNumber(1, 20) + attackerAccuracy) < defender.ac;
  }

  isMonsterDead() {
    return this.monsterCurrentHealth <= 0;
  }

  isCharacterDead() {
    return this.characterCurrentHealth <= 0;
  }

  doAttack(attacker, type) {
    if (type === 'player') {
      this.monsterCurrentHealth = this.monsterCurrentHealth - attacker.attack;

      this.battleMessages.push({
        message: attacker.name + ' hit for ' + attacker.attack
      });
    }

    if (type === 'monster') {
      const monster = new Monster(attacker);
      const attack  = monster.attack();

      this.characterCurrentHealth = this.characterCurrentHealth - attack;

      this.battleMessages.push({
        message: attacker.name + ' hit for ' + attack
      });
    }
  }
}
