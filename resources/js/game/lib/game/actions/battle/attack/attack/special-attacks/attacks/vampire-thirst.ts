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

export default class VampireThirst extends BattleBase {

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

        if (extraAction.type === SpecialType.VAMPIRE_THIRST && extraAction.has_item) {
            this.addMessage('There is a thirst, child, it\'s in your soul! Lash out and kill!', 'regular');

            return this.doAttack(character, attackType, monsterCurrentHealth, characterCurrentHealth);

        }

        return {
            monster_health: monsterCurrentHealth,
            character_health: characterCurrentHealth,
        };
    }

    /**
     * Do primary damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public doAttack(character: CharacterType, attackType: AttackType, monsterCurrentHealth: number, characterCurrentHealth: number): HealthObject {
        let damage = Math.round(character.dur_modded + character.dur_modded * 0.15);

        damage = this.planeReduction(attackType.damage_deduction, damage);

        this.addMessage('The thirst hits for (and healed for) ' + formatNumber(damage), 'player-action');

        return {
            character_health: characterCurrentHealth + damage,
            monster_health: monsterCurrentHealth - damage,
        }
    }
}
