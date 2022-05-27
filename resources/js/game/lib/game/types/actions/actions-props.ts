import {CharacterType} from "../../character/character-type";
import CharacterStatusType from "../../character/character-status-type";
import CharacterCurrenciesType from "../../character/character-currencies-type";

export default interface ActionsProps {
    character_id : number;
    character: CharacterType;
    character_statuses: CharacterStatusType | null;
    currencies?: CharacterCurrenciesType;
    celestial_id: number;
    update_celestial: (celestialId: number|null) => void
}
