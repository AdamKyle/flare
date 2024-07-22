import NpcKingdomsDetails from "../../../../../components/kingdoms/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../../../../components/kingdoms/types/map/player-kingdoms-details";
import LocationDetails from "../../location-details";

export default interface TeleportModalState {
    x_position: number;

    y_position: number;

    character_position: {
        x: number;
        y: number;
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
