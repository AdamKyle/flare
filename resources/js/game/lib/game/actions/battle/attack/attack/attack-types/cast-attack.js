import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import UseItems from "./use-items";
import Damage from "../damage";
import {random} from "lodash";
<<<<<<< HEAD:resources/js/game/lib/game/actions/battle/attack/attack/attack-types/cast-attack.js
import BattleBase from "../../../battle-base";
import {formatNumber} from "../../../../../format-number";
=======
import CounterHandler from "./ambush-and-counter/counter-handler";
>>>>>>> 1.1.10.7:resources/js/components/game/battle/attack/attack-types/cast-attack.js

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

      this.attackWithSpells(attackData, false, true);

      if (attackData.heal_for > 0) {
        this.healWithSpells(attackData);
      }

      this.useItems(attackData, this.attacker.class);

      return this.setState();
    }

    if (canEntrance) {
      this.mergeMessages(canEntranceEnemy.getBattleMessages());

      this.attackWithSpells(attackData, true, false);

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
          this.addMessage(this.defender.name + ' Blocked your damaging spell!', 'enemy-action')

          if (attackData.heal_for > 0) {
            this.healWithSpells(attackData);
          }

          this.useItems(attackData, this.attacker.class);

          return this.setState();
        }

        this.attackWithSpells(attackData, false, false);

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
      battleMessages: this.getMessages(),
    }

    this.resetMessages();

    return state;
  }

  attackWithSpells(attackData, isEntranced, canAutoHit) {
    const evasion = this.defender.spell_evasion;
    let dc        = 100;
    let roll      = random(1, 100);

    if (evasion > 1.0 && !isEntranced) {
      this.addMessage('The enemy evades your magic!', 'enemy-action')

      return;
    }

    if (this.attacker.class === 'Prophet' || this.attacker.class === 'Heretic') {
      const bonus = this.attacker.skills.filter(s => s.name === 'Casting Accuracy')[0].skill_bonus

      dc   = roll - Math.ceil(dc * bonus);
      roll = roll - Math.ceil(roll * evasion);
    }

    if (roll < dc && dc > 0 && !isEntranced) {
      this.addMessage('The enemy evades your magic!', 'enemy-action')

      return;
    }

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;
    let damage       = attackData.spell_damage;

    const critDc     = 100 - 100 * skillBonus;

    if (random(1, 100) > critDc) {
      this.addMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)', 'regular');

      damage *= 2;
    }

    damage = damage - damage * attackData.damage_deduction;

    this.monsterHealth -= damage;

<<<<<<< HEAD:resources/js/game/lib/game/actions/battle/attack/attack/attack-types/cast-attack.js
    this.addMessage('Your damage spell(s) hits ' + this.defender.name + ' for: ' + formatNumber(damage.toFixed(0)), 'player-action');
=======
    this.addMessage('Your damage spell(s) hits ' + this.defender.name + ' for: ' + this.formatNumber(damage.toFixed(0)));

    if (!isEntranced && !canAutoHit) {
      this.enemyCounterCastAttack();

      if (this.characterCurrentHealth <= 0 || this.monsterHealth <= 0) {
        return this.setState();
      }
    }
>>>>>>> 1.1.10.7:resources/js/components/game/battle/attack/attack-types/cast-attack.js

    this.extraAttacks(attackData);
  }

  enemyCounterCastAttack() {
    if (this.monsterHealth > 0) {
      const counterHandler = new CounterHandler();

      const healthObject = counterHandler.enemyCounter(this.defender, this.attacker, this.voided, this.monsterHealth, this.characterCurrentHealth);

      this.characterCurrentHealth = healthObject.character_health;
      this.monsterHealth = healthObject.monster_health;

      this.battleMessages = [...this.battleMessages, ...counterHandler.getMessages()];

      counterHandler.resetMessages();

      if (this.monsterHealth <= 0) {
        this.addEnemyActionMessage('Your counter of their counter has slaughtered the enemy!');

        return;
      }

      if (this.characterCurrentHealth <= 0) {
        this.addEnemyActionMessage('The enemies counter has slaughtered you!');

        return;
      }
    }
  }

  healWithSpells(attackData) {

    const skillBonus = this.attacker.skills.filter(s => s.name === 'Criticality')[0].skill_bonus;

    let healFor = attackData.heal_for;

    if (healFor > 1) {

      const dc = 100 - 100 * skillBonus;

      if (random(1, 100) > dc) {
        this.addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action')

        healFor *= 2;
      }

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
