import LocationDetails from "../../map/types/location-details";
import PlayerKingdomsDetails from "./player-kingdoms-details";
import NpcKingdomsDetails from "./npc-kingdoms-details";

export default interface MapState {
    map_url: string;

    map_id: number | 0;

    game_map_id: number | 0;

    map_position: {x: number, y: number};

    character_position: {x: number, y: number};

    bottom_bounds: number,

    right_bounds:  number,

    locations: LocationDetails[] | [];

    port_location: LocationDetails | null;

    loading: boolean;

    player_kingdoms: PlayerKingdomsDetails[] | [];

    enemy_kingdoms: PlayerKingdomsDetails[] | [];

    npc_kingdoms: NpcKingdomsDetails[] | [];

    coordinates: {x: number[], y: number[]} | null;

    time_left: number,

    automation_time_out: number;

    can_player_move: boolean,

    characters_on_map: number,
}
