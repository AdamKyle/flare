import AttackType from "./attack-type";
import WeaponAttack from "./attack-types/weapon-attack";
import MonsterAttack from "../monster/monster-attack";
import CastAttack from "./attack-types/cast-attack";
import {random} from "lodash";
import UseItems from "./attack-types/use-items";
import Defend from "./attack-types/defend";
import CastAndAttack from "./attack-types/cast-and-attack";
import AttackAndCast from "./attack-types/attack-and-cast";
import BattleBase from "../../battle-base";

export default class Attack extends BattleBase {

  constructor(characterCurrentHealth, monsterCurrentHealth, voided, monsterVoided) {
    super();

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

      this.addMessage(defender.name + ' has been defeated!', 'enemy-action');

      this.state.monsterCurrentHealth = 0;

      this.state.battleMessages = [...this.state.battleMessages, ...this.getMessages()]

      return this;
    }

    if (this.isCharacterDead()) {
      this.addMessage('You must resurrect first!', 'enemy-action');

      this.state.characterCurrentHealth = 0;

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

        if (!attackType.includes('voided') && this.state.characterCurrentHealth >= 1) {
          const attackData = defender.attack_types[attackType];

          this.lifeSteal(defender, attacker, attackData);
        }
      } else if (this.state.characterCurrentHealth > 0) {
        if (!attackType.includes('voided')) {
          const attackData = defender.attack_types[attackType];

          this.lifeSteal(defender, attacker, attackData);
        }
      }

      if (this.isMonsterDead()) {
        this.addMessage(attacker.getMonster().name + ' has been defeated!', 'enemy-action');

        this.monsterCurrentHealth = 0;
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
        this.state = (new CastAndAttack(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack();
        break;
      case AttackType.ATTACK_AND_CAST:
      case AttackType.VOIDED_ATTACK_AND_CAST:
        this.state = (new AttackAndCast(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack();
        break;
      case AttackType.DEFEND:
      case AttackType.VOIDED_DEFEND:
        this.state = (new Defend(attacker, defender, this.characterCurrentHealth, this.monsterCurrentHealth, this.isVoided)).doAttack()
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
      this.addMessage('You are pulled back from the void and given one health!', 'player-action');

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
      useItems.lifeStealingAffixes(attackData, true)
    } else {
      useItems.lifeStealingAffixes(attackData, false);
    }

    this.state.battleMessages         = [...this.state.battleMessages, ...useItems.getBattleMessage()];

    this.state.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.state.monsterCurrentHealth   = useItems.getMonsterCurrentHealth();
  }

  getState() {
    return this.state;
  }

  isMonsterDead() {
    return this.state.monsterCurrentHealth <= 0;
  }

  isCharacterDead() {
    return this.state.characterCurrentHealth <= 0;
  }
}
