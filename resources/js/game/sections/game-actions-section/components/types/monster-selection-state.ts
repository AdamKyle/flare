import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";

export default interface MonsterSelectionState {
    monster_to_fight: MonsterType | null;

    monsters: MonsterType[]|[],
}
