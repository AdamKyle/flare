import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import WeaponAttack from "./weapon-attack";
import CastAttack from "./cast-attack";

export default class CastAndAttack {

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
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_CAST_AND_ATTACK : AttackType.CAST_AND_ATTACK];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages    = canEntranceEnemy.getBattleMessages();

    const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    if (canEntrance) {

      if (attackData.spell_damage > 0) {
        castAttack.attackWithSpells(attackData);
      }

      if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      weaponAttack.attackWithWeapon(attackData);

      this.setStateInfo(weaponAttack);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);

    this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]

    if (canHit) {
      if (this.canBlock(attackData.spell_damage + attackData.weapon_damage)) {
        this.addEnemyActionMessage(this.defender.name + ' Blocked both your damage spell and attack!');

        if (attackData.heal_for > 0) {
          castAttack.healWithSpells(attackData);
        }

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      const canHit = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages, this.voided);


      if (attackData.spell_damage > 0) {
        castAttack.attackWithSpells(attackData);
      } else if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      if (canHit) {
        if (this.canBlock(attackData.weapon_damage)) {
          this.addEnemyActionMessage('Your weapon was blocked!')
        } else {
          weaponAttack.attackWithWeapon(attackData);
        }
      } else {
        this.addEnemyActionMessage('Your weapon missed!');
      }

      this.setStateInfo(weaponAttack);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    } else {
      this.addMessage('Your damage spell missed and you fumbled with your weapon!');

      if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.useItems(attackData, this.attacker.class);
    }

    return this.setState();
  }

  setStateInfo(attackClass) {
    const state = attackClass.setState();

    this.monsterHealth          = state.monsterCurrentHealth;
    this.characterCurrentHealth = state.characterCurrentHealth;

    this.battleMessages = [...this.battleMessages, ...state.battleMessages]
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


  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();
    this.battleMessages         = [...this.battleMessages, ...useItems.getBattleMessage()];
  }

  canBlock() {
    return this.defender.ac > this.attacker.base_stat;
  }

  addMessage(message) {
    this.battleMessages.push({message: message, class: 'info-damage'});
  }

  addEnemyActionMessage(message) {
    this.battleMessages.push({message: message, class: 'enemy-action-fired'});
  }
}