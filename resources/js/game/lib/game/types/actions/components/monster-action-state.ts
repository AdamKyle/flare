import MonsterType from "../monster/monster-type";

export default interface MonsterActionState {
    monster_to_fight: MonsterType | null;

    is_same_monster: boolean;

    character_revived: boolean;

    attack_time_out: number;

    rank_selected: number;
}
