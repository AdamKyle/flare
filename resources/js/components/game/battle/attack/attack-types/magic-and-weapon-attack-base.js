import WeaponAttack from "./weapon-attack";
import UseItems from "./use-items";
import CastAttack from "./cast-attack";


export default class MagicAndWeaponAttackBase {

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

  setStateInfo(attackClass) {
    const state = attackClass.setState();

    this.monsterHealth          = state.monsterCurrentHealth;
    this.characterCurrentHealth = state.characterCurrentHealth;

    this.battleMessages = [...this.battleMessages, ...state.battleMessages]
  }

  setState() {

    // remove duplicate messages.
    const battleMessages = this.battleMessages.filter((v,i,a)=>a.findIndex(t=>(t.message===v.message))===i);

    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battleMessages: battleMessages,
    }

    this.battleMessages = [];

    return state;
  }

  autoCastAndAttack(attackData, castAttack, canHitCheck, canEntrance) {
    this.battleMessages = [...this.battleMessages, ...canHitCheck.getBattleMessages()];

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, canEntrance);
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

  autoAttackAndCast(attackData, canHitCheck, canEntrance) {
    this.battleMessages    = [...this.battleMessages, ...canHitCheck.getBattleMessages()];

    return this.doWeaponCastAttack(attackData, canEntrance);
  }

  entrancedCastThenAttack(attackData, castAttack, canEntranceEnemy, canEntrance) {
    this.battleMessages    = [...this.battleMessages, ...canEntranceEnemy.getBattleMessages()];

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, canEntrance);
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


  entrancedWeaponThenCastAttack(attackData, canEntranceEnemy, canEntrance) {
    this.battleMessages    = [...this.battleMessages, ...canEntranceEnemy.getBattleMessages()];

    return this.doWeaponCastAttack(attackData, canEntrance);
  }

  doWeaponCastAttack(attackData, canEntrance) {
    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    weaponAttack.attackWithWeapon(attackData);

    this.setStateInfo(weaponAttack);

    const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    if (attackData.spell_damage > 0) {
      castAttack.attackWithSpells(attackData, canEntrance);
    }

    if (attackData.heal_for > 0) {
      castAttack.healWithSpells(attackData);
    }

    this.setStateInfo(castAttack);

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }

  castAttack(attackData, castAttack, canCast) {
    const spellDamage = attackData.spell_damage;

    if (spellDamage > 0) {

      if (canCast) {
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

  weaponAttack(attackData, weaponAttack, canHit) {
    if (canHit) {
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
