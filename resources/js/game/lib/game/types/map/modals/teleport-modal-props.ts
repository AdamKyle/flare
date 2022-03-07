import DialogueTypes from "../../../../ui/types/dialogue/dialogue-types";

export default interface TeleportModalProps extends DialogueTypes {

    coordinates: {x: number[], y: number[]} | null;

    character_position: { x: number, y: number },

    currencies: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    } | null;
}
