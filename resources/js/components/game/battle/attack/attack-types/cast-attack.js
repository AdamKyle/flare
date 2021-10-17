import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {random} from "lodash";

export default class CastAttack {

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
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_CAST : AttackType.CAST];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages   = canEntranceEnemy.getBattleMessages();

    if (canEntrance) {
      this.attackWithSpells(attackData);

      this.healWithSpells(attackData);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);

    this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]

    if (canHit) {
      if (this.canBlock()) {
        this.addEnemyActionMessage(this.defender.name + ' Blocked your damaging spell!');

        this.healWithSpells(attackData);

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      this.attackWithSpells(attackData);
      this.healWithSpells(attackData);

      this.useItems(attackData, this.attacker.class)
    } else {
      this.addMessage('Your damage spell missed!');

      this.healWithSpells(attackData);

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

  attackWithSpells(attackData) {

    const evasion = this.defender.spell_evasion;
    let dc        = 100 - 100 * evasion;
    let maxRole   = 100

    if (this.attacker.class === 'Prophet' || this.attacker.class === 'Heretic') {
      const bonus = this.attacker.skills.filter(s => s.name === 'Casting Accuracy')[0].skill_bonus
      maxRole     = (this.voided ? this.attacker.voided_focus : this.attacker.focus) * (0.05 + bonus);
      dc          = maxRole - maxRole * evasion
    }

    if (random(1, maxRole) > dc) {
      this.addEnemyActionMessage('The enemy evades your magic!')

      return;
    }

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;
    let damage       = attackData.spell_damage;
    const critDc     = 100 - 100 * skillBonus;

    if (random(1, 100) > critDc) {
      this.addActionMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)')

      damage *= 2;
    }

    this.monsterHealth -= damage;

    this.extraAttacks();

    this.addActionMessage('Your damage spell hits ' + this.defender.name + ' for: ' + this.formatNumber(damage.toFixed(0)))
  }

  healWithSpells(attackData) {

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let healFor = attackData.heal_for;

    const dc = 100 - 100 * skillBonus;

    if (random(1, 100) > dc) {
      this.addActionMessage('The heavens open and your wounds start to heal over (Critical heal!)')

      healFor *= 2;
    }

    this.characterCurrentHealth += healFor

    this.extraHealing();

    this.addActionMessage('Your healing spell(s) heals you for: ' + this.formatNumber(healFor.toFixed(0)))
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

    this.monsterHealth = damage.doubleCastChance(this.attacker, this.defender, this.monsterHealth);

    const health = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth);

    this.monsterHealth          = health.monster_hp;
    this.characterCurrentHealth = health.character_hp;

    this.battleMessages = [...this.battleMessages, ...damage.getMessages()];
  }

  extraHealing() {
    const damage = new Damage();

    this.characterCurrentHealth = damage.doubleHeal(this.attacker, this.characterCurrentHealth);

    const health = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth);

    this.monsterHealth          = health.monster_hp;
    this.characterCurrentHealth = health.character_hp;

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

  addEnemyActionMessage(message) {
    this.battleMessages.push({message: message, class: 'enemy-action-fired'});
  }
}