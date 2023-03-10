import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {formatNumber} from "../../../../../format-number";
import SpecialAttacks from "../special-attacks/special-attacks";

export default class Defend {

  constructor(attacker, defender, characterCurrentHealth, monsterHealth, voided) {

    if (!defender.hasOwnProperty('name')) {
      this.defender = defender.monster;
    } else {
      this.defender = defender;
    }

    this.attacker               = attacker;
    this.monsterHealth          = monsterHealth;
    this.characterCurrentHealth = characterCurrentHealth;
    this.battleMessages         = [];
    this.voided                 = voided;
  }

  doAttack() {
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_DEFEND : AttackType.DEFEND];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages   = canEntranceEnemy.getBattleMessages();

    if (canEntrance) {
      this.useItems(attackData, this.attacker.class);

      this.fireOffVampThirst(attackData);

      this.monsterHealth = this.handleClassSpecialAttackEquipped(attackData, this.monsterHealth);

      return this.setState();
    }

    this.useItems(attackData, this.attacker.class)

    this.fireOffVampThirst(attackData);

    this.monsterHealth = this.handleClassSpecialAttackEquipped(attackData, this.monsterHealth);

    return this.setState();
  }

  setState() {
    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battle_messages: this.battleMessages,
    }

    this.battleMessages = [];

    return state;
  }

  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.battleMessages         = [...this.battleMessages, ...useItems.getBattleMessage()];
  }

  fireOffVampThirst(attackData) {

    const specialAttacks = new SpecialAttacks(this.attacker, attackData, this.characterCurrentHealth, this.monsterHealth);

    specialAttacks.doSpecialAttack();

    this.characterCurrentHealth = specialAttacks.getCharacterHealth();
    this.monsterHealth          = specialAttacks.getMonsterHealth();

    this.concatMessages(specialAttacks.getMessages());
  }

  handleClassSpecialAttackEquipped(character, monsterHealth) {

    if (character.special_damage.length == 0) {
      return monsterHealth;
    }

    if (character.special_damage.required_attack_type !== 'any') {

      if (character.special_damage.required_attack_type !== character.attack_type) {
        return monsterHealth;
      }
    }

    monsterHealth = monsterHealth - character.special_damage.damage;

    this.addMessage('Your class special: ' + character.special_damage.name + ' fires off and you do: ' + formatNumber(character.special_damage.damage) + ' damage to the enemy!', "player-action");

    return monsterHealth > 0 ? monsterHealth : 0
  }

  addMessage(message, type) {
    this.battleMessages.push({
      message: message,
      type: type,
    });
  }

  concatMessages(messages) {
    this.battleMessages = this.battleMessages.concat(messages);
  }
}
