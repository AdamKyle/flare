import GameClassType from "./game-class-type";

export default interface ClassRankType {
    character_id: number;
    class_name: string;
    current_xp: number;
    game_class: GameClassType;
    game_class_id: number;
    id: number;
    is_active: boolean;
    is_locked: boolean;
    level: number;
    required_xp: number;
}
