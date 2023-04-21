import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";

export default class DoubleCast extends BattleBase {

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

        if (extraAction.type === SpecialType.HERETICS_DOUBLE_CAST && extraAction.has_item) {
            this.addMessage('Magic crackles through the air as you cast again!', 'regular');

            let damage = character.spell_damage + character.spell_damage * 0.15

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

        monsterCurrentHealth = monsterCurrentHealth - damage;

        this.addMessage('Your spell(s) hits for: ' +  formatNumber(damage), 'player-action');

        return monsterCurrentHealth;
    }
}
