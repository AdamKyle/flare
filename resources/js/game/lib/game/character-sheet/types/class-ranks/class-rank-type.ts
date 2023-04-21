import GameClassType from "./game-class-type";
import WeaponMastery from "./weapon-mastery";

export default interface ClassRankType {
    character_id: number;
    class_name: string;
    current_xp: number;
    game_class: GameClassType;
    weapon_masteries: WeaponMastery[],
    game_class_id: number;
    id: number;
    is_active: boolean;
    is_locked: boolean;
    level: number;
    required_xp: number;
    primary_class_name: string|null;
    secondary_class_name: string|null;
    primary_class_required_level: number|null;
    secondary_class_required_level: number|null;
}
