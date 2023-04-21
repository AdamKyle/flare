import Damage from "../damage";
import BattleBase from "../../../battle-base";
import {random} from "lodash";

export default class CanHitCheck extends BattleBase {

  constructor() {
    super();

    this.canAutoHit     = false;
  }

  canAutomaticallyHit() {
    return this.canAutoHit
  }

  canHit (attacker, defender) {
    const damage        = new Damage();

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {

        this.mergeMessages(damage.getMessages());

        this.canAutoHit = true;

        return true;
      }
    }

    return this.toHitCalculation(attacker.to_hit_base, defender.agi, attacker.skills.accuracy, defender.dodge);
  }

  canCast(attacker, defender) {
    const damage         = new Damage();
    let attackerAccuracy = null;
    let dodge            = null;

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.mergeMessages(damage.getMessages());

        this.canAutoHit     = true;

        return true;
      }

      attackerAccuracy = attacker.skills.casting_accuracy;
      dodge            = defender.dodge;
    } else {
      attackerAccuracy = attacker.casting_accuracy;
      dodge            = defender.skills.dodge;
    }

    if (attackerAccuracy >= 1.0) {
      return true;
    }

    if (dodge >= 1.0) {
      return false;
    }

    return this.toHitCalculation(attacker.to_hit_base, defender.agi, attackerAccuracy, dodge)
  }

  canMonsterHit(attacker, defender) {
    let monsterToHit    = attacker.to_hit_base * .20;
    let defenderAgi     = defender.agi * 0.20;
    let monsterAccuracy = attacker.accuracy;
    let defenderDodge   = defender.skills.dodge;

    if (monsterToHit < 50) {
      monsterToHit = attacker.to_hit_base;
    }

    if (defenderAgi < 50) {
      defenderAgi = defender.agi;
    }

    if (monsterAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
      return false;
    }

    return this.toHitCalculation(monsterToHit, defenderAgi, monsterAccuracy, defenderDodge);
  }

  getBattleMessages () {
    return this.getMessages();
  }

  getCanAutoHit() {
    return this.canAutoHit;
  }

  toHitCalculation(toHit, agi, accuracy, dodge) {

    if (accuracy >= 1) {
      return true;
    }

    if (dodge >= 1) {
      return false;
    }

    let enemyAgi = agi * 0.20; // Take only 20%.
    let playerToHit = toHit * 0.20; // take only 20%.

    if (playerToHit < 50) {
      playerToHit = toHit;
    }

    if (enemyAgi < 50) {
      enemyAgi = agi;
    }

    return (playerToHit + playerToHit * accuracy) > (enemyAgi + enemyAgi * dodge);
  }
}

