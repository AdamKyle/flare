import {CharacterType} from "../character/character-type";
import CharacterCurrenciesDetails from "../types/character-currencies-details";

export default interface CharacterTopSectionProps {

    view_port: number;

    update_character_status: (characterStatus: {is_dead: boolean, can_adventure: boolean}) => void

    update_character_currencies: (currencies: CharacterCurrenciesDetails) => void

    character: CharacterType;
}
