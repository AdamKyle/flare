import LocationDetails from "../../../location-details";
import CharacterCurrenciesDetails from "../../../../../../lib/game/types/character-currencies-details";

export interface LocationModalPros {
    is_open: boolean;

    handle_close: () => void;

    title: string;

    location: LocationDetails;

    character_position?: {x: number, y: number};

    currencies?: CharacterCurrenciesDetails;

    teleport_player?: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    hide_secondary_button: boolean | null;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;
}
