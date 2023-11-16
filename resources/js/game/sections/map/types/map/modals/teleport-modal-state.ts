import LocationDetails from "../../location-details";
import PlayerKingdomsDetails from "../player-kingdoms-details";
import NpcKingdomsDetails from "../npc-kingdoms-details";

export default interface TeleportModalState {

    x_position: number;

    y_position: number;

    character_position: {
        x: number, y: number
    };

    cost: number;

    can_afford: boolean;

    distance: number;

    time_out: number;

    current_location: LocationDetails | null;

    current_player_kingdom: PlayerKingdomsDetails | null;

    current_enemy_kingdom: PlayerKingdomsDetails | null;

    current_npc_kingdom: NpcKingdomsDetails | null;

    view_port: number | null;

    show_help: boolean;
}
