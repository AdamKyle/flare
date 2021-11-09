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

    if (canEntrance) {
      if (attackData.spell_damage > 0) {
        castAttack.attackWithSpells(attackData);
      }

      if (attackData.heal_for > 0) {
        castAttack.healWithSpells(attackData);
      }

      this.setStateInfo(castAttack);

      const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

      weaponAttack.attackWithWeapon(attackData);

      this.setStateInfo(weaponAttack);

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    this.castAttack(attackData, castAttack);

    this.setStateInfo(castAttack);

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    this.weaponAttack(attackData, weaponAttack);

    this.setStateInfo(weaponAttack);

    this.useItems(attackData, this.attacker.class);

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

  castAttack(attackData, castAttack) {
    const spellDamage = attackData.spell_damage;

    if (spellDamage > 0) {

      const canHitCheck      = new CanHitCheck();

      const canCast           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);

      if (canHitCheck.canAutomaticallyHit()) {
        castAttack.attackWithSpells(attackData);

        if (attackData.heal_for > 0) {
          castAttack.healWithSpells(attackData);
        }
      } else if (canCast) {
        if (this.canBlock(attackData.spell_damage)) {
          this.addEnemyActionMessage(this.defender.name + ' Blocked your damage spell!');

          if (attackData.heal_for > 0) {
            castAttack.healWithSpells(attackData);
          }
        } else {
          if (attackData.spell_damage > 0) {
            castAttack.attackWithSpells(attackData);
          } else if (attackData.heal_for > 0) {
            castAttack.healWithSpells(attackData);
          }
        }
      } else {
        this.addEnemyActionMessage('Your damage spell missed');
      }

      this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]
    } else {
      castAttack.healWithSpells(attackData);
    }
  }

  weaponAttack(attackData, weaponAttack) {
    const canHitCheck = new CanHitCheck();
    const canHit      = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages, this.voided);

    if (canHitCheck.canAutomaticallyHit()) {
      weaponAttack.attackWithWeapon(attackData);
    } else if (canHit) {
      if (this.canBlock(attackData.weapon_damage)) {
        this.addEnemyActionMessage('Your weapon was blocked!')
      } else {
        weaponAttack.attackWithWeapon(attackData);
      }
    } else {
      this.addEnemyActionMessage('Your weapon missed!');
    }

    this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]
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