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

    return randomNumber(healthRange[0], healthRange[1]) + 10 + this.monster.dur;
  }

  attack() {

    if (typeof this.monster === 'undefined') {
      return  0;
    }

    const attackRange = this.monster.attack_range.split('-');

    return randomNumber(attackRange[0], attackRange[1]) + this.monster[this.monster.damage_stat];
  }
}
