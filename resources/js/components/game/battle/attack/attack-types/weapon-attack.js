import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";

export default class WeaponAttack {

  constructor(attacker, defender, characterCurrentHealth, monsterHealth) {
    this.attacker               = attacker;
    this.defender               = defender;
    this.monsterHealth          = monsterHealth;
    this.characterCurrentHealth = characterCurrentHealth;
    this.battleMessages         = [];
  }

  doAttack() {
    const attackData       = this.attacker.attack_types[AttackType.ATTACK];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages   = [...this.battleMessages, canEntranceEnemy.getBattleMessages()];

    if (canEntrance) {
      this.monsterHealth = this.monsterHealth - attackData.weapon_damage;

      this.addMessage('Your weapon(s) hits: ' + this.defender.name + ' for: ' + attackData.weapon_damage)

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

      this.addMessage('Your weapon(s) hits: ' + this.defender.name + ' for: ' + attackData.weapon_damage)

      this.useItems(attackData, this.attacker.class)
    } else {
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

  canBlock() {
    return this.defender.ac > this.attacker.base_stat;
  }

  addMessage(message) {
    this.battleMessages.push({message: message});
  }
}