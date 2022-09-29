import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {random} from "lodash";
import BattleBase from "../../../battle-base";
import {formatNumber} from "../../../../../format-number";

export default class CastAttack extends BattleBase {

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
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_CAST : AttackType.CAST];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    const canHitCheck      = new CanHitCheck();

    const canHit           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);

    if (canHitCheck.getCanAutoHit()) {
      this.mergeMessages(canHitCheck.getBattleMessages());

      const status = this.attackWithSpells(attackData, false, true);

      if (!status) {
        return this.setState();
      }

      if (attackData.heal_for > 0) {
        this.healWithSpells(attackData);
      }

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    if (canEntrance) {
      this.mergeMessages(canEntranceEnemy.getBattleMessages());

      const status = this.attackWithSpells(attackData, true, false);

      if (!status) {
        return this.setState();
      }

      if (attackData.heal_for > 0) {
        this.healWithSpells(attackData);
      }

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    this.mergeMessages(canHitCheck.getBattleMessages());

    if (attackData.spell_damage > 0) {
      if (canHit) {
        if (this.canBlock()) {

          this.addMessage(this.defender.name + ' blocked your damaging spell!', 'enemy-action');

          if (attackData.heal_for > 0) {
            this.healWithSpells(attackData);
          }

          this.useItems(attackData, this.attacker.class);

          return this.setState();
        }

        const status = this.attackWithSpells(attackData, false, false);

        if (!status) {
          return this.setState();
        }

        this.healWithSpells(attackData);

        this.useItems(attackData, this.attacker.class)
      } else {
        this.addMessage('Your damage spell missed!', 'enemy-action');

        if (attackData.heal_for > 0) {
          this.healWithSpells(attackData);
        }

        this.useItems(attackData, this.attacker.class);
      }
    } else {
      this.healWithSpells(attackData);

      this.useItems(attackData, this.attacker.class)
    }

    return this.setState();
  }

  setState() {
    const state = {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterHealth,
      battle_messages: this.getMessages(),
    }

    this.resetMessages();

    return state;
  }

  attackWithSpells(attackData, isEntranced, canAutoHit) {
    const evasion = this.defender.spell_evasion;
    let dc        = 100 - (100 - 100 * evasion);
    let roll      = random(1, 100);

    if (evasion > 1.0 && !isEntranced) {
      this.addMessage('The enemy evades your magic!', 'enemy-action')

      return true;
    }

    const bonus = this.attacker.skills.casting_accuracy;
    roll        = roll + Math.ceil(roll * bonus);

    if (roll < dc && dc > 0 && !isEntranced) {
      this.addMessage('The enemy evades your magic!', 'enemy-action')

      return true;
    }

    const skillBonus = this.attacker.skills.criticality;
    let damage       = attackData.spell_damage;

    const critDc     = 100 - 100 * skillBonus;

    if (random(1, 100) > critDc) {
      this.addMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)', 'regular');

      damage *= 2;
    }

    damage = damage - damage * attackData.damage_deduction;

    this.monsterHealth -= damage;

    this.addMessage('Your damage spell(s) hits ' + this.defender.name + ' for: ' + formatNumber(damage.toFixed(0)), 'player-action');

    if (this.monsterHealth > 0) {
      const healthObject = this.handleCounter(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, 'enemy', this.voided);

      this.characterCurrentHealth = healthObject.character_health;
      this.monsterHealth = healthObject.monster_health;

      if (this.monsterHealth <= 0) {
        return true;
      }

      if (this.characterCurrentHealth <= 0) {
        return false;
      }
    }

    this.extraAttacks(attackData);

    return true;
  }

  healWithSpells(attackData) {

    const skillBonus = this.attacker.skills.criticality;

    let healFor = attackData.heal_for;

    if (healFor > 1) {

      const dc = 100 - 100 * skillBonus;

      if (random(1, 100) > dc) {
        this.addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action')

        healFor *= 2;
      }

      console.log('Regular healing Amount', healFor);

      this.characterCurrentHealth += healFor

      this.addMessage('Your healing spell(s) heals you for: ' + formatNumber(healFor.toFixed(0)), 'player-action');
    }

    this.extraHealing(attackData);
  }

  useItems(attackData, attackerClass) {
    const useItems = new UseItems(this.defender, this.monsterHealth, this.characterCurrentHealth);

    useItems.useItems(attackData, attackerClass, this.voided);

    this.monsterHealth          = useItems.getMonsterCurrentHealth();
    this.characterCurrentHealth = useItems.getCharacterCurrentHealth();

    this.mergeMessages(useItems.getBattleMessage());

  }

  extraAttacks(attackData) {
    const damage = new Damage();

    this.monsterHealth = damage.doubleCastChance(this.attacker, attackData, this.monsterHealth);

    const health = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth, attackData.damage_deduction);

    this.monsterHealth          = health.monster_hp;
    this.characterCurrentHealth = health.character_hp;

    this.mergeMessages(damage.getMessages());
  }

  extraHealing(attackData) {
    const damage = new Damage();

    this.characterCurrentHealth = damage.doubleHeal(this.attacker, this.characterCurrentHealth, attackData, true);

    const health = damage.vampireThirstChance(this.attacker, this.monsterHealth, this.characterCurrentHealth, attackData.damage_deduction);

    this.monsterHealth          = health.monster_hp;
    this.characterCurrentHealth = health.character_hp;

    this.mergeMessages(damage.getMessages());
  }

  canBlock() {
    return this.defender.ac > this.attacker.base_stat;
  }
}
