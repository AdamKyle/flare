import {CharacterType} from "../../../../../character/character-type";

export default interface GamblingSectionProps {

    character: CharacterType;

    close_gambling_section: () => void;
}
