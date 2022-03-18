import {CharacterType} from "./character/character-type";
import MonsterType from "./monster/monster-type";

export default interface FightSectionProps {

    character: CharacterType|null;

    monster_to_fight: MonsterType;
}
