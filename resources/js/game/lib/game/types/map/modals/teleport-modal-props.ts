import DialogueTypes from "../../../../ui/types/dialogue/dialogue-types";
import {ClassArray, ClassDictionary} from "clsx";

export type ClassValue = ClassArray | ClassDictionary | string | number | null | boolean | undefined;

export default interface TeleportModalProps  {

    is_open: boolean;

    handle_close: () => void;

    handle_action: (args: any) => void;

    title: string;

    coordinates: {x: number[], y: number[]} | null;

    character_position: { x: number, y: number },

    currencies: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    } | null;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void
}
