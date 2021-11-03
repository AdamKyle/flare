import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import WeaponAttack from "./weapon-attack";
import CastAttack from "./cast-attack";

export default class AttackAndCast {

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
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_ATTACK_AND_CAST : AttackType.ATTACK_AND_CAST];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    this.battleMessages    = canEntranceEnemy.getBattleMessages();

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    if (canEntrance) {

      weaponAttack.attackWithWeapon(attackData);

      this.setStateInfo(weaponAttack);

      if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

      if (attackData.spell_damage > 0) {
        castAttack.attackWithSpells(attackData);
      }

      if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages, this.voided);

    this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]

    if (canHit) {
      if (this.canBlock(attackData.spell_damage + attackData.weapon_damage)) {
        this.addEnemyActionMessage(this.defender.name + ' Blocked both your damage spell and attack!');

        const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

        if (attackData.heal_for > 0) {
          castAttack.healWithSpells(attackData);
        }

        this.useItems(attackData, this.attacker.class);

        return this.setState();
      }

      weaponAttack.attackWithWeapon(attackData);

      this.setStateInfo(weaponAttack);

      const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

      const canCast = canHitCheck.canCast(this.attacker, this.defender);

      if (attackData.spell_damage > 0) {
        if (canCast) {
          if (this.canBlock(attackData.spell_damage)) {
            this.addEnemyActionMessage('Your damaging spells were blocked!');
          } else {
            castAttack.attackWithSpells(attackData);
          }
        } else {
          this.addEnemyActionMessage('Your damage spell missed!');
        }

      } else if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    } else {
      this.addMessage('Your damage spell missed and you fumbled with your weapon!');

      const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

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

  canBlock(damage) {
    return this.defender.ac > damage;
  }

  addMessage(message) {
    this.battleMessages.push({message: message, class: 'info-damage'});
  }

  addEnemyActionMessage(message) {
    this.battleMessages.push({message: message, class: 'enemy-action-fired'});
  }
}