import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import Damage from "../../damage";

export default class DoubleHeal extends BattleBase {

    /**
     * Handle the attack.
     *
     * @param character
     * @param attackType
     * @param extraAction
     * @param monsterCurrentHealth
     */
    public handleAttack(character: CharacterType, attackType: AttackType, extraAction: ExtraActionType, characterCurrentHealth: number): number {
        if (!this.canUse(extraAction.chance)) {
            return characterCurrentHealth;
        }

        if (extraAction.type === SpecialType.PROPHET_HEALING && extraAction.has_item) {
            this.addMessage('Your prayers were heard by The Creator and he grants you extra life!', 'regular');

            characterCurrentHealth = this.heal(character, attackType, characterCurrentHealth);

        }

        return characterCurrentHealth;
    }

    /**
     * Do primary damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public heal(character: CharacterType, attackType: AttackType, characterCurrentHealth: number): number {

        const health = (new Damage()).calculateHealingTotal(character, attackType, true)

        this.addMessage('You heal for: ' + formatNumber(health), 'player-action');

        return characterCurrentHealth + health;
    }
}
