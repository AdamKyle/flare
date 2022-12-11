import ClassSpecialtiesType from "./class-specialties-type";

export default interface CharacterSpecialsEquippedTyp {

    base_ac_mod: number;

    base_damage_mod: number;

    base_damage_stat_increase: number;

    base_healing_mod: number;

    base_spell_damage_mod: number;

    character_id: number;

    current_xp: number;

    equipped: boolean

    game_class_special: ClassSpecialtiesType

    game_class_special_id: number;

    health_mod: number;

    id: number;

    increase_specialty_damage_per_level: number;

    level: number;

    required_xp: number;

    specialty_damage: number;
}
