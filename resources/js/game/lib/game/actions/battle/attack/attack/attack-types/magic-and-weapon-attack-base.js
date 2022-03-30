import WeaponAttack from "./weapon-attack";
import UseItems from "./use-items";
import CastAttack from "./cast-attack";
<<<<<<< HEAD:resources/js/game/lib/game/actions/battle/attack/attack/attack-types/magic-and-weapon-attack-base.js
import BattleBase from "../../../battle-base";
=======
import CounterHandler from "./ambush-and-counter/counter-handler";
>>>>>>> 1.1.10.7:resources/js/components/game/battle/attack/attack-types/magic-and-weapon-attack-base.js


export default class MagicAndWeaponAttackBase extends BattleBase {

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
    this.voided                 = voided;
  }

  setStateInfo(attackClass) {
    const state = attackClass.setState();

    this.monsterHealth          = state.monsterCurrentHealth;
    this.characterCurrentHealth = state.characterCurrentHealth;

    this.mergeMessages(state.battleMessages);
  }

  setState() {

    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battleMessages: this.getMessages(),
    }

    return state;
  }

  autoCastAndAttack(attackData, castAttack, canHitCheck, canEntrance) {
    this.mergeMessages(canHitCheck.getBattleMessages());

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, false, true);
    }

    if (attackData.heal_for > 0) {
      castAttack.healWithSpells(attackData);
    }

    this.setStateInfo(castAttack);

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    weaponAttack.attackWithWeapon(attackData, false, true);

    this.setStateInfo(weaponAttack);

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }

  autoAttackAndCast(attackData, canHitCheck, canEntrance) {
    this.mergeMessages(canHitCheck.getBattleMessages());

    return this.doWeaponCastAttack(attackData, canEntrance);
  }

  entrancedCastThenAttack(attackData, castAttack, canEntranceEnemy, canEntrance) {
    this.mergeMessages(canEntranceEnemy.getBattleMessages());

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, true, false);
    }

    if (attackData.heal_for > 0) {
      castAttack.healWithSpells(attackData);
    }

    this.setStateInfo(castAttack);

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    weaponAttack.attackWithWeapon(attackData, true, false);

    this.setStateInfo(weaponAttack);

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }


  entrancedWeaponThenCastAttack(attackData, canEntranceEnemy, canEntrance) {
    this.mergeMessages(canEntranceEnemy.getBattleMessages());

    return this.doWeaponCastAttack(attackData, canEntrance);
  }

  doWeaponCastAttack(attackData, canEntrance) {
    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    weaponAttack.attackWithWeapon(attackData, false, false);

    this.setStateInfo(weaponAttack);

    const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, false, false);
    }

    if (attackData.heal_for > 0) {
      castAttack.healWithSpells(attackData);
    }

    this.setStateInfo(castAttack);

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }

  castAttack(attackData, castAttack, canHitCheck, canCast) {
    const spellDamage = attackData.spell_damage;

    if (spellDamage > 0) {

      if (canCast) {
        if (this.canBlock(attackData.spell_damage)) {
          this.addMessage(this.defender.name + ' Blocked your damage spell!', 'enemy-action');

          if (attackData.heal_for > 0) {
            castAttack.healWithSpells(attackData);
          }
        } else {
          if (attackData.spell_damage > 0) {
            castAttack.attackWithSpells(attackData, false, false);
          } else if (attackData.heal_for > 0) {
            castAttack.healWithSpells(attackData);
          }
        }
      } else {
        this.addMessage('Your damage spell missed', 'enemy-action');
      }

      this.battleMessages    = [...this.battleMessages, canHitCheck.getBattleMessages()]
    } else {
      castAttack.healWithSpells(attackData);
    }
  }

  weaponAttack(attackData, weaponAttack, canHitCheck, canHit) {
    if (canHit) {
      if (this.canBlock(attackData.weapon_damage)) {
        this.addMessage('Your weapon was blocked!', 'enemy-action')
      } else {
        weaponAttack.attackWithWeapon(attackData, false, false);

      }
    } else {
      this.addMessage('Your weapon missed!', 'enemy-action');
    }

    this.mergeMessages(canHitCheck.getBattleMessages());
  }


  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();

    this.mergeMessages(useItems.getBattleMessage());
  }

  canBlock(damage) {
    return this.defender.ac > damage;
  }
}
