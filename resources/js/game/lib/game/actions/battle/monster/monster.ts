import {maxBy, random, round, sumBy} from "lodash";
import BattleBase from "../battle-base";

export default class Monster extends BattleBase {

    private monster: any;

    constructor(monster: any) {
        super();

        this.monster = monster;
    }

    getMonster() {
        return this.monster;
    }

    name(): string {
        return this.monster.name;
    }

    devouringDarknessChance(): number {
        return this.monster.devouring_darkness_chance
    }

    devouringLightChance(): number {
        return this.monster.devouring_light_chance
    }

    ambushChance(): number {
        return this.monster.ambush_chance
    }

    ambushResistance(): number {
        return this.monster.ambush_resistance;
    }

    counterChance(): number {
        return this.monster.counter_chance;
    }

    plane(): string {
        return this.monster.map_name;
    }

    health(): number {
        const healthRange = this.monster.health_range.split('-');

        let health = random(healthRange[0], healthRange[1]);

        if (this.monster.is_special && this.monster.dur > 0) {
            health = health + (this.monster.dur * 0.25);
        }

        if (this.monster.increases_damage_by !== null) {
            health = health + health * this.monster.increases_damage_by;
        }

        return round(health);
    }

    getBaseDamageStat(percentage?: number): number {
        if (typeof percentage !== 'undefined') {
            return round(this.monster[this.monster.damage_stat] * percentage)
        }

        return this.monster[this.monster.damage_stat];
    }

    attack(): number {

        const attackRange = this.monster.attack_range.split('-');

        let attack = random(attackRange[0], attackRange[1]) + (this.monster[this.monster.damage_stat] * .25);

        attack = attack + attack * this.monster.increases_damage_by;

        return parseInt(attack.toFixed(0));
    }

    canMonsterDevoidPlayer(devouringDarkResistance: number): boolean {
        let chance = this.monster.devouring_darkness_chance

        if (devouringDarkResistance >= 1) {
            return false;
        }

        if (devouringDarkResistance > chance) {
            return false;
        }

        chance -= devouringDarkResistance;

        if (chance > 1) {
            return true;
        }

        const dc = 100 - 100 * chance;

        return random(1, 100) > dc;
    }

    canMonsterVoidPlayer(devouringLightResistance: number): boolean {
        let chance = this.monster.devouring_light_chance

        if (devouringLightResistance >= 1) {
            return false;
        }

        if (devouringLightResistance > chance) {
            return false;
        }

        chance -= devouringLightResistance;

        if (chance > 1) {
            return true;
        }

        const dc = 100 - 100 * chance;

        return random(1, 100) > dc;
    }

    canMonsterBeStatReduced(resistanceReduction: number, canBeResisted: boolean): boolean {

        if (canBeResisted) {
            return true;
        }

        let resistance = (this.monster.affix_resistance - resistanceReduction);

        if (resistance < 0.0) {
            return true;
        }

        let dc = 50 + Math.ceil(50 * resistance);

        if (dc > 100) {
            dc = 99;
        }

        if (random(0, 100) < dc) {
            return false
        }

        return true;
    }

    reduceStats(character: any) {
        let monster         = JSON.parse(JSON.stringify(this.monster));
        const statReduction = character.stat_affixes;

        if (statReduction.all_stat_reduction === null && statReduction.stat_reduction.length === 0) {
            return;
        }

        if (statReduction.all_stat_reduction !== null) {
            const newMonster = this.reduceAllStats(monster, statReduction);

            if (!newMonster) {
                this.addMessage(this.name() + ' laughs at your attempt to make them weak (All Stat Reduction Failed).', 'regular')
            } else {
                this.addMessage(this.name() + ' sinks to their knees in agony!', 'player-action')

                monster = newMonster;
            }
        }

        if (statReduction.stat_reduction.length > 0) {
            const newMonster = this.nonStackingStatReducingAffixes(monster, statReduction);

            if (!newMonster) {
                this.addMessage(this.name() + ' laughs at your attempt to make them weak (Stat Reduction Failed).', 'regular')
            } else {
                this.addMessage(this.name() + ' cries out for mercy!', 'player-action')

                monster = newMonster;
            }
        }

        this.monster = monster;

        return true;
    }

