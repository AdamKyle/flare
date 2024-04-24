import CharacterCurrenciesDetails from "../../../../../../lib/game/types/character-currencies-details";

export default interface KingdomModalProps {
    is_open: boolean;

    handle_close: () => void;

    kingdom_id: number;

    character_id: number;

    character_position?: { x: number; y: number };

    currencies?: CharacterCurrenciesDetails;

    teleport_player?: (data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) => void;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;

    show_top_section: boolean;
}
