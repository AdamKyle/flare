import Monster from '../monster/monster';
import {randomNumber} from '../../helpers/random_number';

export default class Attack {

  constructor(attacker, defender, characterCurrenthealth, monsterCurrenthealth) {
    this.attacker = attacker;
    this.defender = defender;
    this.characterCurrentHealth = characterCurrenthealth;
    this.monsterCurrentHealth = monsterCurrenthealth;
    this.battleMessages = [];
    this.attackerName = '';
    this.missed       = 0;
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
        message: 'You must resurrect first!'
      });

      this.characterCurrentHealth = 0;

      return this;
    }

    if (!this.canHit(attacker, defender, type)) {
      this.battleMessages.push({
        message: this.attackerName + ' missed!'
      });

      this.missed += 1;

      if (attackAgain) {
        return this.attack(defender, attacker, false, 'monster');
      }
    } else {
      if (this.blockedAttack(defender, attacker, type)) {
        this.battleMessages.push({
          message: defender.name + ' blocked the attack!'
        });

        this.missed += 1;

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
      monsterCurrentHealth: this.monsterCurrentHealth,
      battleMessages: this.battleMessages,
      missCounter: this.missed
    }
  }

  canHit(attacker, defender) {
    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;

    if (attackerAccuracy < 1) {
      attackerAccuracy = 1 + attackerAccuracy
    }

    if (defenderDodge < 1) {
      defenderDodge = 1 + defenderDodge
    }

    const attackerDexPercentage      = this.calculatePercentage(attacker.dex);
    const attackerBaseStatPercentage = this.calculatePercentage(attacker.base_stat);
    const defenderDexPercentage      = this.calculatePercentage(defender.dex);
    const defenderBaseStatPercentage = this.calculatePercentage(defender.base_stat);

    const baseHitPercentage   = Math.round(100*Math.log(attacker.base_stat)/Math.log(10))/100;
    const baseDodgePercentage = Math.round(100*Math.log(defender.dex)/Math.log(10))/100;

    const attack = (attackerDexPercentage + attackerBaseStatPercentage) * (attackerAccuracy > 1 ? attackerAccuracy : (1 + attackerAccuracy));
    const dodge  = (defenderDexPercentage + defenderBaseStatPercentage) * (defenderDodge > 1 ? defenderDodge : (1 + defenderDodge));

    return attack * (1 + (baseHitPercentage / 100)) > dodge * (1 + (baseDodgePercentage / 100));
  }

  blockedAttack(defender, attacker) {
    const attackerBaseStatPercentage = this.calculatePercentage(attacker.base_stat);

    return defender.ac > attackerBaseStatPercentage;
  }

  calculatePercentage(number) {
    let statThousands = Math.round(100*Math.log(number)/Math.log(10))/100;
    let stat          = number;

    switch (statThousands) {
      case 3:
        stat = Math.round(stat / 100);
        break;
      case 4:
        stat = Math.round(stat / 1000);
        break;
      case 5:
        stat = Math.round(stat / 10000);
        break;
      case 6:
        stat = Math.round(stat / 100000);
        break;
      case 7:
        stat = Math.round(stat / 1000000);
        break;
    }

    return stat / 100;
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

      if (attacker.has_artifacts) {
        this.battleMessages.push({
          message: 'Your artifacts glow before the enemy!'
        });
      }

      if (attacker.has_affixes) {
        this.battleMessages.push({
          message: 'The enchantments on your equipment lash out at the enemy!'
        });
      }

      if (attacker.has_damage_spells) {
        this.battleMessages.push({
          message: 'Your spells burst forward towards the enemy!'
        });
      }

      if (attacker.heal_for > 0) {
        const healFor = attacker.heal_for + this.characterCurrentHealth;

        if (attacker.health <= (attacker.health * 0.75)) {
          this.characterCurrentHealth += healFor;

          this.battleMessages.push({
            message: 'Light floods your eyes as your wounds heal over.'
          });
        }
      }

      this.battleMessages.push({
        message: attacker.name + ' hit for ' + attacker.attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }

    if (type === 'monster') {
      const monster = new Monster(attacker);
      const attack = monster.attack();

      this.characterCurrentHealth = this.characterCurrentHealth - attack;

      this.battleMessages.push({
        message: attacker.name + ' hit for ' + attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }
}
