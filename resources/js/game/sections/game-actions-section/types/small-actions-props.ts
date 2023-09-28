import CharacterCurrenciesDetails from "../../../lib/game/types/character-currencies-details";
import ActionsProps from "./actions-props";
import PositionType from "../../../lib/game/types/map/position-type";
import MapState from "../../map/types/map-state";
import { MapTimerData } from "../../../lib/game/types/game-state";

export default interface SmallActionsProps extends ActionsProps {
    character_currencies: CharacterCurrenciesDetails;

    update_plane_quests: (plane: string) => void;

    update_character_position: (position: PositionType) => void;

    view_port: number;

    map_data: MapState | null;

    map_timer_data: MapTimerData;

    update_map_timer_data: (timerData: MapTimerData) => void;

    set_map_data: (mapData: MapState) => void;
}
