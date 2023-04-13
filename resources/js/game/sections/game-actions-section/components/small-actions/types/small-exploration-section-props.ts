import {CharacterType} from "../../../../../lib/game/character/character-type";
import MonsterType from "../../../../../lib/game/types/actions/monster/monster-type";

export default interface SmallExplorationSectionProps {
    close_exploration_section: () => void;

    character: CharacterType;

    monsters: MonsterType[];
}
