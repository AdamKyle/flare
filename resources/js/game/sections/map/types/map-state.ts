import NpcKingdomsDetails from "../../../lib/game/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../../lib/game/types/map/player-kingdoms-details";
import LocationDetails from "../../../lib/game/map/types/location-details";

export type MapDetails = {
    map_url: string;

    map_id: number | 0;

    map_position: {x: number, y: number};

    map_name: string;

    game_map_id: number;

    character_position: {x: number, y: number};

    locations: LocationDetails[] | null;

    port_location: LocationDetails | null;

    player_kingdoms: PlayerKingdomsDetails[] | [];

    enemy_kingdoms: PlayerKingdomsDetails[] | [];

    npc_kingdoms: NpcKingdomsDetails[] | [];

    coordinates: {x: number[], y: number[]} | null;

    can_player_move: boolean,

    time_left: number,

    characters_on_map: number,
}

export default interface MapState extends MapDetails {
    loading: boolean;

    automation_time_out: number;

    bottom_bounds: number,

    right_bounds:  number,

    celestial_time_out: number,
}
