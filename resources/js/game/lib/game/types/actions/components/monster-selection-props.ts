import MonsterType from "../monster/monster-type";
import {CharacterType} from "../../../character/character-type";

export default interface MonsterSelectionProps {
    monsters: MonsterType[]|[];

    character: CharacterType;

    update_monster_to_fight: (monster: MonsterType|null) => void;
}
