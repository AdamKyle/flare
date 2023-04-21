import AttackTypeAffixes from "./attack-type-affixes";
import AttackTypeSpecialDamage from "./attack-type-special-damage";

export default interface AttackType {
    affixes: AttackTypeAffixes;

    ambush_chance: number;

    ambush_resistance_chance: number;

    attack_type: string;

    counter_chance: number;

    counter_resistance_chance: number;

    damage_deduction: number;

    heal_for: number;

    name: string;

    res_chance: number;

    ring_damage: number;

    special_damage: AttackTypeSpecialDamage|[];

    weapon_damage: number;
}
