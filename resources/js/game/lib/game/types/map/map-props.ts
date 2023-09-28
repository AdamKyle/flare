import MapState from "../../../../sections/map/types/map-state";
import CharacterCurrenciesDetails from "../character-currencies-details";
import { MapTimerData } from "../game-state";
import PositionType from "./position-type";

export default interface MapProps {

    user_id: number,

    character_id: number,

    view_port: number,

    currencies: CharacterCurrenciesDetails;

    is_dead: boolean;

    is_automaton_running: boolean;

    automation_completed_at: number;

    can_engage_celestials_again_at: number;

    show_celestial_fight_button: (id: number | null) => void;

    set_character_position: (position: PositionType) => void;

    update_character_quests_plane: (plane: string) => void;

    disable_bottom_timer: boolean;

    can_engage_celestial: boolean;

    map_data: MapState | null;

    map_timer_data: MapTimerData;

    update_map_timer_data: (timerData: MapTimerData) => void;

    set_map_data: (mapData: MapState) => void; 
}
