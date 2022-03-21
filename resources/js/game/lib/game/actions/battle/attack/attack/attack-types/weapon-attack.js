import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {random} from "lodash";
import BattleBase from "../../../battle-base";
import {formatNumber} from "../../../../../format-number";

export default class WeaponAttack extends BattleBase {

  constructor(attacker, defender, characterCurrentHealth, monsterHealth, voided) {

    super();

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
      this.mergeMessages(canHitCheck.getBattleMessages());

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    if (canEntrance ) {
      this.mergeMessages(canEntranceEnemy.getBattleMessages());

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    this.mergeMessages(canHitCheck.getBattleMessages());

    if (canHit) {
      if (this.canBlock(attackData.weapon_damage)) {
        this.addMessage('Your weapon was blocked!', 'enemy-action');

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      this.attackWithWeapon(attackData);

      this.useItems(attackData, this.attacker.class)
    } else {
      this.addMessage('Your attack missed!', 'enemy-action');

      this.useItems(attackData, this.attacker.class);
    }

    return this.setState();
  }

  setState() {
    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battleMessages: this.getMessages(),
    }

    this.battleMessages = [];

    return state;
  }

  attackWithWeapon(attackData) {

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let damage = attackData.weapon_damage;

    const dc = 100 - 100 * skillBonus;

    if (random(1, 100) > dc) {
      this.addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

      damage *= 2;
    }

    const totalDamage = damage - damage * attackData.damage_deduction;

    this.monsterHealth = this.monsterHealth - totalDamage;

    this.addMessage('Your weapon hits ' + this.defender.name + ' for: ' + formatNumber(totalDamage), 'player-action');

    this.extraAttacks(attackData);
  }

  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();

    this.mergeMessages(useItems.getBattleMessage())
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

    this.mergeMessages(damage.getMessages());
  }

  canBlock(damage) {
    return this.defender.ac > damage;
  }
}
