import {CharacterType} from "../character/character-type";

export default interface CharacterTopSectionProps {

    view_port: number;

    update_character_status: (characterStatus: {is_dead: boolean, can_adventure: boolean}) => void

    update_character_currencies: (currencies: {gold: number, shards: number, gold_dust: number, copper_coins: number}) => void

    character: CharacterType | null;
}
