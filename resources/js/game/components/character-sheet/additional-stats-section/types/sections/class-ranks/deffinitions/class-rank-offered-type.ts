export interface OfferedGameSkill {
    id: number;
    name: string;
    description: string;
    max_level: number;
}

export interface OfferedWeaponMastery {
    id: number;
    name: string;
    weapon_type: string;
    current_xp: number;
    required_xp: number;
    level: number;
}

export interface OfferedSpecialty {
    id: number;
    name: string;
    description: string;
    requires_class_rank_level: number | null;
}

export default interface ClassRankOfferedType {
    class_id: number;
    class_name: string;
    offered_game_skills: OfferedGameSkill[];
    remaining_weapon_masteries: OfferedWeaponMastery[];
    remaining_specialties: OfferedSpecialty[];
}
