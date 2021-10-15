import Damage from "../damage";
import {random} from "lodash";

const battleMessages = [];

export default class CanHitCheck {

  constructor() {
    this.battleMessages = [];
  }

  canHit (attacker, defender, battleMessages) {
    const damage        = new Damage();

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.battleMessages = [...battleMessages, ...damage.getMessages()];

        return true;
      }
    }

    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.dodge
    let toHitBase        = this.toHitCalculation(attacker.to_hit_base, attacker.dex, attackerAccuracy, defenderDodge);

    if (attackerAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.calculateCanHit(toHitBase);
  }

  canCast(attacker, defender) {
    const damage        = new Damage();

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.battleMessages = [...battleMessages, ...damage.getMessages()];

        return true;
      }
    }

    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Casting Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.dodge
    let toHitBase        = this.toHitCalculation(attacker.to_hit_base, attacker.dex, attackerAccuracy, defenderDodge);

    if (attackerAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.calculateCanHit(toHitBase);
  }

  canMonsterHit(attacker, defender) {
    let monsterAccuracy = attacker.accuracy;
    let defenderDodge   = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;
    let toHitBase       = this.toHitCalculation(attacker.to_hit_base, attacker.dex, monsterAccuracy, defenderDodge);

    if (monsterAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.calculateCanHit(toHitBase);
  }

  canMonsterCast(attacker, defender, isVoided) {
    const castingAccuracy = attacker.casting_accuracy;
    let defenderFocus   = defender.focus * 0.05;

    if (isVoided) {
      defenderFocus = defender.voided_focus * 0.05;
    }

    if (defender.class === 'Prophet' || defender.class === 'Heretic') {
      defenderFocus = defenderFocus * 0.25;
    }

    const dc = Math.ceil(defenderFocus - defenderFocus * castingAccuracy);

    return random(1, defenderFocus) > dc;
  }

  calculateCanHit(toHitBase) {
    if (Math.sign(toHitBase) === - 1) {
      toHitBase = Math.abs(toHitBase);
    }

    if (toHitBase > 1.0) {
      return true;
    }

    const percentage = Math.floor((100 - toHitBase));

    const needToHit = 100 - percentage;

    return (Math.random() * (100 - 1) + 1) > needToHit;
  }

  getBattleMessages () {
    return this.battleMessages;
  }

  toHitCalculation(toHit, dex, accuracy, dodge) {
    const enemyDex = (dex / 10000);
    const hitChance = ((toHit + toHit * accuracy) / 100);

    return (enemyDex + enemyDex * dodge) - hitChance;
  }
}

