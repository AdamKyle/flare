import LocationDetails from "../../../map/types/location-details";
import CharacterCurrenciesDetails from "../../character-currencies-details";

export default interface LocationProps {

    locations: LocationDetails[] | null;

    character_position: {x: number, y: number};

    currencies?: CharacterCurrenciesDetails;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;
}
