import {CharacterType} from "../../../../character/character-type";
import MonsterType from "../../monster/monster-type";

export default interface SmallExplorationSectionProps {
    close_exploration_section: () => void;

    character: CharacterType;

    monsters: MonsterType[];
}
