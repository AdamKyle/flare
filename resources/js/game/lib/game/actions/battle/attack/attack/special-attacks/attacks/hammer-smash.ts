import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import {random} from "lodash";

export default class HammerSmash extends BattleBase {

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

        if (extraAction.type === SpecialType.HAMMER_SMASH && extraAction.has_item) {
            let damage = character.str_modded * 0.30;

            this.addMessage('You raise your mighty hammer high above your head and bring it crashing down!', 'regular');

            damage = this.planeReduction(attackType.damage_deduction, damage);

            monsterCurrentHealth = this.doDamage(monsterCurrentHealth, damage);

            monsterCurrentHealth = this.extraDamage(damage, monsterCurrentHealth);

        }

        return monsterCurrentHealth;
    }

    /**
     * Do primary damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public doDamage(monsterCurrentHealth: number, damage: number): number {

        monsterCurrentHealth -= damage;

        this.addMessage('You hit for (Hammer Smash): ' + formatNumber(damage), 'player-action');

        return monsterCurrentHealth;
    }

    /**
     * Do extra damage.
     *
     * @param damage
     * @param monsterCurrentHealth
     */
    public extraDamage(damage: number, monsterCurrentHealth: number): number {
        let roll = random(1, 100);
        roll += roll * 0.60;

        if (roll > 99) {
            this.addMessage('The enemy feels the aftershocks of the Hammer Smash!', 'regular');

            for (let i = 3; i > 0; i--) {
                damage -= damage * 0.15;

                if (damage >= 1) {
                    monsterCurrentHealth -= damage;

                    this.addMessage('Aftershock hits for: ' + formatNumber(damage), 'player-action');
                }
            }
        }

        return monsterCurrentHealth;
    }
}
