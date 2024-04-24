import { CharacterType } from "../../../../lib/game/character/character-type";

export default interface GamblingSectionProps {
    character: CharacterType;

    close_gambling_section: () => void;

    is_small: boolean;
}
