import LocationDetails from "../../map/types/location-details";
import PlayerKingdomsDetails from "./player-kingdoms-details";
import NpcKingdomsDetails from "./npc-kingdoms-details";

export default interface MapState {
    map_url: string;

    map_position: {x: number, y: number};

    character_position: {x: number, y: number};

    bottom_bounds: number,

    right_bounds:  number,

    locations: LocationDetails[] | null;

    location_with_adventures: LocationDetails | null;

    port_location: LocationDetails | null;

    loading: boolean;

    player_kingdoms: PlayerKingdomsDetails[] | null;

    enemy_kingdoms: PlayerKingdomsDetails[] | null;

    npc_kingdoms: NpcKingdomsDetails[] | null;

    coordinates: {x: number[], y: number[]} | null;

    time_left: number,

    can_player_move: boolean,

    characters_on_map: number,
}
