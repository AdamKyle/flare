import MonsterType from "../monster/monster-type";

export default interface MonsterSelectionState {
    monster_to_fight: MonsterType | null;

    monsters: MonsterType[]|[],
}
