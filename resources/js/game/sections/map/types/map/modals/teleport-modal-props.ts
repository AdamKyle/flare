import NpcKingdomsDetails from "../../../../../components/kingdoms/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../../../../components/kingdoms/types/map/player-kingdoms-details";
import CharacterCurrenciesType from "../../../../../lib/game/character/character-currencies-type";
import LocationDetails from "../../location-details";

export default interface TeleportModalProps {
    is_open: boolean;

    handle_close: () => void;

    title: string;

    coordinates: { x: number[]; y: number[] } | null;

    character_position: { x: number; y: number };

    currencies?: CharacterCurrenciesType;

    view_port?: number;

    locations: LocationDetails[] | null;

    player_kingdoms: PlayerKingdomsDetails[] | null;

    enemy_kingdoms: PlayerKingdomsDetails[] | null;

    npc_kingdoms: NpcKingdomsDetails[] | null;

    teleport_player: (data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) => void;
}
