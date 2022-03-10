import LocationDetails from "../../map/types/location-details";

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

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number}[] | null;

    npc_kingdoms: {id: number, x_position: number, y_position: number, npc_owned: boolean}[] | null;

    coordinates: {x: number[], y: number[]} | null;

    time_left: number,

    can_player_move: boolean,

    characters_on_map: number,
}
