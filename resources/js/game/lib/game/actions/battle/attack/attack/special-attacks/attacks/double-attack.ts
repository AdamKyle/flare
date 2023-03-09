import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";

export default class DoubleAttack extends BattleBase {

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

        if (extraAction.type === SpecialType.FIGHTERS_DOUBLE_DAMAGE && extraAction.has_item) {
            this.addMessage('A fury takes over you. You notch the arrows thrice at the enemy\'s direction', 'regular');

            let damage = attackType.weapon_damage + attackType.weapon_damage * 0.15

            damage = this.planeReduction(attackType.damage_deduction, damage);

            monsterCurrentHealth = this.doDamage(monsterCurrentHealth, damage);

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

        for (let i = 1; i <= 2; i++) {

            monsterCurrentHealth = monsterCurrentHealth - damage;

            this.addMessage('You hit for (weapon - double attack) ' + formatNumber(damage), 'player-action');
        }

        return monsterCurrentHealth;
    }
}
