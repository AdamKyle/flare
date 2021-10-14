import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";

export default class WeaponAttack {

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
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_ATTACK : AttackType.ATTACK];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages   = [...this.battleMessages, canEntranceEnemy.getBattleMessages()];

    if (canEntrance) {
      this.monsterHealth = this.monsterHealth - attackData.weapon_damage;

      this.extraAttacks();

      this.addActionMessage('Your weapon hits ' + this.defender.name + ' for: ' + this.formatNumber(attackData.weapon_damage))

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages);

    this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]

    if (canHit) {
      if (this.canBlock()) {
        this.addMessage(this.defender.name + ' Blocked your attack!');

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      this.monsterHealth = this.monsterHealth - attackData.weapon_damage;

      this.extraAttacks();

      this.addActionMessage('Your weapon hits ' + this.defender.name + ' for: ' + this.formatNumber(attackData.weapon_damage))

      this.useItems(attackData, this.attacker.class)
    } else {
      this.addMessage('Your attack missed!');

      this.useItems(attackData, this.attacker.class);
    }

    return this.setState();
  }

  setState() {
    return {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battleMessages: this.battleMessages,
    }
  }

  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.battleMessages         = [...this.battleMessages, ...useItems.getBattleMessage()];
  }

  extraAttacks() {
    const damage = new Damage();

    this.monsterHealth = damage.tripleAttackChance(this.attacker, this.monsterHealth);
    this.monsterHealth = damage.doubleDamage(this.attacker, this.monsterHealth);
    const healthObject = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth);

    this.monsterHealth          = healthObject.monster_hp;
    this.characterCurrentHealth = healthObject.character_hp;

    this.battleMessages = [...this.battleMessages, ...damage.getMessages()];
  }

  canBlock() {
    return this.defender.ac > this.attacker.base_stat;
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  addMessage(message) {
    this.battleMessages.push({message: message, class: 'info-damage'});
  }

  addActionMessage(message) {
    this.battleMessages.push({message: message, class: 'action-fired'});
  }
}