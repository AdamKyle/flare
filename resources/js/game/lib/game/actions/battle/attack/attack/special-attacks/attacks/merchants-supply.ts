import BattleBase from "../../../../battle-base";
import ExtraActionType from "../../../../../../character/extra-action-type";
import SpecialType from "../special-type";
import {CharacterType} from "../../../../../../character/character-type";
import AttackType from "../../../../../../character/attack-type";
import {formatNumber} from "../../../../../../format-number";
import {random} from "lodash";

export default class MerchantsSupply extends BattleBase {

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

        if (extraAction.type === SpecialType.MERCHANTS_SUPPLY && extraAction.has_item) {
            this.addMessage('You stare the enemy down as pull a coin out of your pocket to flip ...', 'regular');

            const chance = random(1, 100);
            let damage   = attackType.weapon_damage;

            if (chance > 50) {
                damage = damage * 4;

                damage = this.planeReduction(attackType.damage_deduction, damage);

                return this.doTwoTimesDamage(monsterCurrentHealth, damage);

            }

            damage = damage * 2;

            damage = this.planeReduction(attackType.damage_deduction, damage);

            return this.doFourTimesDamage(monsterCurrentHealth, damage);
        }

        return monsterCurrentHealth;
    }

    /**
     * Do Four times the damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public doTwoTimesDamage(monsterCurrentHealth: number, damage: number): number {
        monsterCurrentHealth = monsterCurrentHealth - damage;

        this.addMessage('You flip the coin: Heads! You do 2x the damage for a total of: ' + formatNumber(damage), 'player-action');

        return monsterCurrentHealth;
    }

    /**
     * Do two times the damage.
     *
     * @param monsterCurrentHealth
     * @param damage
     */
    public doFourTimesDamage(monsterCurrentHealth: number, damage: number): number {
        monsterCurrentHealth = monsterCurrentHealth - damage;

        this.addMessage('You flip the coin: Heads! You do 4x the damage for a total of: ' + formatNumber(damage), 'player-action');

        return monsterCurrentHealth;
    }
}
