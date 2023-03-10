import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import {random} from "lodash";

export default class AlchemistsRavenousDream extends BattleBase {

    /**
     * Handle the attack.
     *
     * @param character
     * @param attackType
     * @param extraAction
     * @param monsterCurrentHealth
     */
    public handleAttack(character: CharacterType, attackType: AttackType, extraAction: ExtraActionType, monsterCurrentHealth: number): number {
        if (!this.canUse(extraAction.chance)) {
            return monsterCurrentHealth;
        }

        if (extraAction.type === SpecialType.ARCANE_ALCHEMISTS_DREAMS && extraAction.has_item) {
            this.addMessage('The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.', 'regular');

            let damage           = character.int_modded * 0.10;

            monsterCurrentHealth = this.doDamage(monsterCurrentHealth, damage, attackType.damage_deduction);
        }

        return monsterCurrentHealth;
    }

    /**
     * Do primary damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public doDamage(monsterCurrentHealth: number, damage: number, deduction: number): number {

        let times           = random(2, 6);
        const originalTimes = times;
        let percent         = 0.10;

        while (times > 0) {

            if (times === originalTimes) {
                damage               =  this.planeReduction(deduction, damage);
                monsterCurrentHealth -= damage;

                this.addMessage('You hit for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');
            } else {
                damage               =  this.planeReduction(deduction, damage);
                damage               = damage + damage * percent;

                if (damage >= 1) {
                    this.addMessage('The earth shakes as you cause a multitude of explosions to engulf the enemy.', 'regular');

                    monsterCurrentHealth -= damage;

                    this.addMessage('You hit for (Arcane Alchemist Ravenous Dream): ' + formatNumber(damage), 'player-action');
                }
            }

            times--;
            percent += 0.03;
        }

        return monsterCurrentHealth
    }
}
