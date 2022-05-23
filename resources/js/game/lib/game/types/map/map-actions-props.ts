import LocationDetails from "../../map/types/location-details";
import NpcKingdomsDetails from "./npc-kingdoms-details";

export default interface MapActionsProps {

    move_player: (direction: string) => void;

    map_id: number | 0;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    set_sail: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    can_player_move: boolean;

    players_on_map: number;

    locations: LocationDetails[] | null;

    npc_kingdoms: NpcKingdomsDetails[] | null;

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    port_location: LocationDetails| null;

    ports: LocationDetails[] | null;

    coordinates: {x: number[], y: number[]} | null;

    character_position: {x: number, y: number};

    character_id: number;

    view_port: number;

    is_dead?: boolean;

    currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };

    is_automation_running: boolean;
}
