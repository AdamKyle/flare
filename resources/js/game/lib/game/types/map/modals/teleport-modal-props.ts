import {ClassArray, ClassDictionary} from "clsx";
import LocationDetails from "../../../map/types/location-details";

export default interface TeleportModalProps  {

    is_open: boolean;

    handle_close: () => void;

    handle_action: (args: any) => void;

    title: string;

    coordinates: {x: number[], y: number[]} | null;

    character_position: { x: number, y: number },

    currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };

    view_port: number;

    locations: LocationDetails[] | null;

    player_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    enemy_kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number, name: string}[] | null;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void
}
