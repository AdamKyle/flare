export default interface MapActionsProps {

    move_player: (direction: string) => void,

    can_player_move: boolean,

    players_on_map: number,

    location_with_adventures: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] } | null;

    port_location: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] } | null;

    ports: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {id: number, name:string}[] }[] | null;

    coordinates: {x: number[], y: number[]} | null;

    character_position: {x: number, y: number};

    currencies: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    } | null;
}
