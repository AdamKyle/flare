import LocationDetails from "../../location-details";
import PlayerKingdomsDetails from "../player-kingdoms-details";

export default interface SetSailModalState {

    x_position: number;

    y_position: number;

    character_position: {
        x: number; y: number;
    };

    current_location: LocationDetails | null;

    current_player_kingdom: PlayerKingdomsDetails | null;

    current_enemy_kingdom: PlayerKingdomsDetails | null;

    cost: number;

    can_afford: boolean;

    distance: number;

    time_out: number;

    current_port: LocationDetails | null;

    view_port: number | null;

    show_help: boolean;
}
