import {ClassArray, ClassDictionary} from "clsx";
import LocationDetails from "../../location-details";
import CharacterCurrenciesDetails from "../../../../../lib/game/types/character-currencies-details";

export default interface SetSailModalProps  {

    is_open: boolean;

    handle_close: () => void;

    title: string;

    character_position: { x: number, y: number },

    currencies?: CharacterCurrenciesDetails;

    ports: LocationDetails[] | null;

    set_sail: (data: {x: number, y: number, cost: number, timeout: number}) => void;
}
