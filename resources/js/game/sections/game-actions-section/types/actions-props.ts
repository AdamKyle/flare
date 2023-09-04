import {CharacterType} from "../../../lib/game/character/character-type";
import CharacterStatusType from "../../../lib/game/character/character-status-type";
import PositionType from "../../../lib/game/types/map/position-type";
import { GameActionState } from "../../../lib/game/types/game-state";

export default interface ActionsProps {
    character: CharacterType;

    character_status: CharacterStatusType;

    character_position: PositionType | null;

    celestial_id: number;

    update_celestial: (celestialId: number | null) => void;

    can_engage_celestial: boolean;

    action_data: GameActionState | null;
}
