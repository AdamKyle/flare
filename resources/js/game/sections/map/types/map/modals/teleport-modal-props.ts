import {ClassArray, ClassDictionary} from "clsx";
import LocationDetails from "../../location-details";
import PlayerKingdomsDetails from "../player-kingdoms-details";
import CharacterCurrenciesType from "../../../../../lib/game/character/character-currencies-type";
import NpcKingdoms from "../../../../components/kingdoms/npc-kingdoms";
import NpcKingdomsDetails from "../npc-kingdoms-details";

export default interface TeleportModalProps  {

    is_open: boolean;

    handle_close: () => void;

    title: string;

    coordinates: {x: number[], y: number[]} | null;

    character_position: { x: number, y: number },

    currencies?: CharacterCurrenciesType;

    view_port?: number;

    locations: LocationDetails[] | null;

    player_kingdoms: PlayerKingdomsDetails[] | null;

    enemy_kingdoms: PlayerKingdomsDetails[] | null;

    npc_kingdoms: NpcKingdomsDetails[] | null;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void
}
