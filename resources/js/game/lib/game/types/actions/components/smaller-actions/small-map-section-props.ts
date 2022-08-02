import {CharacterType} from "../../../../character/character-type";
import CharacterCurrenciesDetails from "../../../character-currencies-details";
import PositionType from "../../../map/position-type";

export default interface SmallMapSectionProps {
    close_map_section: () => void;

    update_celestial: (id: number | null) => void

    view_port: number;

    character: CharacterType;

    character_currencies: CharacterCurrenciesDetails;

    update_plane_quests: (plane: string) => void;

    update_character_position: (position: PositionType) => void;
}
