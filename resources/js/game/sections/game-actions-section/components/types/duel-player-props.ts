import {CharacterType} from "../../../../lib/game/character/character-type";
import DuelData from "../../../../lib/game/types/core/duel-player/definitions/duel-data";

export default interface DuelPlayerProps {
    character: CharacterType;
    characters: CharactersList[]|[];
    duel_data: DuelData|null;
    manage_pvp: () => void;
    reset_duel_data: () => void;
    is_small: boolean;
}

export interface CharactersList {
    character_position_x: number;
    character_position_y: number;
    game_map_id: number;
    id: number;
    name: string;
}
