import {randomNumber} from '../../helpers/random_number';
import {sum, sumBy} from "lodash/math";
import {groupBy} from "lodash/collection";
import {random} from "lodash";

export default class Monster {

  constructor(monster) {
    this.monster = monster;
  }

  health() {

    if (typeof this.monster === 'undefined') {
      return  0;
    }

    const healthRange = this.monster.health_range.split('-');

    return parseInt(randomNumber(healthRange[0], healthRange[1]) + this.monster.dur);
  }

  canMonsterVoidPlayer() {
    const dc = 100 - 100 * this.monster.devouring_light_chance;

    return random(1, 100) > dc;
  }

  reduceAllStats(affixes) {
    let monster = JSON.parse(JSON.stringify(this.monster));
    const dc    = 100 - monster.affix_resistance;

    if (affixes.all_stat_reduction === null && affixes.stat_reduction.length === 0) {
      this.monster = monster;

      return [];
    }

    if (affixes.all_stat_reduction !== null || affixes.stat_reduction.length > 0) {
      if (!affixes.can_be_resisted && (dc <= 0 || randomNumber(0, 100) > dc)) {
        return [{message: 'Your enemy laughs at your attempt to make them week fails.', class: 'info-damage'}]
      }
    }

    const statReducingAffix = affixes.all_stat_reduction;

    if (statReducingAffix !== null) {
      monster.str = monster.str - (monster.str * statReducingAffix.str_reduction);
      monster.dex = monster.dex - (monster.dex * statReducingAffix.dex_reduction);
      monster.dur = monster.dur - (monster.dur * statReducingAffix.dur_reduction);
      monster.chr = monster.chr - (monster.chr * statReducingAffix.chr_reduction);
      monster.int = monster.int - (monster.int * statReducingAffix.int_reduction);
      monster.agi = monster.agi - (monster.agi * statReducingAffix.agi_reduction);
      monster.focus = monster.focus - (monster.focus * statReducingAffix.focus_reduction);
    }

    const statReducingAffixes = affixes.stat_reduction;

    if (statReducingAffixes.length > 0) {
      const stats = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

      for (let i = 0; i < stats.length; i++) {
        const iteratee = stats[i] + '_reduction';

        const sumOfReductions = sumBy(statReducingAffixes, iteratee);

        monster[stats[i]] = monster[stats[i]] - (monster[stats[i]] * sumOfReductions);
      }
    }

    this.monster = monster;

    return [{message: 'Your enemy sinks to their knees in agony as you make them weaker.', class: 'info-damage'}]
  }

  attack() {

    if (typeof this.monster === 'undefined') {
      return  0;
    }

    const attackRange = this.monster.attack_range.split('-');

    return parseInt(randomNumber(attackRange[0], attackRange[1]) + (this.monster[this.monster.damage_stat] / 2));
  }

  getMonster() {
    return this.monster;
  }

}
