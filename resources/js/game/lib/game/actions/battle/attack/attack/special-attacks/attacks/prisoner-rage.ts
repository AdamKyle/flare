import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import {random} from "lodash";

export default class PrisonerRage extends BattleBase {

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

        if (extraAction.type === SpecialType.PRISONER_RAGE && extraAction.has_item) {
            this.addMessage('You cannot let them keep you prisoner! Lash out and kill!', 'regular');

            let strAmount  = character.str_modded * 0.15;
            let damage     = attackType.weapon_damage + strAmount;

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

        const times = random(1, 4);

        for (let i = 0; i <= times; i++) {
            monsterCurrentHealth = monsterCurrentHealth - damage;

            this.addMessage('You slash, you thrash, you bash and you crash your way through! (You dealt: '+formatNumber(damage)+')', 'player-action');
        }

        return monsterCurrentHealth;
    }
}
