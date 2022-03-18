import {CharacterType} from "./character/character-type";
import MonsterType from "./monster/monster-type";

export default interface ActionsState {

    character: CharacterType|null;

    monsters: MonsterType[];

    monster_to_fight: MonsterType|null;

    loading: boolean;
}
