export default interface MapState {

    map_url: string;

    map_position: {x: number, y: number};

    character_position: {x: number, y: number};

    locations: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] }[] | null;

    location_with_adventures: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] } | null;

    port_location: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] } | null;

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number}[] | null;

    npc_kingdoms: {id: number, x_position: number, y_position: number, npc_owned: boolean}[] | null;

    can_player_move: boolean,

    time_left: number,

    characters_on_map: number,
}
