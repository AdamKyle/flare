import MonsterType from "../monster/monster-type";
import {CharacterType} from "../../../character/character-type";
import MonsterSelection from "../../../../../sections/game-actions-section/components/monster-selection";

export default interface MonsterSelectionProps {
    monsters: MonsterType[]|[];

    character: CharacterType;

    update_monster_to_fight: (monster: MonsterType|null) => void;
}
