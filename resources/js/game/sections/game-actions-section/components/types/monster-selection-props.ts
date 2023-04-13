import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import {CharacterType} from "../../../../lib/game/character/character-type";
import MonsterSelection from "../monster-selection";

export default interface MonsterSelectionProps {
    monsters: MonsterType[]|[];

    character: CharacterType;

    update_monster_to_fight: (monster: MonsterType|null) => void;

    close_monster_section?: () => void;
}
