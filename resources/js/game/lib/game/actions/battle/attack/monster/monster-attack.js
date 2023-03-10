import CanEntranceEnemy from "../attack/attack-types/enchantments/can-entrance-enemy";
import CanHitCheck from "../attack/attack-types/can-hit-check";
import AttackType from "../attack/attack-type";
import UseItems from "../attack/attack-types/use-items";
import {parseInt, random} from "lodash";
import BattleBase from "../../battle-base";
import {formatNumber} from "../../../../format-number";
import Monster from "../../monster/monster";

export default class MonsterAttack extends BattleBase {

  constructor(attacker, defender, currentCharacterHealth, currentMonsterHealth, maxMonsterHealth) {
    super();

    this.attacker               = attacker;
    this.defender               = defender;
    this.currentCharacterHealth = currentCharacterHealth;
    this.currentMonsterHealth   = currentMonsterHealth;
    this.maxMonsterHealth       = maxMonsterHealth;
  }

  doAttack(previousAttackType, isCharacterVoided, isMonsterVoided) {
    const monster = this.attacker;
    let damage    = (new Monster(this.attacker)).attack();

    if (this.entrancesEnemy(monster, this.defender, isCharacterVoided, isMonsterVoided)) {

      if (this.canDoCritical(monster)) {
        this.addMessage(monster.name + ' grows enraged and lashes out with all fury! (Critical Strike!)', 'regular')

        damage = damage * 2;
      }

      if (this.defender.ac >= damage) {
        this.addMessage('You blocked the enemies attack with your armour!', 'player-action');
      } else {
        damage -= this.defender.ac;


        this.addMessage('You reduced the incoming (Physical) damage with your armour by: ' + formatNumber(this.defender.ac), 'player-action')

        this.currentCharacterHealth = this.currentCharacterHealth - damage;

        this.addMessage(monster.name + ' hits for: ' + formatNumber(damage), 'enemy-action');
      }

      this.useItems(monster, isCharacterVoided, isMonsterVoided);

      this.fireOffHealing(monster);

      return this.setState()
    } else {
      if (this.canHit(monster, this.defender)) {

        if (this.isBlocked(previousAttackType, this.defender, damage, isCharacterVoided)) {
          this.addMessage('The enemy\'s attack was blocked!', 'player-action');

          this.useItems(monster, isCharacterVoided, isMonsterVoided, previousAttackType);

          this.fireOffHealing(monster);

          return this.setState()
        }

        if (this.canDoCritical(monster)) {
          this.addMessage(monster.name + ' grows enraged and lashes out with all fury! (Critical Strike!)', 'enemey-action')

          damage = damage * 2;
        }

        if (this.defender.ac >= damage) {
          this.addMessage('You blocked the enemies attack!', 'player-action');
        } else {
          damage -= this.defender.ac;


          this.addMessage('You reduced the incoming (Physical) damage with your armour by: ' + formatNumber(this.defender.ac), 'player-action')

          this.currentCharacterHealth = this.currentCharacterHealth - damage;

          this.addMessage(monster.name + ' hits for: ' + formatNumber(damage), 'enemy-action');
        }

        this.useItems(monster, isCharacterVoided, isMonsterVoided, previousAttackType);

        this.fireOffHealing(monster);

        return this.setState()

      } else {
        this.addMessage(monster.name + ' missed!', 'enemy-action');

        this.useItems(monster, isCharacterVoided, isMonsterVoided, previousAttackType);

        this.fireOffHealing(monster);

        return this.setState();
      }
    }
  }

  setState() {
    return {
      characterCurrentHealth: this.currentCharacterHealth,
      monsterCurrentHealth: this.currentMonsterHealth,
      battleMessages: this.getMessages(),
    }
  }

  entrancesEnemy(attacker, defender, isCharacterVoided, isMonsterVoided) {

    if (isMonsterVoided) {
      return false;
    }

    const canEntrance = new CanEntranceEnemy();

    if (canEntrance.monsterCanEntrance(attacker, defender, isCharacterVoided)) {
      this.mergeMessages(canEntrance.getBattleMessages());

      return true;
    }

    this.mergeMessages(canEntrance.getBattleMessages());

    return false;
  }

  canHit(attacker, defender) {
    const canHit = new CanHitCheck()

    if (canHit.canMonsterHit(attacker, defender, this.battleMessages)) {
      return true;
    }

    return false;
  }

  isBlocked(attackType, defender, damage, isCharacterVoided) {
    console.log(defender);
    if (AttackType.DEFEND === attackType || AttackType.VOIDED_DEFEND === attackType) {
      const defenderAC = defender.attack_types[attackType].defence;


      if (damage <= defenderAC) {
        return true
      }

      const chanceToBlock = defenderAC / damage;

      const dc = 100 - 100 * chanceToBlock

      return random(1, 100) > dc;
    }

    if (isCharacterVoided) {
      return damage <= defender.voided_ac;
    }

    return damage <= defender.ac;
  }

