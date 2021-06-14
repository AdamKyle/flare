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

  canHit(attacker, defender, type) {
    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus / 2;
    let defenderDodge    = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;

    if (attackerAccuracy < 1) {
      attackerAccuracy = 1 + attackerAccuracy
    }

    if (defenderDodge < 1) {
      defenderDodge = 1 + defenderDodge
    }

    const baseStatBonus      = attacker.base_stat - Math.ceil(attacker.base_stat * .50);
    const enemyBaseStatBonus = defender.base_stat - Math.ceil(defender.base_stat * .50);

    return (attacker.dex + (baseStatBonus * attackerAccuracy)) > (defender.dex + (enemyBaseStatBonus * defenderDodge));
  }

  blockedAttack(defender, attacker, type) {
    let dexBonus      = attacker.dex - Math.ceil(attacker.dex * .50);
    let baseStatBonus = defender.base_stat - Math.ceil(defender.base_stat * .50);

    return Math.ceil((attacker.base_stat / 2) + dexBonus) < (defender.ac + baseStatBonus);
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
