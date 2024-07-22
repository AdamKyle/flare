import CharacterCurrenciesDetails from "../../../../../lib/game/types/character-currencies-details";
import PlayerKingdomsDetails from "../player-kingdoms-details";

export default interface KingdomProps {
    kingdoms: PlayerKingdomsDetails[] | [];

    character_id: number;

    character_position: { x: number; y: number; game_map_id: number };

    currencies?: CharacterCurrenciesDetails;

    teleport_player: (data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) => void;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;
}
