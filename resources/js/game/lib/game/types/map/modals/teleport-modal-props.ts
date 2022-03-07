import DialogueTypes from "../../../../ui/types/dialogue/dialogue-types";

export default interface TeleportModalProps extends DialogueTypes {

    ports: { id: number, is_port: boolean, x: number, y: number, name: string, adventures: {name: string, id: number}[]}[] | null;
}
