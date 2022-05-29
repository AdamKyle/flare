import LocationDetails from "../types/location-details";

export default interface MapState {

    map_url: string;

    map_position: {x: number, y: number};

    game_map_id: number;

    character_position: {x: number, y: number};

    locations: LocationDetails[] | null;

    location_with_adventures: LocationDetails | null;

    port_location: LocationDetails | null;

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    npc_kingdoms: {id: number, x_position: number, y_position: number, npc_owned: boolean}[] | null;

    coordinates: {x: number[], y: number[]} | null;

    can_player_move: boolean,

    time_left: number,

    characters_on_map: number,
}
