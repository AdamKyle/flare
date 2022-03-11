import LocationDetails from "../../map/types/location-details";

export default interface MapActionsProps {

    move_player: (direction: string) => void,

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void

    can_player_move: boolean,

    players_on_map: number,

    location_with_adventures: LocationDetails | null;

    locations: LocationDetails[] | null;

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    port_location: LocationDetails| null;

    ports: LocationDetails[] | null;

    coordinates: {x: number[], y: number[]} | null;

    character_position: {x: number, y: number};

    character_id: number;

    view_port: number,

    currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };
}
