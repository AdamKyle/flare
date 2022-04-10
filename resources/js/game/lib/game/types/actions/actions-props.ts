import {CharacterType} from "../../character/character-type";
import CharacterStatusType from "../../character/character-status-type";

export default interface ActionsProps {
    character_id : number;
    character: CharacterType;
    character_statuses: CharacterStatusType | null;
}
