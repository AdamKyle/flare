import LocationDetails from "./location-details";
import PlayerKingdomsDetails from "../../types/map/player-kingdoms-details";
import NpcKingdomsDetails from "../../types/map/npc-kingdoms-details";
import CharacterCurrenciesDetails from "../../types/character-currencies-details";
import MapData from "../request-types/MapData";

export default interface MapActionsProps {

    can_move: boolean;

    is_dead: boolean;

    is_automation_running: boolean;

    port_location: LocationDetails | null;

    locations: LocationDetails[] | [];

    character_position: {x: number, y: number};

    player_kingdoms: PlayerKingdomsDetails[] | [];

    npc_kingdoms: NpcKingdomsDetails[] | [];

    enemy_kingdoms: PlayerKingdomsDetails[] | [];

    character_currencies: CharacterCurrenciesDetails;

    coordinates: {x: number[], y: number[]} | null;

    view_port: number;

    character_id: number;

    update_map_state: (data: MapData, callback?: () => void) => void;

    map_id: number;
}