    reduceAllStats(monster: any, allStatAffixes: any): any|boolean {
        const statReducingAffix = allStatAffixes.all_stat_reduction;

        if (this.canMonsterBeStatReduced(statReducingAffix.resistance_reduction, allStatAffixes.cant_be_resisted)) {
            const strReductionAmount   = monster.str - Math.ceil(monster.str * statReducingAffix.str_reduction);
            const dexReductionAmount   = monster.dex - Math.ceil(monster.dex * statReducingAffix.dex_reduction);
            const durReductionAmount   = monster.dur - Math.ceil(monster.dur * statReducingAffix.dur_reduction);
            const chrReductionAmount   = monster.chr - Math.ceil(monster.chr * statReducingAffix.chr_reduction);
            const intReductionAmount   = monster.int - Math.ceil(monster.int * statReducingAffix.int_reduction);
            const agiReductionAmount   = monster.agi - Math.ceil(monster.agi * statReducingAffix.agi_reduction);
            const focusReductionAmount = monster.focus - Math.ceil(monster.focus * statReducingAffix.focus_reduction);

            monster.str   = strReductionAmount > 0 ? strReductionAmount : 1;
            monster.dex   = dexReductionAmount > 0 ? dexReductionAmount : 1;
            monster.dur   = durReductionAmount > 0 ? durReductionAmount : 1;
            monster.chr   = chrReductionAmount > 0 ? chrReductionAmount : 1;
            monster.int   = intReductionAmount > 0 ? intReductionAmount : 1;
            monster.agi   = agiReductionAmount > 0 ? agiReductionAmount : 1;
            monster.focus = focusReductionAmount > 0 ? focusReductionAmount : 1;

            return monster;
        }

        return false;
    }

    nonStackingStatReducingAffixes(monster: any, allStatAffixes: any): any|boolean {
        const statReducingAffixes = allStatAffixes.stat_reduction;
        const stats: string[]     = ['str', 'dex', 'int', 'chr', 'dur', 'agi', 'focus'];
        const applied: boolean[]  = [];

        for (let i = 0; i < stats.length; i++) {
            const iteratee = stats[i] + '_reduction';
            const sumOfReductions = sumBy(statReducingAffixes, iteratee);
            const maxReduction    = maxBy(statReducingAffixes, 'resistance_reduction');

            if (typeof maxReduction === 'undefined') {
                continue;
            }

            if (this.canMonsterBeStatReduced(parseInt(maxReduction.toString()), allStatAffixes.cant_be_resisted)) {
                const reductionAmount = monster[stats[i]] - (monster[stats[i]] * sumOfReductions);

                monster[stats[i]] = reductionAmount > 0 ? reductionAmount : 1

                applied.push(true);
            } else {
                this.addMessage(this.name() + ' taunts you as one of your stat reducing affixes fails to fire! ('+stats[i]+' failed to fire)', 'regular')
            }
        }

        for (let i = 0; i < stats.length; i++) {
            if (monster[stats[i]] < 0) {
                monster[stats[i]] = 0;
            }
        }

        if (applied.length > 0) {
            return monster;
        }

        return false;
    }

    reduceSkills(skillReduction: number) {
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

            this.addMessage(this.name() + ' Thrashes around blindly with out agility or sound! (skills % reduced)', 'player-action');
        }
    }

    reduceResistances(reduction: number) {

        let monster = JSON.parse(JSON.stringify(this.monster));

        if (reduction > 0.0) {
            monster.spell_evasion      = monster.spell_evasion - reduction;
            monster.affix_resistance   = monster.affix_resistance - reduction;

            if (monster.spell_evasion < 0.0) {
                monster.spell_evasion = 0.0;
            }

            if (monster.affix_resistance < 0.0) {
                monster.affix_resistance = 0.0;
            }

            this.monster = monster;

            this.addMessage(this.name() + ' is less resistant to your charms! (spell/affix resistance reduced!)', 'player-action');
        }

        return;
    }
}