  useItems(attacker, isCharacterVoided, isMonsterVoided, previousAttackType) {

    if (!isMonsterVoided) {
      const useItems = new UseItems(this.defender, this.currentMonsterHealth, this.currentCharacterHealth);

      this.mergeMessages(useItems.getBattleMessage());

      this.currentCharacterHealth = useItems.getCharacterCurrentHealth();

      this.fireOffAffixes(attacker);
    }

    this.fireOffSpells(attacker, this.defender, isCharacterVoided, previousAttackType);
  }

  fireOffAffixes(attacker) {
    if (attacker.max_affix_damage > 0) {
      const defenderReduction = this.defender.affix_damage_reduction;
      let damage              = random(1, attacker.max_affix_damage);

      if (defenderReduction > 0) {
        damage = (damage - (damage * defenderReduction)).toFixed(2);

        this.addMessage('Your rings negate some of the enemy\'s enchantment damage.', 'player-action');
      }

      if (damage <= 0.0) {
        return;
      }

      this.currentCharacterHealth = this.currentCharacterHealth - damage;

      this.addMessage(attacker.name + '\'s enchantments glow, lashing out for: ' + formatNumber(damage), 'enemy-action');
    }
  }

  fireOffSpells(attacker, defender, isCharacterVoided, previousAttackType) {
    if (!this.canCastSpells(attacker, defender, isCharacterVoided)) {
      this.addMessage(attacker.name + '\'s Spells fizzle and fail to fire.', 'enemy-action');

      return;
    }

    if (attacker.spell_damage > 0) {
      const evasionChance = this.defender.spell_evasion;
      const dc            = Math.ceil(100 - (100 * evasionChance));
      const roll          = random(1, 100);

      if (evasionChance >= 1.0) {
        this.addMessage('You evade the enemy\'s spells!', 'player-action');

        return;
      }

      if (roll > dc) {
        this.addMessage('You evade the enemy\'s spells!', 'player-action');
      } else {
        if (this.isBlocked(previousAttackType, defender, damage, isCharacterVoided)) {
          this.addMessage('You managed to block the enemy\'s spells with your armour!', 'player-action');

          return;
        }

        let damage = attacker.spell_damage;

        if (this.canDoCritical(attacker)) {
          this.addMessage(attacker.name + ' With a fury of hatred their spells fly viciously at you! (Critical Strike!)', 'regular')

          damage = damage * 2;
        }

        this.currentCharacterHealth = this.currentCharacterHealth - damage

        this.addMessage(attacker.name + '\'s spells burst toward you doing: ' + formatNumber(damage), 'enemy-action');

        return;
      }
    }
  }

  canCastSpells(attacker, defender, isCharacterVoided) {
    const canHit = new CanHitCheck()

    if (canHit.canCast(attacker, defender, isCharacterVoided)) {
      return true;
    }

    return false;
  }

  fireOffHealing(attacker) {
    if (attacker.max_healing > 0) {
      const defenderHealingReduction = this.defender.healing_reduction;
      let monsterCurrentHealth       = parseInt(this.currentMonsterHealth) || 0;
      let healFor                    = Math.ceil(attacker.dur * attacker.max_healing);

      if (healFor < 0) {
        return;
      }

      if (this.canDoCritical(attacker)) {
        this.addMessage(attacker.name + ' Glows with renewed life! (Critical Healing!)', 'regular')

        healFor = healFor * 2;
      }

      if (defenderHealingReduction > 0.0) {
        healFor = healFor - healFor * defenderHealingReduction;
      }

      if (healFor > 1) {
        monsterCurrentHealth = monsterCurrentHealth + healFor;

        if (monsterCurrentHealth > this.maxMonsterHealth) {
          monsterCurrentHealth = parseInt(this.maxMonsterHealth) || 0;
        }

        this.currentMonsterHealth = monsterCurrentHealth;

        if (defenderHealingReduction > 0.0) {
          this.addMessage('Your rings negate some of the enemy\'s healing power.', 'player-action');
        }

        this.addMessage(attacker.name + '\'s healing spells wash over them for: ' + formatNumber(healFor.toFixed(0)), 'enemy-action');
      } else if (defenderHealingReduction > 0.0) {
        this.addMessage('Your rings negate all of the enemy\'s healing power.', 'player-action');
      }
    }
  }

  canDoCritical(attacker) {
    const dc = 100 - 100 * attacker.criticality;

    return random(1, 100) > dc;
  }

}
