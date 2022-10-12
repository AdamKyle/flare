import {random} from "lodash";
import BattleBase from "../../../../battle-base";

export default class CanEntranceEnemy extends BattleBase {

  constructor() {
    super();

    this.battleMessages = [];
  }

  canEntranceEnemy(attackType, defender) {

    let canEntrance   = false;
    const chance      = attackType.affixes.entrancing_chance;

    if (attackType.affixes.entrancing_chance > 0.0) {
      const cantResist     = attackType.affixes.cant_be_resisted;
      const canBeEntranced = random(1, 100) > (100 - (100 * chance));

      if (cantResist) {
        this.addMessage('The enemy is dazed by your enchantments!', 'player-action');

        canEntrance = true;
      } else if (canBeEntranced) {
        const dc = 100 - (100 * defender.affix_resistance);

        if (dc <= 0 || random(0, 100) > dc) {
          this.addMessage('The enemy resists your entrancing enchantments!', 'enemy-action');

        } else {
          this.addMessage('The enemy is dazed by your enchantments!', 'player-action');

          canEntrance = true;
        }
      } else {
        this.addMessage('The enemy is resists your entrancing enchantments!', 'enemy-action');
      }
    }

    return canEntrance;
  }

  monsterCanEntrance(attacker, defender, isVoided) {
    let canEntrance = false;
    const dc        = this.monsterChanceDC(attacker, defender, isVoided);
    const chance    = random(1, this.monsterMaxRoll(defender));

    if (dc > chance) {
      this.addMessage('You resist the alluring entrancing enchantments on your enemy!', 'player-action');
    } else {

      this.addMessage(attacker.name + ' has trapped you in a trance-like state with their enchantments!', 'enemy-action');

      canEntrance = true;
    }

    return canEntrance;
  }

  monsterChanceDC(attacker, defender, isVoided) {
    if (defender.class === 'Heretic' || defender.class === 'Prophet') {
      let baseDc = (defender.focus * 0.05);

      if (isVoided) {
        baseDc = (defender.voided_focus * 0.05);
      }

      return baseDc - (baseDc * attacker.entrancing_chance);
    }

    return 100 - 100 * attacker.entrancing_chance;
  }

  monsterMaxRoll(defender) {
    if (defender.class === 'Heretic' || defender.class === 'Prophet') {
      return (defender.focus * 0.05);
    }

    return 100;
  }

  getBattleMessages() {
    return this.getMessages();
  }
}
