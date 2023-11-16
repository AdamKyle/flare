import { CharacterType } from "../../../../../lib/game/character/character-type";
import MapState from "../../../../map/types/map-state";
import CharacterCurrenciesDetails from "../../../../../lib/game/types/character-currencies-details";
import PositionType from "../../../../map/types/map/position-type";
import { MapTimerData } from "../../../../../lib/game/types/game-state";

export default interface SmallMapSectionProps {
    close_map_section: () => void;

    update_celestial: (id: number | null) => void

    view_port: number;

    character: CharacterType;

    character_currencies: CharacterCurrenciesDetails;

    update_plane_quests: (plane: string) => void;

    update_character_position: (position: PositionType) => void;

    map_data: MapState | null;

    map_timer_data: MapTimerData;

    set_map_data: (mapData: MapState) => void;
}
