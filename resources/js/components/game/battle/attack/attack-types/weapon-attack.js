import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {random} from "lodash";

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

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages);

    if (canHitCheck.getCanAutoHit()) {
      this.battleMessages    = [...this.battleMessages, ...canHitCheck.getBattleMessages()]

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    if (canEntrance ) {
      this.battleMessages    = [...this.battleMessages, ...canEntranceEnemy.getBattleMessages()];

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }


    this.battleMessages    = [...this.battleMessages, ...canHitCheck.getBattleMessages()]

    if (canHit) {
      if (this.canBlock(attackData.weapon_damage)) {
        this.addEnemyActionMessage('Your weapon was blocked!')

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class)
    } else {
      this.addMessage('Your attack missed!');

      this.useItems(attackData, this.attacker.class);
    }

    return this.setState();
  }

  setState() {
    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battleMessages: this.battleMessages,
    }

    this.battleMessages = [];

    return state;
  }

  attackWithWeapon(attackData) {

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let damage = attackData.weapon_damage;

    const dc = 100 - 100 * skillBonus;

    if (random(1, 100) > dc) {
      this.addActionMessage('You become overpowered with rage! (Critical strike!)')

      damage *= 2;
    }

    const totalDamage = damage - damage * attackData.damage_deduction;

    this.monsterHealth = this.monsterHealth - totalDamage;

    this.addMessage('Your weapon hits ' + this.defender.name + ' for: ' + this.formatNumber(totalDamage))

    this.extraAttacks(attackData);

  }

  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.battleMessages         = [...this.battleMessages, ...useItems.getBattleMessage()];
  }

  extraAttacks(attackData) {
    const damage = new Damage();

    this.monsterHealth = damage.tripleAttackChance(this.attacker, this.monsterHealth, attackData);
    this.monsterHealth = damage.doubleDamage(this.attacker, this.monsterHealth, attackData);
    this.monsterHealth = damage.hammerSmash(this.attacker, this.monsterHealth, attackData);
    this.monsterHealth = damage.alchemistsRavenousDream(this.attacker, this.monsterHealth, attackData)
    const healthObject = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth, attackData.damage_deduction);

    this.monsterHealth          = healthObject.monster_hp;
    this.characterCurrentHealth = healthObject.character_hp;

    this.battleMessages = [...this.battleMessages, ...damage.getMessages()];
  }

  canBlock(damage) {
    return this.defender.ac > damage;
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

  addEnemyActionMessage(message) {
    this.battleMessages.push({message: message, class: 'enemy-action-fired'});
  }
}
