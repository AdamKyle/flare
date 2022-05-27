import {CharacterType} from "../../character/character-type";

export default interface CharacterSheetProps {

    character: CharacterType | null;

    finished_loading: boolean;

}
