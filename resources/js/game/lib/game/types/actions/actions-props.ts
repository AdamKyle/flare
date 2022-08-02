import {CharacterType} from "../../character/character-type";
import CharacterStatusType from "../../character/character-status-type";
import PositionType from "../map/position-type";

export default interface ActionsProps {
    character: CharacterType;

    character_status: CharacterStatusType;

    character_position: PositionType | null;

    celestial_id: number;

    update_celestial: (celestialId: number | null) => void;
}
