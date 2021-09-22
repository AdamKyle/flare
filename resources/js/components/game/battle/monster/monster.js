import {randomNumber} from '../../helpers/random_number';

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

  reduceAllStats(affix, canResist) {
    let monster = JSON.parse(JSON.stringify(this.monster));
    const dc    = 100 - monster.affix_resistance;

    if (canResist && (dc <= 0 || randomNumber(0, 100) > dc)) {
      return ['Your enemy laughs at your attempt to make them week fails.']
    }

    monster.str   = monster.str - (monster.str * affix.str_reduction);
    monster.dex   = monster.dex - (monster.dex * affix.dex_reduction);
    monster.dur   = monster.dur - (monster.dur * affix.dur_reduction);
    monster.chr   = monster.chr - (monster.chr * affix.chr_reduction);
    monster.int   = monster.int - (monster.int * affix.int_reduction);
    monster.agi   = monster.agi - (monster.agi * affix.agi_reduction);
    monster.focus = monster.focus - (monster.focus * affix.focus_reduction);

    this.monster = monster;

    return [{message: 'Your enemy sinks to their knees in agony as you make them weaker.'}]
  }

  attack() {

    if (typeof this.monster === 'undefined') {
      return  0;
    }

    const attackRange = this.monster.attack_range.split('-');

    return parseInt(randomNumber(attackRange[0], attackRange[1]) + (this.monster[this.monster.damage_stat]) / 2);
  }


}
