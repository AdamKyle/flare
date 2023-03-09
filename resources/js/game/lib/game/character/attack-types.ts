import AttackType from "./attack-type";

export default interface AttackTypes {
    attack: AttackType;

    attack_and_cast: AttackType;

    cast: AttackType;

    cast_and_attack: AttackType;

    defend: AttackType;

    voided_attack: AttackType;

    voided_attack_and_cast: AttackType;

    voided_cast: AttackType;

    voided_cast_and_attack: AttackType;

    voided_defend: AttackType;
}
