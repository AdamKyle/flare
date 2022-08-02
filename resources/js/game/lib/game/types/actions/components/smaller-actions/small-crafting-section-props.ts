import {CharacterType} from "../../../../character/character-type";
import CharacterStatusType from "../../../../character/character-status-type";

export default interface SmallCraftingSectionProps {
    close_crafting_section: () => void;
    character: CharacterType;
    crafting_time_out: number;
    character_status: CharacterStatusType;
}
