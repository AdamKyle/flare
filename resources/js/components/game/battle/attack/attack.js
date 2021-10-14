import AttackType from "./attack-type";
import WeaponAttack from "./attack-types/weapon-attack";
import MonsterAttack from "../monster/monster-attack";

export default class Attack {

  constructor(characterCurrentHealth, monsterCurrentHealth, voided, monsterVoided) {
    this.characterCurrentHealth = characterCurrentHealth;
    this.characterMaxHealth     = characterCurrentHealth;
    this.monsterCurrentHealth   = monsterCurrentHealth;
    this.isVoided               = voided;
    this.isMonsterVoided        = monsterVoided;
    this.battleMessages         = [];
    this.attackerName           = '';
    this.missed                 = 0;
    this.state                  = {
      characterCurrentHealth: characterCurrentHealth,
      monsterCurrentHealth: monsterCurrentHealth,
      battleMessages: [],
    };
  }

  attack(attacker, defender, attackAgain, type, attackType) {
    if (this.isMonsterDead()) {
      this.state.battleMessages.push({
        message: attacker.getMonster().name + ' has been defeated!',
        class: 'info-damage'
      });

      this.monsterCurrentHealth = 0;

      return this;
    }

    if (this.isCharacterDead()) {
      this.state.battleMessages.push({
        message: 'You must resurrect first!',
        class: 'enemy-action-fired'
      });

      this.characterCurrentHealth = 0;

      return this;
    }

    if (type === 'monster') {

      const monsterAttack = new MonsterAttack(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth);
      const state = monsterAttack.doAttack(attackType, this.isVoided, this.isMonsterVoided);

      this.state.characterCurrentHealth = state.characterCurrentHealth;
      this.state.monsterCurrentHealth   = state.monsterCurrentHealth;
      this.state.battleMessages         = [...this.state.battleMessages, ...state.battleMessages];

      return this;
    }

    switch (attackType) {
      case AttackType.ATTACK:
      case AttackType.VOIDED_ATTACK:
        this.state = (new WeaponAttack(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack();
        break;
      case AttackType.CAST:
      case AttackType.VOIDED_CAST:
        console.log(attackType);
        break;
      case AttackType.CAST_AND_ATTACK:
      case AttackType.VOIDED_CAST_AND_ATTACK:
        console.log(attackType);
        break;
      case AttackType.ATTACK_AND_CAST:
      case AttackType.VOIDED_ATTACK_AND_CAST:
        console.log(attackType);
        break;
      case AttackType.DEFEND:
      case AttackType.VOIDED_DEFEND:
        console.log(attackType);
        break;
      default:
        console.log(attackType);
        break;
    }

    this.characterCurrentHealth = this.state.characterCurrentHealth;
    this.monsterCurrentHealth   = this.state.monsterCurrentHealth;

    return this.attack(defender, attacker, false, 'monster', attackType)
  }

  getState() {
    return this.state;
  }

  isMonsterDead() {
    return this.monsterCurrentHealth <= 0;
  }

  isCharacterDead() {
    return this.characterCurrentHealth <= 0;
  }
}
