import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import Damage from "../../damage";

type HealthObject = {
    character_health: number,
    monster_health: number;
}

export default class BloodyPuke extends BattleBase {

    /**
     * Handle the attack.
     *
     * @param character
     * @param attackType
     * @param extraAction
     * @param monsterCurrentHealth
     */
    public handleAttack(character: CharacterType, attackType: AttackType, extraAction: ExtraActionType, monsterCurrentHealth: number, characterCurrentHealth: number): HealthObject {
        if (!this.canUse(extraAction.chance)) {
            return {
                monster_health: monsterCurrentHealth,
                character_health: characterCurrentHealth,
            };
        }

        if (extraAction.type === SpecialType.BLOODY_PUKE && extraAction.has_item) {
            this.addMessage('You drink and you drink and you drink ...', 'player-action');

            let damage         = character.dur_modded * 0.30;
            let damageToSuffer = character.dur_modded * 0.15;

            damage             = this.handleReduction(damage, attackType.damage_deduction);

            this.addMessage('You cannot hold it in, you vomit blood and bile so acidic your enemy cannot handle it! (You dealt: '+formatNumber(damage)+')', 'player-action');
            this.addMessage('You lost a lot of blood in your attack. You took: ' + formatNumber(damageToSuffer) + ' damage.', 'enemy-action');

            return {
                monster_health: monsterCurrentHealth - damage,
                character_health: characterCurrentHealth - damageToSuffer
            };
        }

        return {
            monster_health: monsterCurrentHealth,
            character_health: characterCurrentHealth,
        };
    }

    /**
     * handle damage reduction with a specific message.
     *
     * @param damage
     * @param damageReduction
     * @protected
     */
    protected handleReduction(damage: number, damageReduction: number): number {
        if (damageReduction > 0.0) {
            this.addMessage('The Plane weakens your ability to do full damage! You will still suffer the 15% damage for vomiting blood.', 'enemy-action');

            return damage - (damage * damageReduction);
        }

        return damage;
    }
}
