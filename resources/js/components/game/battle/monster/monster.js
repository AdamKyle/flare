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

    let health = randomNumber(healthRange[0], healthRange[1]) + this.monster.dur;

    health = health + health * this.monster.increases_damage_by;

    return parseInt(health);
  }

  canMonsterVoidPlayer() {
    const dc = 100 - 100 * this.monster.devouring_light_chance;

    return random(1, 100) > dc;
  }

  reduceAllStats(affixes) {
    let monster = JSON.parse(JSON.stringify(this.monster));
    let dc      = 75 + Math.ceil(75 * monster.affix_resistance);

    if (dc > 100) {
      dc = 99;
    }

    if (affixes.all_stat_reduction === null && affixes.stat_reduction.length === 0) {
      this.monster = monster;

      return [];
    }

    if (affixes.all_stat_reduction !== null || affixes.stat_reduction.length > 0) {
      if (!affixes.can_be_resisted && (dc <= 0 || randomNumber(0, 100) > dc)) {
        return [{message: 'Your enemy laughs at your attempt to make them weak fails.', class: 'enemy-action-fired'}]
      }
    }

    const statReducingAffix = affixes.all_stat_reduction;

    if (statReducingAffix !== null) {
      monster.str = monster.str - Math.ceil(monster.str * statReducingAffix.str_reduction);
      monster.dex = monster.dex - Math.ceil(monster.dex * statReducingAffix.dex_reduction);
      monster.dur = monster.dur - Math.ceil(monster.dur * statReducingAffix.dur_reduction);
      monster.chr = monster.chr - Math.ceil(monster.chr * statReducingAffix.chr_reduction);
      monster.int = monster.int - Math.ceil(monster.int * statReducingAffix.int_reduction);
      monster.agi = monster.agi - Math.ceil(monster.agi * statReducingAffix.agi_reduction);
      monster.focus = monster.focus - Math.ceil(monster.focus * statReducingAffix.focus_reduction);
    }

    const statReducingAffixes = affixes.stat_reduction;
    const stats               = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];

    if (statReducingAffixes.length > 0) {
      for (let i = 0; i < stats.length; i++) {
        const iteratee = stats[i] + '_reduction';

        const sumOfReductions = sumBy(statReducingAffixes, iteratee);

        monster[stats[i]] = monster[stats[i]] - (monster[stats[i]] * sumOfReductions);
      }
    }

    for (let i = 0; i < stats.length; i++) {
      if (monster[stats[i]] < 0) {
        monster[stats[i]] = 0;
      }
    }

    this.monster = monster;

    return [{message: 'Your enemy sinks to their knees in agony as you make them weaker!', class: 'info-damage'}]
  }

  reduceSkills(skillReduction) {
    let monster = JSON.parse(JSON.stringify(this.monster));

    if (skillReduction > 0.0) {
      monster.accuracy         -= skillReduction;
      monster.casting_accuracy -= skillReduction;
      monster.criticality      -= skillReduction;
      monster.dodge            -= skillReduction;

      if (monster.accuracy < 0.0) {
        monster.accuracy = 0.0;
      }

      if (monster.casting_accuracy < 0.0) {
        monster.casting_accuracy = 0.0;
      }

      if (monster.criticality < 0.0) {
        monster.criticality = 0.0;
      }

      if (monster.dodge < 0.0) {
        monster.dodge = 0.0;
      }

      this.monster = monster;

      return [{message: 'Your enemy stumbles around confused as you reduce their chances at life!', class: 'info-damage'}]
    }

    this.monster = monster;

    return [];
  }

  reduceResistances(reduction) {
    let monster = JSON.parse(JSON.stringify(this.monster));

    if (reduction > 0.0) {
      monster.spell_evasion      -= reduction;
      monster.artifact_annulment -= reduction;
      monster.affix_resistance   -= reduction;

      if (monster.spell_evasion < 0.0) {
        monster.spell_evasion = 0.0;
      }

      if (monster.artifact_annulment < 0.0) {
        monster.artifact_annulment = 0.0;
      }

      if (monster.affix_resistance < 0.0) {
        monster.affix_resistance = 0.0;
      }

      this.monster = monster;

      return [{message: 'The enemy looks in awe at the shiny artifacts. They seem less resistant to their allure then before!', class: 'info-damage'}]
    }

    this.monster = monster;

    return [];
  }

  attack() {

    if (typeof this.monster === 'undefined') {
      return  0;
    }

    const attackRange = this.monster.attack_range.split('-');

    let attack = randomNumber(attackRange[0], attackRange[1]) + (this.monster[this.monster.damage_stat] / 2);

    attack = attack + attack * this.monster.increases_damage_by;

    return parseInt(attack);
  }

  getMonster() {
    return this.monster;
  }

}
