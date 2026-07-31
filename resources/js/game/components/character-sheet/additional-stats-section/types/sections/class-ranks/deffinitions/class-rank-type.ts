import GameClassType from "./game-class-type";
import WeaponMastery from "./weapon-mastery";

export default interface ClassRankType {
    character_id?: number;
    class_name: string;
    current_xp: number;
    game_class?: GameClassType;
    weapon_masteries: WeaponMastery[];
    game_class_id: number;
    id?: number;
    is_active: boolean;
    is_locked: boolean;
    level: number;
    required_xp: number;
    primary_class_name?: string | null;
    secondary_class_name?: string | null;
    primary_class_required_level?: number | null;
    secondary_class_required_level?: number | null;

    // Fields present when this class rank comes from a preloaded/read-only
    // (e.g. Tops character inspection) payload rather than the live
    // `class-ranks/{character}` endpoint.
    class_id?: number;
    class?: string;
    base_class_info?: {
        name: string | null;
        description: string | null;
    };
    requirements?: {
        required_xp: number;
    };
}
