import AttackType from "./attack-type";
import WeaponAttack from "./attack-types/weapon-attack";
import MonsterAttack from "../monster/monster-attack";
import CastAttack from "./attack-types/cast-attack";
import {random} from "lodash";
import UseItems from "./attack-types/use-items";

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

      if (this.state.characterCurrentHealth <= 0) {
        this.resurrectCharacter(defender, attackType)

        if (!attackType.includes('voided')) {
          const attackData = defender.attack_types[attackType];

          this.lifeSteal(defender, attacker, attackData);
        }
      } else {
        if (!attackType.includes('voided')) {
          const attackData = defender.attack_types[attackType];

          this.lifeSteal(defender, attacker, attackData);
        }
      }

      return this;
    }

    switch (attackType) {
      case AttackType.ATTACK:
      case AttackType.VOIDED_ATTACK:
        this.state = (new WeaponAttack(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack();
        break;
      case AttackType.CAST:
      case AttackType.VOIDED_CAST:
        this.state = (new CastAttack(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack();
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

  resurrectCharacter(defender, attackType) {
    const canRes = this.characterCanResurrect(defender, attackType);

    if (canRes) {
      this.state.battleMessages.push({
        message: 'You are pulled back from the void and given one health!',
        class: 'action-fired'
      });

      this.state.characterCurrentHealth = 1;
    }
  }

  characterCanResurrect(defender, attackType) {
    const resChance = defender.attack_types[attackType].res_chance;
    const dc        = 100 - 100 * resChance;
    const roll      = random(1, 100);

    if (roll > dc) {
      return true;
    }

    return false;
  }

  lifeSteal(defender, attacker, attackData) {
    const useItems = new UseItems(defender, this.state.monsterCurrentHealth, this.state.characterCurrentHealth);

    if (defender.class === 'Vampire') {
      useItems.lifeStealingAffixes(attackData, false)
      useItems.lifeStealingAffixes(attackData, true)
    } else {
      useItems.lifeStealingAffixes(attackData, false);
    }

    this.state.battleMessages = [...this.state.battleMessages, ...useItems.getBattleMessage()];
    this.state.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.state.monsterCurrentHealth   = useItems.getMonsterCurrentHealth();
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
